<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Api;

use Fedex\MarketplacePunchout\Api\Data\CustomerPunchoutUniqueIdSearchResultInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Api\SearchCriteriaInterface;
use Fedex\MarketplacePunchout\Api\Data\CustomerPunchoutUniqueIdInterface;
use Magento\Framework\Exception\NoSuchEntityException;

interface CustomerPunchoutUniqueIdRepositoryInterface
{
    /**
     * @param int $id
     * @return CustomerPunchoutUniqueIdInterface
     * @throws NoSuchEntityException
     */
    public function getById($id);

    /**
     * @param CustomerPunchoutUniqueIdInterface $customerPunchoutUniqueId
     * @return CustomerPunchoutUniqueIdInterface
     */
    public function save(CustomerPunchoutUniqueIdInterface $customerPunchoutUniqueId);

    /**
     * @param CustomerPunchoutUniqueIdInterface $customerPunchoutUniqueId
     * @return void
     */
    public function delete(CustomerPunchoutUniqueIdInterface $customerPunchoutUniqueId);

    /**
     * @param Customer|CustomerInterface $customer
     * @return string
     */
    public function retrieveCustomerUniqueId(Customer|CustomerInterface $customer);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return CustomerPunchoutUniqueIdSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);
}
