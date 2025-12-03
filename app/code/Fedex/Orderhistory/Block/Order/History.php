<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Orderhistory\Block\Order;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Block\Order\History as ParentOrderHistory;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Sales\Model\Order\Config;
use Fedex\Orderhistory\Helper\Data;
use Fedex\SharedDetails\ViewModel\SharedEnhancement;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;

/**
 * Order history view
 *
 */
class History extends ParentOrderHistory
{
    /**
     * Constant for Default Order by Value in Sorting
     */
    public const ORDER_BY_DEFAULT_OPTION  = 'DESC';
    /**
     * Constant for Default Sort by Value in Sorting
     */
    public const SORT_BY_DEFAULT_OPTION = 'created_at';
    /**
     * @var string
     */
    protected $template = 'Magento_Sales::order/history.phtml';

    /**
     * @var string
     */
    protected $customEproTemplate = 'Fedex_Orderhistory::order/history.phtml';

    /**
     * @var string
     */
    protected $customRetailTemplate = 'Fedex_Orderhistory::order/retail-history.phtml';

    /**
     * @var CollectionFactoryInterface
     */
    private $orderCollectionFactory;

    /**
     * @param Context $context
     * @param CollectionFactory $orderCollectionFactory
     * @param Session $customerSession
     * @param Config $orderConfig
     * @param Data $orderHistoryDataHelper
     * @param RequestInterface $requestInterface
     * @param SharedEnhancement $sharedEnhancement
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $orderCollectionFactory,
        Session $customerSession,
        Config $orderConfig,
        protected Data $orderHistoryDataHelper,
        protected RequestInterface $requestInterface,
        protected SharedEnhancement $sharedEnhancement,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $orderCollectionFactory,
            $customerSession,
            $orderConfig,
            $data
        );
    }

    /**
     * Provide order collection factory
     *
     * @return CollectionFactoryInterface
     * @deprecated 100.1.1
     */
    private function getOrderCollectionFactory()
    {
        if ($this->orderCollectionFactory === null) {
            $this->orderCollectionFactory = ObjectManager::getInstance()->get(CollectionFactoryInterface::class);
        }

        return $this->orderCollectionFactory;
    }


    /**
     * @param $sortBy
     * @return string
     */
    public function isValidSortOption($sortBy): string
    {
        $sortOptions = ['created_at'];
        if (in_array($sortBy, $sortOptions)){
            return $sortBy;
        }
        return self::SORT_BY_DEFAULT_OPTION;
    }

    /**
     * @param $orderBy
     * @return string
     */
    public function isValidOrderOption($orderBy): string
    {
        $orderOptions = ['ASC', 'DESC'];
        if (in_array($orderBy, $orderOptions)){
            return $orderBy;
        }
        return self::ORDER_BY_DEFAULT_OPTION;
    }
    /**
     * Get customer orders
     *
     * @return bool|\Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getOrders()
    {
        $sortBy = $this->requestInterface->getParam('sortby') ? $this->requestInterface->getParam('sortby') :
            self::SORT_BY_DEFAULT_OPTION;

        $orderBy = $this->requestInterface->getParam('orderby') ? $this->requestInterface->getParam('orderby') :
            self::ORDER_BY_DEFAULT_OPTION;

        $customerId = $this->_customerSession->getCustomerId();
        if (!$customerId) {
            return false;
        }
        $customerGroupId = $this->_customerSession->getCustomer()->getGroupId();
        if (!$this->orders) {
            if ($this->orderHistoryDataHelper->isModuleEnabled()) {
                if ($this->sharedEnhancement->isSharedOrderPage()) {
                    $companyId = $this->_customerSession->getCustomerCompany();
                    $orderCollection = $this->getOrderCollectionFactory()->create()->addFieldToSelect('*');
                    $orderCollection->getSelect()->join(
                        'company_order_entity',
                        'main_table.entity_id = company_order_entity.order_id',
                        []
                    )->where("company_order_entity.company_id= ?", $companyId);
                    $this->orders = $orderCollection->addFieldToFilter(
                        'main_table.status',
                        ['in' => $this->_orderConfig->getVisibleOnFrontStatuses()]
                    );
                } else {
                    $orderCollection = $this->getOrderCollectionFactory()->create($customerId)->addFieldToSelect('*');
                    $this->orders = $orderCollection->addFieldToFilter(
                        'status',
                        ['in' => $this->_orderConfig->getVisibleOnFrontStatuses()]
                    );
                    $this->orders = $orderCollection->addFieldToFilter('main_table.status', ['neq' => 'pending']);
                }
            } else {
                if ($this->sharedEnhancement->isSharedOrderPage()) {
                    $orderCollection = $this->getOrderCollectionFactory()->create()->addFieldToSelect('*');
                    $orderCollection->getSelect()->join(
                        'customer_entity',
                        'main_table.customer_id = customer_entity.entity_id',
                        []
                    )->where("customer_entity.group_id= ?", $customerGroupId);
                } else {
                    $orderCollection = $this->getOrderCollectionFactory()->create($customerId)->addFieldToSelect('*');
                }

                $this->orders = $orderCollection->addFieldToFilter('main_table.status', ['neq' => 'pending']);
            }

            // B-1140445 - RT-ECVS- Ability to sort any of the columns on the Order History page
            if ($this->orderHistoryDataHelper->isModuleEnabled()
                && !$this->orderHistoryDataHelper->getIsSdeStore()) {
                $this->orders->setOrder($this->isValidSortOption($sortBy), $this->isValidOrderOption($orderBy));
            } elseif ($this->orderHistoryDataHelper->isRetailOrderHistoryEnabled() ||
                $this->orderHistoryDataHelper->getIsSdeStore()) {
                $this->orders->setOrder($this->isValidSortOption($sortBy), $this->isValidOrderOption($orderBy));
                $this->orders->getSelect()->columns(
                    [
                        'diff' => new \Zend_Db_Expr('DATEDIFF(NOW(), DATE_ADD(main_table.created_at, INTERVAL 13 Month))')
                    ]
                );
            } else {
                $this->orders->setOrder('created_at', 'DESC');
            }
        }

        $this->orders->addFilterToMap('increment_id', 'main_table.increment_id');

        return $this->orders;
    }

    /**
     * Get relevant path to template
     *
     * @return string
     */
    public function getTemplate()
    {
        if ($this->orderHistoryDataHelper->isModuleEnabled() || $this->orderHistoryDataHelper->getIsSdeStore()) {
            $template = $this->customEproTemplate;
        } elseif ($this->orderHistoryDataHelper->isRetailOrderHistoryEnabled()) {
            $template = $this->customRetailTemplate;
        } else {
            $template = $this->template;
        }

        return $template;
    }

    /**
     * Add Breadcrumbs for epro and sde
     *
     * D-92177 fix for breadcrumb issue in sde
     *
     * @return void
     */
    public function _prepareLayout()
    {
        $strMyProfile = 'My Profile';
        $strMyOrderTitle = 'My Orders';
        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        if ($this->orderHistoryDataHelper->isModuleEnabled()) {
            $breadcrumbs->addCrumb(
                $strMyProfile,
                [
                    'label' => __($strMyProfile),
                    'title' => __($strMyProfile),
                    'link' => $this->getUrl("customer/account"),
                ]
            );
            $breadcrumbs->addCrumb(
                $strMyOrderTitle,
                [
                    'label' => __($strMyOrderTitle),
                    'title' => __($strMyOrderTitle)]
            );
        }
        if ($this->orderHistoryDataHelper->getIsSdeStore()) {
            $breadcrumbs->addCrumb(
                'Home',
                [
                    'label' => __('Home'),
                    'title' => __('Home'),
                    'link' => $this->getBaseUrl(),
                ]
            );
            $breadcrumbs->addCrumb(
                $strMyOrderTitle,
                [
                    'label' => __($strMyOrderTitle),
                    'title' => __($strMyOrderTitle)
                ]
            );
        }

        return parent::_prepareLayout();
    }
}
