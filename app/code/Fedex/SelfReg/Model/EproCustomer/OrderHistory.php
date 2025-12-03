<?php

/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Model\EproCustomer;

use DateInterval;
use Fedex\Delivery\Helper\Data as Deliveryhelper;
use Magento\Customer\Model\Session;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Fedex\SelfReg\Helper\SelfReg;

/**
 * Class OrderHistory
 *
 * This class will be responsible for epro customer order history
 */
class OrderHistory extends AbstractModel
{
    /**
     * OrderHistory Construct.
     *
     * @param TimezoneInterface $localeDate
     * @param Session $customerSession
     * @param Context $context
     * @param Registry $registry
     * @param CollectionFactory $orderCollectionFactory
     * @param Deliveryhelper $deliveryHelper
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param SelfReg $selfRegHelper
     * @param array $data
     */
    public function __construct(
        protected TimezoneInterface $localeDate,
        protected Session $customerSession,
        Context $context,
        Registry $registry,
        public CollectionFactory $orderCollectionFactory,
        protected Deliveryhelper $deliveryHelper,
        protected QuoteCollectionFactory $quoteCollectionFactory,
        private SelfReg $selfRegHelper,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Get Order count for SDE Home page
     *
     * B-1255699 Ajax implementation for updating order count in home page
     *
     * @return array
     */
    public function getOrderCountForHomepage()
    {
        return [
            'submitted' => $this->getSubmittedOrderCount(),
            'quote' => $this->getQuoteCount(),
            'progress' => $this->getInProgressOrderCount(),
            'completed' => $this->getCompletedOrderCount(),
        ];
    }

    /**
     * Get Submitted Orders Count
     *
     * @return int
     */
    public function getSubmittedOrderCount()
    {
        $customerOrder = 0;
        $isSelfRegCustomer = $this->selfRegHelper->isSelfRegCustomer();
        /** B-1857860 **/
        if (
            $isSelfRegCustomer || 
            ($this->deliveryHelper->isCommercialCustomer())
        ) {
            $customerOrder = $this->getOrderFilter();
            $customerOrder->addFieldToFilter('ext_order_id', array('neq' => 'NULL'));
            return $customerOrder->count();
        }

        return $customerOrder;
    }

    /**
     * Get Submitted Quote Count
     *
     * @return int
     */
    public function getQuoteCount()
    {
        $customerId = $this->getCustomerId();
        $date = $this->localeDate->date();
        $this->localeDate->convertConfigTimeToUtc($date);
        $utcTimestampTo = $date->add(new DateInterval('P1D'))
            ->format(DateTime::DATETIME_PHP_FORMAT);

        // 30 days before
        $utcTimestampFrom = $date->sub(new DateInterval('P30D'))
            ->format(DateTime::DATETIME_PHP_FORMAT);

        $customerOrder = $this->orderCollectionFactory->create()
            ->addFieldToSelect('quote_id')
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('ext_order_id', array('neq' => 'NULL'))->getColumnValues('quote_id');

        $customerQuote = $this->quoteCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('entity_id', ['nin' => $customerOrder])
            ->addFieldToFilter('is_active', 0)
            ->addFieldToFilter('created_at', ['from' => $utcTimestampFrom, 'to' => $utcTimestampTo]);

        return $customerQuote->count();
    }

    /**
     * Get Submitted Complete Count
     *
     * @return int
     */
    public function getCompletedOrderCount()
    {
        $customerOrder = $this->getOrderFilter();
        $customerOrder = $customerOrder->addFieldToFilter(
            'status',
            [
                'in' => ['ready_for_pickup', 'shipped', 'complete'],
            ]
        );

        return $customerOrder->count();
    }

    /**
     * Get Submitted In Progress Count
     *
     * @return int
     */
    public function getInProgressOrderCount()
    {
        $customerOrder = $this->getOrderFilter();
        $customerOrder = $customerOrder->addFieldToFilter('status', ['in' => ['in_process']]);
        return $customerOrder->count();
    }

    /**
     * Get customer id
     *
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customerSession->getId();
    }

    /**
     * Perpare order collection filter
     *
     * @return object
     */
    public function getOrderFilter()
    {
        $customerId = $this->getCustomerId();
        $date = $this->localeDate->date();
        $this->localeDate->convertConfigTimeToUtc($date);
        $utcTimestampTo = $date->add(new DateInterval('P1D'))
            ->format(DateTime::DATETIME_PHP_FORMAT);
        // 30 days before
        $utcTimestampFrom = $date->sub(new DateInterval('P30D'))
            ->format(DateTime::DATETIME_PHP_FORMAT);
        return $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('created_at', ['from' => $utcTimestampFrom, 'to' => $utcTimestampTo]);
    }
}
