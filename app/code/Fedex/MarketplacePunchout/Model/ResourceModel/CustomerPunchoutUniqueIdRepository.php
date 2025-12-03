<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model\ResourceModel;

use Fedex\MarketplacePunchout\Api\CustomerPunchoutUniqueIdRepositoryInterface;
use Fedex\MarketplacePunchout\Api\Data\CustomerPunchoutUniqueIdInterface;
use Fedex\MarketplacePunchout\Api\Data\CustomerPunchoutUniqueIdSearchResultInterfaceFactory;
use Fedex\MarketplacePunchout\Model\ResourceModel\CustomerPunchoutUniqueId\CollectionFactory as CustomerPunchoutUniqueIdCollectionFactory;
use Fedex\MarketplacePunchout\Model\CustomerPunchoutUniqueIdFactory;
use Fedex\MarketplacePunchout\Model\CustomerPunchoutUniqueId;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Math\Random;
use Psr\Log\LoggerInterface;

class CustomerPunchoutUniqueIdRepository implements CustomerPunchoutUniqueIdRepositoryInterface
{
    const CHARS_DIGITS = '0123456789';

    /**
     * @param CustomerPunchoutUniqueIdFactory $customerPunchoutUniqueIdFactory
     * @param CustomerPunchoutUniqueIdCollectionFactory $customerPunchoutUniqueIdCollectionFactory
     * @param CustomerPunchoutUniqueIdSearchResultInterfaceFactory $customerPunchoutUniqueIdSearchResultInterfaceFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param Random $random
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CustomerPunchoutUniqueIdFactory                        $customerPunchoutUniqueIdFactory,
        private readonly CustomerPunchoutUniqueIdCollectionFactory              $customerPunchoutUniqueIdCollectionFactory,
        private readonly CustomerPunchoutUniqueIdSearchResultInterfaceFactory   $customerPunchoutUniqueIdSearchResultInterfaceFactory,
        private readonly CollectionProcessorInterface                           $collectionProcessor,
        private readonly Random                                                 $random,
        protected LoggerInterface                                                 $logger
    ) {}

    /**
     * @inheritDoc
     */
    public function getById($id)
    {
        $customerPunchoutUniqueId = $this->customerPunchoutUniqueIdFactory->create();
        $customerPunchoutUniqueId->getResource()->load($customerPunchoutUniqueId, $id);
        if (!$customerPunchoutUniqueId->getId()) {
            throw new NoSuchEntityException(__('Unable to find Customer Punchout Unique ID with Customer ID "%1"', $id));
        }
        return $customerPunchoutUniqueId;
    }

    /**
     * @inheritDoc
     */
    public function save(CustomerPunchoutUniqueIdInterface $customerPunchoutUniqueId)
    {
        $customerPunchoutUniqueId->getResource()->save($customerPunchoutUniqueId);
        return $customerPunchoutUniqueId;
    }

    /**
     * @inheritDoc
     */
    public function delete(CustomerPunchoutUniqueIdInterface $customerPunchoutUniqueId)
    {
        $customerPunchoutUniqueId->getResource()->delete($customerPunchoutUniqueId);
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->customerPunchoutUniqueIdCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, ($collection));

        $searchData = $this->customerPunchoutUniqueIdSearchResultInterfaceFactory->create();
        $searchData->setSearchCriteria($searchCriteria);
        $searchData->setItems($collection->getItems());
        $searchData->setTotalCount($collection->getSize());

        return $searchData;
    }

    /**
     * @param Customer|CustomerInterface $customer
     * @return string
     */
    public function retrieveCustomerUniqueId(Customer|CustomerInterface $customer)
    {
        try {
            $customerPunchoutUniqueId = $this->getById($customer->getId());
        } catch (NoSuchEntityException $noSuchEntityException) {
            $customerPunchoutUniqueId = $this->setupUniqueIdForCustomer($customer);
        }
        return $customerPunchoutUniqueId ? $customerPunchoutUniqueId->getUniqueId() : '';
    }

    /**
     * @param Customer|CustomerInterface $customer
     * @return false|CustomerPunchoutUniqueId
     * @throws LocalizedException
     */
    protected function setupUniqueIdForCustomer(Customer|CustomerInterface $customer)
    {
        $customerPunchoutUniqueId = $this->customerPunchoutUniqueIdFactory->create();

        try {
            $customerId = $customer->getId();
            $customerPunchoutUniqueId->setCustomerId((int)$customerId);
            $customerPunchoutUniqueId->setCustomerEmail($customer->getEmail());
            $uniqueId = $this->generateUniqIdWithCustomer($customerId);
            $customerPunchoutUniqueId->setUniqueId($uniqueId);
            $this->save($customerPunchoutUniqueId);
        } catch (LocalizedException|\Exception $e) {
            $this->logger->error(
                'Couldn\'t setup Unique ID for Customer: '.$customerId.'. Error: '.$e->getMessage()
            );
            $customerPunchoutUniqueId = false;
        }
        return $customerPunchoutUniqueId;
    }

    /**
     * @param $customerId
     * @param $length
     * @return string
     * @throws LocalizedException
     */
    protected function generateUniqIdWithCustomer($customerId, $length = 10)
    {
        return $customerId . $this->random->getRandomString(($length - strlen((string)$customerId)), self::CHARS_DIGITS);
    }
}
