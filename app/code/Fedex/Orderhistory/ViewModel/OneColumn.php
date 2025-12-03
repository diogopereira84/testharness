<?php
declare(strict_types=1);

namespace Fedex\Orderhistory\ViewModel;

use Fedex\EnhancedProfile\Helper\Account;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\Orderhistory\Helper\Data;
use Fedex\EnvironmentManager\Helper\ModuleStatus;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\Delivery\Helper\Data as DeliveryHelper;

class OneColumn implements ArgumentInterface
{
    /**
     * @param Data $helper
     * @param ModuleStatus $moduleStatus
     * @param SelfReg $selfRegHelper
     * @param Account $accountHelper
     * @param DeliveryHelper $deliveryHelper
     */
    public function __construct(
        public Data         $helper,
        public ModuleStatus $moduleStatus,
        public SelfReg      $selfRegHelper,
        public Account      $accountHelper,
        protected DeliveryHelper $deliveryHelper
    )
    {
    }

    /**
     * Is module enable
     *
     * @param string $moduleName
     * @return bool true|false
     */
    public function isModuleEnable($moduleName)
    {
        return $this->moduleStatus->isModuleEnable($moduleName);
    }

    /**
     * @inheritDoc
     */
    public function isSetOneColumn()
    {
        return $this->helper->isSetOneColumn();
    }

    /**
     * @inheritDoc
     */
    public function isSdeStoreEnabled()
    {
        return $this->helper->getIsSdeStore();
    }

    /**
     * @inheritDoc
     */
    public function isSetOneColumnRetail()
    {
        return $this->helper->isSetOneColumnRetail();
    }

    /**
     * @inheritDoc
     *
     * B-1112160 - View Quote Details
     */
    public function isEnhancementClass()
    {
        return $this->helper->isEnhancementClass();
    }

    /**
     * @inheritDoc
     *
     * Retail- B-1140444 RT-ECVS-Ability to expands order detail on click of View Order
     */
    public function isRetailEnhancementClass()
    {
        return $this->helper->isRetailEnhancementClass();
    }

    /**
     * @inheritDoc
     *
     * Retail- B-1219239 RT-ECVS- My Order is accessible from left nav bar
     */
    public function isSetOneColumnRetailReOrder()
    {
        return $this->helper->isRetailOrderHistoryReorderEnabled();
    }

    /**
     * @inheritDoc
     *
     * My Order is accessible from left nav bar
     */
    public function isSetTwoColumnEproReOrder()
    {
        return $this->helper->isCommercialReorderEnabled();
    }

    /**
     * @inheritDoc
     * B-1501794
     * My Quotes is accessible from left nav bar
     */
    public function isSelfRegCompany()
    {
        return $this->selfRegHelper->isSelfRegCompany();
    }

    /**
     * @return bool
     */
    public function isSelfRegCustomerWithFclEnabled()
    {
        return $this->selfRegHelper->isSelfRegCustomerWithFclEnabled();
    }

    /**
     * @return string
     */
    public function getLoginType()
    {
        return $this->accountHelper->getCompanyLoginType();
    }

    /**
     * Check logged customer is from EPRO or not                m
     *
     * @return boolean
     */
    public function isEproCustomer()
    {
        return $this->deliveryHelper->isEproCustomer();
    }
}
