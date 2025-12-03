<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Model\Config\Adminhtml\Attribute\Source;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Company\Model\ResourceModel\Customer as CompanyCustomer;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class EproCustomer implements OptionSourceInterface
{
    private array $options = [];

    public function __construct(
        protected CompanyRepositoryInterface  $companyRepository,
        protected CustomerRepositoryInterface $customerRepository,
        protected SearchCriteriaBuilder       $searchCriteriaBuilder,
        protected FilterBuilder               $filterBuilder,
        protected CompanyCustomer             $companyCustomer,
        protected LoggerInterface             $logger
    )
    {
    }

    public function toOptionArray(): array
    {
        if (empty($this->options)) {
            $customers = $this->getEproCustomers();
            foreach ($customers as $customer) {
                $this->options[] = [
                    'value' => $customer['id'], 'label' => $customer['name']
                ];
            }
            array_unshift($this->options, ['value' => 0, 'label' => __('None')]);
        }
        return $this->options;
    }

    protected function getEproCustomers(): array
    {
        $customers = [];
        $filter = $this->filterBuilder
            ->setField('storefront_login_method_option')
            ->setValue('commercial_store_epro')
            ->setConditionType('eq')
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilters([$filter])
            ->create();
        try {
            $companies = $this->companyRepository->getList($searchCriteria);
            $customerIds = [];
            foreach ($companies->getItems() as $company) {
                $ids = $this->companyCustomer->getCustomerIdsByCompanyId($company->getId());
                array_push($customerIds, ...$ids);
            }
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('entity_id', $customerIds, 'in')
                ->create();
            foreach ($this->customerRepository->getList($searchCriteria)->getItems() as $customer) {
                $customers[] = [
                    'id' => (int)$customer->getId(),
                    'name' => $customer->getId() . ' - ' . $customer->getFirstname() . ' ' . $customer->getLastname()];
            }
        } catch (LocalizedException $le) {
            $this->logger->error($le->getMessage());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $customers;
    }

}
