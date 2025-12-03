<?php

namespace Fedex\SDE\Model\Customer;

use Fedex\Orderhistory\Helper\Data as OrderHistoryHelper;
use Fedex\Shipment\Api\NewOrderUpdateInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use DateInterval;

/**
 * Class OrderHistory
 *
 * This class will be responsible for sde customer order history
 */
class OrderHistory extends AbstractModel
{
    /**
     * OrderHistory Construct.
     *
     * @param TimezoneInterface $localeDate
     * @param Session $customerSession
     * @param OrderHistoryHelper $orderHistoryDataHelper
     * @param Context $context
     * @param Registry $registry
     * @param CollectionFactory $orderCollectionFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @return void
     */
    public function __construct(
        protected TimezoneInterface $localeDate,
        protected Session $customerSession,
        protected OrderHistoryHelper $orderHistoryDataHelper,
        Context $context,
        Registry $registry,
        public CollectionFactory $orderCollectionFactory,
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
        return $this->getOrderCollectionCount();
    }

    /**
     * Get Inprogress Orders Count
     *
     * @return int
     */
    public function getInProgressOrderCount()
    {
        $status = [NewOrderUpdateInterface::INPROCESS];

        return $this->getOrderCollectionCount($status);
    }

    /**
     * Get Completed Orders Count
     *
     * @return int
     */
    public function getCompletedOrderCount()
    {
        $status = [
            NewOrderUpdateInterface::READYFORPICKUP,
            NewOrderUpdateInterface::SHIPPED,
            NewOrderUpdateInterface::COMPLETE,
        ];

        return $this->getOrderCollectionCount($status);
    }

    /**
     * Get Order Collection Count based on Order status
     *
     * @param array $status
     * @return int
     */
    public function getOrderCollectionCount($status = [])
    {
        $orderCount = 0;
        if ($this->orderHistoryDataHelper->isSDEHomepageEnable()) {
            $date = $this->localeDate->date();
            $this->localeDate->convertConfigTimeToUtc($date);
            $utcTimestampTo = $date->format(DateTime::DATETIME_PHP_FORMAT);
            $utcTimestampTo = $date->add(new DateInterval('P1D'))
                ->format(DateTime::DATETIME_PHP_FORMAT);

            // 30 days before
            $utcTimestampFrom = $date->sub(new DateInterval('P30D'))
                ->format(DateTime::DATETIME_PHP_FORMAT);
            $customerOrderCollection = $this->orderCollectionFactory->create()
                ->addFieldToFilter('customer_id', $this->getCustomerId())
                ->addFieldToFilter('created_at', ['from' => $utcTimestampFrom, 'to' => $utcTimestampTo]);
            if ($status) {
                $customerOrderCollection->addFieldToFilter('status', ['in' => $status]);
            }
            $orderCount = $customerOrderCollection->count();
        }

        return $orderCount;
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
}
