<?php
/**
 * @category  Fedex
 * @package   Fedex_SharedCatalogCustomization
 * @copyright Copyright (c) 2024 FedEx.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\SharedCatalogCustomization\Ui\Component\Form\Field\AdvancedSearchCustomerGroup;

class Group implements OptionSourceInterface
{
    /**
     * @param GroupRepositoryInterface $groupRepository
     * @param SortOrderBuilder $sortOrderBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param ConfigInterface $companyConfigInterface
     */
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly SortOrderBuilder $sortOrderBuilder,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly RequestInterface $request,
        private readonly ConfigInterface $companyConfigInterface
    ) {
    }

    /**
     * {@inheritdoc}
    */
    public function toOptionArray(): array
    {
        $customerGroups = [];
        if ($this->isNewCompany()) {
            $customerGroups[] = [
                'label' => AdvancedSearchCustomerGroup::CREATE_NEW_LABEL,
                'value' => AdvancedSearchCustomerGroup::CREATE_NEW_VALUE,
            ];
        }

        $sort = $this->sortOrderBuilder
            ->setField(GroupInterface::CODE)
            ->setAscendingDirection()
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(GroupInterface::ID, GroupInterface::NOT_LOGGED_IN_ID, 'neq')
            ->addSortOrder($sort)
            ->create();
        $groups = $this->groupRepository->getList($searchCriteria);
        foreach ($groups->getItems() as $group) {
            $customerGroups[] = [
                'label' => $group->getCode(),
                'value' => $group->getId(),
            ];
        }

        return $customerGroups;
    }

    /**
     * @return bool
     */
    public function isNewCompany(): bool
    {
        return !$this->request->getParam('id');
    }
}
