<?php

namespace Fedex\Commercial\Helper;

use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\SelfReg\Helper\SelfReg;

class CommercialHelper extends AbstractHelper
{
    /**
     * Data Class Constructor.
     *
     * @param  Context      $context
     * @param  ToggleConfig $toggleConfig
     * @return void
     */
    public function __construct(
        Context $context,
        private ToggleConfig $toggleConfig,
        protected SdeHelper $sdeHelper,
        protected DeliveryHelper $delivaryHelper,
        protected SelfReg $selfRegHelper,
        private readonly PerformanceImprovementPhaseTwoConfig $performanceImprovementPhaseTwoConfig
    ) {
        parent::__construct($context);
    }

    public function isRolePermissionToggleEnable()
    {
        return $this->toggleConfig->getToggleConfigValue('change_customer_roles_and_permissions');

    }
    public function isGlobalCommercialCustomer()
    {
        $boolFlag = false;
        if ($this->commercialHeaderAndFooterEnable()
            && ($this->delivaryHelper->isCommercialCustomer() || $this->sdeHelper->getIsSdeStore())
        ) {
            $boolFlag = true;
        }

        return $boolFlag;
    }

    public function commercialHeaderAndFooterEnable()
    {
        static $return = null;
        if ($return !== null
            && $this->performanceImprovementPhaseTwoConfig->isActive()
        ) {
            return $return;
        }
        $this->selfRegHelper->isSelfRegCustomer();

        /**
         * B-1857860
         */
        $return = true;
        return true;
    }

    public function isCommercialReorderEnable()
    {
        $isSelfRegCustomer = $this->selfRegHelper->isSelfRegCustomer();
        if (($this->delivaryHelper->isEproCustomer() || $isSelfRegCustomer) && !$this->sdeHelper->getIsSdeStore()) {
            return true;
        }

        return false;
    }

    public function isSelfRegAdminUpdates()
    {
        $isSelfRegAdmin = $this->selfRegHelper->isSelfRegCustomerAdmin();
        if(($isSelfRegAdmin || $this->sdeHelper->getIsSdeStore())) {
            return true;
        }
        return false;
    }

    public function getSelfRegAdminUser()
    {
        return $this->delivaryHelper->isSelfRegCustomerAdminUser();
    }

    /**
     * Get Company info for commercial users
     */
    public function getCompanyInfo()
    {
        return $this->delivaryHelper->getOnDemandCompInfo();
    }

    /**
     * return $this->delivaryHelper
     *
     * @return DeliveryHelper $this->delivaryHelper
     */
    public function getDeliveryDataHelper()
    {
        return $this->delivaryHelper;
    }

    /**
     * company settings catalog toggle
     *
     * @return bool
     */
    public function isCompanySettingsToggleEnable()
    {
        return $this->toggleConfig->getToggleConfigValue('explorers_company_settings_customer_admin');
    }

    public function getCompanyAdminUser()
    {
        return $this->delivaryHelper->isCompanyAdminUser();
    }

    /**
     * Personal Address Book Toggle
     *
     * @return bool
     */
    public function isPersonalAddressBookToggleEnable()
    {
        return (bool)$this->toggleConfig->getToggleConfigValue('explorers_e_450676_personal_address_book');
    }
}
