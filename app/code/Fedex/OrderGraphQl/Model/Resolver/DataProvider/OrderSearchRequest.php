<?php
/**
 * @category     Fedex
 * @package      Fedex_OrderGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OrderGraphQl\Model\Resolver\DataProvider;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class OrderSearchRequest
{
    /** @var int */
    const MAX_RESULTS_PARTIAL = 100;
    const TIGER_TK4397896 = 'tiger_tk4397896';

    /** @var array */
    protected array $arguments = [];

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param SortOrderBuilder $sortOrderBuilder
     * @param TimezoneInterface $timezone
     * @param CollectionFactory $orderCollectionFactory
     * @param ToggleConfig $toggleConfig
     * @param array $filters
     */
    public function __construct(
        private SearchCriteriaBuilder             $searchCriteriaBuilder,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly SortOrderBuilder         $sortOrderBuilder,
        private readonly TimezoneInterface        $timezone,
        private readonly CollectionFactory        $orderCollectionFactory,
        private readonly ToggleConfig             $toggleConfig,
        private readonly array                    $filters
    ) {
    }

    /**
     * @return array
     * @throws GraphQlInputException
     */
    public function getOrders(): array
    {
        $filterMap = $this->getFilterMap();

        $this->searchCriteriaBuilder = $this->createFilters(
            $filterMap, $this->searchCriteriaBuilder
        );

        $this->searchCriteriaBuilder = $this->getSortMap(
            $this->searchCriteriaBuilder
        );

        return $this->fetchOrders($this->searchCriteriaBuilder->create());
    }

    /**
     * @param array $args
     * @return array
     * @throws GraphQlInputException
     */
    public function orderSearchRequest(array $args): array
    {
        $this->setArguments($args);
        return $this->getOrders();
    }

    /**
     * @param array $filterMap
     * @param SearchCriteriaBuilder $searchCriteria
     * @return SearchCriteriaBuilder
     */
    private function createFilters(array $filterMap, SearchCriteriaBuilder $searchCriteria): SearchCriteriaBuilder
    {
        $filterMapExtra = ['tiger_tk4397896_toggle_enabled' => (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_TK4397896)];

        foreach ($this->filters as $key => $strategy) {
            if (str_contains(strtolower($key), 'gtn')) {
                $searchCriteria = $strategy->applyFilter($filterMap, $searchCriteria);
                continue;
            }
            if (!array_key_exists($key, $filterMap) || empty($filterMap[$key])) {
                continue;
            }
            $input = str_contains($key, 'telephone')
                ? array_merge($filterMap, $filterMapExtra)
                : $filterMap;

            $searchCriteria = $strategy->applyFilter($input, $searchCriteria);
        }

        return $searchCriteria;
    }

    /**
     * @param SearchCriteria $searchCriteria
     * @return array
     */
    private function fetchOrders(SearchCriteria $searchCriteria): array
    {
        $orderSearchDefectToggle = $this->getOrderGraphQlDefectToggle();
        $filters = $this->getAllFilters($searchCriteria);

        $telephoneValues = $this->getTelephoneValuesFromFilters($filters);
        $incrementIdLikeFilters = [];

        if ($orderSearchDefectToggle && $telephoneValues) {
            $collection = $this->orderCollectionFactory->create();
            $this->joinTelephoneTable($collection, $telephoneValues);

            foreach ($filters as $filter) {
                $field = $filter->getField();
                $condition = $filter->getConditionType() ?? 'eq';
                $value = $filter->getValue();

                if ($field === 'custom_telephone_filter') {
                    continue;
                }

                if ($field === 'main_table.increment_id' && $condition === 'like') {
                    $incrementIdLikeFilters[] = [$condition => $value];
                } else {
                    $collection->addFieldToFilter($field, [$condition => $value]);
                }
            }

            if (!empty($incrementIdLikeFilters)) {
                $collection->addFieldToFilter('main_table.increment_id', $incrementIdLikeFilters);
            }

            $orders = $collection->setPageSize(self::MAX_RESULTS_PARTIAL)
                ->setCurPage(1)
                ->getItems();
        } else {
            $searchCriteria->setPageSize(self::MAX_RESULTS_PARTIAL)
                ->setCurrentPage(1);
            $orders = $this->orderRepository->getList($searchCriteria)->getItems();
        }

        return [
            'orders' => $orders,
            'partial' => count($orders) === self::MAX_RESULTS_PARTIAL
        ];
    }


    /**
     * @return array
     * @example getFilterMapExample
     */
    private function getFilterMap(): array
    {
        $arguments = $this->getArguments();
        return [
            'location_id' => $this->getNestedArgument($arguments, ['filters', 'location', 'id']),
            'increment_id' => $this->getNestedArgument($arguments, ['filters', 'omni', 'text']),
            'omni_attributes' => $this->getNestedArgument($arguments, ['filters', 'omni', 'attributes'], []),
            'customer_email' => $this->getNestedArgument($arguments, ['filters', 'contact', 'emailDetail', 'emailAddress']),
            'customer_firstname' => $this->getNestedArgument($arguments, ['filters', 'contact', 'personName', 'firstName']),
            'customer_lastname' => $this->getNestedArgument($arguments, ['filters', 'contact', 'personName', 'lastName']),
            'telephone' => $this->getNestedArgument($arguments, ['filters', 'contact', 'phoneNumberDetails']),
            'status' => $this->getNestedArgument($arguments, ['filters', 'orderStatuses']),
            'created_at' => $this->getCreatedAtRange($arguments),
            'shipping_due_date' => $this->getShippingDueDateAtRange($arguments)
        ];
    }

    /**
     * Safely retrieves a nested argument from a multidimensional array using a list of keys.
     *
     * @param array $array The array from which to extract the value.
     * @param array $keys An array of keys to navigate through the array.
     * @param mixed $default The default value to return if the target is not found.
     * @return mixed The value found at the nested location, or the default value.
     */
    protected function getNestedArgument(array $array, array $keys, mixed $default = ''): mixed
    {
        foreach ($keys as $key) {
            if (!isset($array[$key])) {
                return $default;
            }
            $array = $array[$key];
        }
        return $array;
    }

    /**
     * @param SearchCriteriaBuilder $searchCriteria
     * @return SearchCriteriaBuilder
     * @example getSortMapExample
     */
    protected function getSortMap(SearchCriteriaBuilder $searchCriteria): SearchCriteriaBuilder
    {
        $orderSearchDefectToggle = $this->getOrderGraphQlDefectToggle();
        if ($orderSearchDefectToggle) {
            $sortOrders = isset($this->getArguments()['sorts']) ? $this->getArguments()['sorts'] : [];
        } else {
            $sortOrders = $this->getArguments()['sorts'];
        }

        foreach ($sortOrders as $sortOrder) {
            $attributeInput = $sortOrder['attribute'];
            $directionInput = $sortOrder['ascending'] ? SortOrder::SORT_ASC : SortOrder::SORT_DESC;

            $mappedAttribute = $this->mapAttribute($attributeInput);
            $this->sortOrderBuilder->setField($mappedAttribute);
            $this->sortOrderBuilder->setDirection($directionInput);
            $searchCriteria->addSortOrder($this->sortOrderBuilder->create());
        }
        return $searchCriteria;
    }

    /**
     * Maps the input attribute to the corresponding database field.
     *
     * @param string $attributeInput The input attribute from the sort option.
     * @return string The database field associated with the input attribute.
     */
    protected function mapAttribute(string $attributeInput): string
    {
        $attributeMap = [
            'submissionTimeDateRange' => 'created_at',
            'ExpectedShipDate' => 'created_at',
            'productionDueTime' => 'shipping_due_date',
            'location' => 'location_id',
            'emailAddress' => 'customer_email',
            'firstName' => 'customer_firstname',
            'lastName' => 'customer_lastname',
            'phoneNumber' => 'telephone',
        ];

        return $attributeMap[$attributeInput] ?? 'entity_id';
    }

    /**
     * @param array $args
     * @return void
     */
    public function setArguments(array $args): void
    {
        $this->arguments = $args;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param $arguments
     * @return array
     */
    protected function getCreatedAtRange($arguments): array
    {
        $rangeFormatted = [];
        $createdAtFrom = $this->getNestedArgument($arguments, ['filters', 'submissionTimeDateRange', 'startDateTime']);
        $createdAtTo = $this->getNestedArgument($arguments, ['filters', 'submissionTimeDateRange', 'endDateTime']);
        if (!empty($createdAtFrom) && !empty($createdAtTo)) {
            $rangeFormatted = [
                'from' => $this->getDatetimeUtcFormatted($createdAtFrom),
                'to' => $this->getDatetimeUtcFormatted($createdAtTo)
            ];
        }
        return $rangeFormatted;
    }

    /**
     * @param $arguments
     * @return array
     */
    protected function getShippingDueDateAtRange($arguments): array
    {
        $rangeFormatted = [];
        $shippingDueDateFrom = $this->getNestedArgument($arguments, ['filters', 'productionDueTimeDateRange', 'startDateTime']);
        $shippingDueDateTo = $this->getNestedArgument($arguments, ['filters', 'productionDueTimeDateRange', 'endDateTime']);
        if (!empty($shippingDueDateFrom) && !empty($shippingDueDateTo)) {
            $rangeFormatted = [
                'from' => $this->getDatetimeUtcFormatted($shippingDueDateFrom),
                'to' => $this->getDatetimeUtcFormatted($shippingDueDateTo)
            ];
        }
        return $rangeFormatted;
    }

    /**
     * @param string $dateTime
     * @return string
     */
    private function getDatetimeUtcFormatted(string $dateTime): string
    {
        return $this->timezone->date($dateTime)
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');
    }

    /**
     * @param SearchCriteria $searchCriteria
     * @return array
     */
    private function getAllFilters(SearchCriteria $searchCriteria): array
    {
        $filters = [];
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $filters = array_merge($filters, $group->getFilters());
        }
        return $filters;
    }

    /**
     * @param array $filters
     * @return array
     */
    private function getTelephoneValuesFromFilters(array $filters): array
    {
        foreach ($filters as $filter) {
            if ($filter->getField() === 'custom_telephone_filter') {
                $value = $filter->getValue();
                return is_array($value) ? $value : [$value];
            }
        }
        return [];
    }

    /**
     * @param Collection $collection
     * @param array $telephoneValues
     * @return void
     */
    private function joinTelephoneTable(Collection $collection, array $telephoneValues): void
    {
        $collection->getSelect()->join(
            ['soa' => $collection->getTable('sales_order_address')],
            'main_table.entity_id = soa.parent_id AND soa.address_type = "billing"',
            []
        )->where('soa.telephone IN (?)', $telephoneValues);
    }

    /**
     * @return bool
     */
    private function getOrderGraphQlDefectToggle(){
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_TK4397896);
    }

}
