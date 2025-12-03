<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CustomizedMegamenu\Plugin\Result;

use Magento\Framework\App\ResponseInterface;
use Fedex\CustomerDetails\Helper\Data;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Magento\Framework\View\Element\Context;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\UrlInterface;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;

/**
 * Page Plugin
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Page
{
    public const TIGER_E424573_OPTIMIZING_PRODUCT_CARDS = 'tiger_E424573_optimizing_product_cards';
    public const TIGER_D195836_FIX_LOAD_TIME_HERO_BANNER = 'tiger_d195836_fix_high_load_time_for_hero_banner';
    public const TIGER_D214669_ADOBE_LIVESEARCH_THUMBNAIL_CUT_OFF_FIX = 'tiger_team_d_214669';
    public const TECH_TITANS_E484727_COMMERCIAL_CATALOG_TYPE = 'tech_titans_e_484727';
    /**
     * Page construct
     *
     * @param Context $context
     * @param Data $helperData
     * @param DeliveryHelper $deliveryHelper
     * @param SdeHelper $sdeHelper
     * @param ToggleConfig $toggleConfig
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        private Context $context,
        private Data $helperData,
        private DeliveryHelper $deliveryHelper,
        protected SdeHelper $sdeHelper,
        private ToggleConfig $toggleConfig,
        private UrlInterface $urlInterface,
        private MarketplaceCheckoutHelper $marketplaceCheckoutHelper
    )
    {
    }

    /**
     * Before Render Result
     *
     * @param $object $subject
     * @param $object $response
     *
     * @return  $array
     */
    public function beforeRenderResult(\Magento\Framework\View\Result\Page $subject, ResponseInterface $response)
    {
        $isLoggedIn = $this->deliveryHelper->isCommercialCustomer();
        if (!$isLoggedIn) {
            $subject->getConfig()->addBodyClass('megamenu-primary-menu');
        }
        $subject->getConfig()->addBodyClass('megamenu-improvement-feature');
        $isSdeStore = $this->sdeHelper->getIsSdeStore();
        if ($isSdeStore) {
            $subject->getConfig()->addBodyClass('cms-sde-home');
        }
        //B-1256522 Add promo-account class name in sde

            $subject->getConfig()->addBodyClass('promo-account');
        if ($isLoggedIn && (!$isSdeStore)) {
            $subject->getConfig()->addBodyClass('epro-store');
        }

        $isEproCustomer = $this->deliveryHelper->isEproCustomer();
        if ($isEproCustomer) {
            $subject->getConfig()->addBodyClass('epro-customer-store');
        }

            $subject->getConfig()->addBodyClass('self_reg_admin_updates');

        if ($this->toggleConfig->getToggleConfigValue('change_customer_roles_and_permissions')) {
            $subject->getConfig()->addBodyClass('update_roles_and_permission');
        }
            $subject->getConfig()->addBodyClass('mazegeeks_download_catalog_items');

            $subject->getConfig()->addBodyClass('profile-error-modal');


        $subject->getConfig()->addBodyClass('catalog_mvp_custom_docs');


        if (strpos($this->urlInterface->getCurrentUrl(), '/shared/order') !== false) {
            $subject->getConfig()->addBodyClass('sales-order-history');
        }

        $isEproAdminUser = $this->deliveryHelper->isCustomerEproAdminUser();

        if($isEproAdminUser) {
            $subject->getConfig()->addBodyClass('epro_admin_user');
        }
        $subject->getConfig()->addBodyClass('my-account-nav-consistency');
        if($this->toggleConfig->getToggleConfigValue('mazegeeks_d_193860_fix')){
            $subject->getConfig()->addBodyClass('mazegeeks_d_193860_fix');
        }
        if($this->toggleConfig->getToggleConfigValue('mazegeeks_ctc_admin_impersonator')){
            $subject->getConfig()->addBodyClass('mazegeeks_ctc_admin_impersonator');
        }
        if($this->toggleConfig->getToggleConfigValue(self::TIGER_E424573_OPTIMIZING_PRODUCT_CARDS)) {
            $subject->getConfig()->addBodyClass('with-prod-ctlg-standard');
        }
        if($this->toggleConfig->getToggleConfigValue(self::TIGER_D195836_FIX_LOAD_TIME_HERO_BANNER)) {
            $subject->getConfig()->addBodyClass('with-tiger-d195836fixloadtimeherobanner');
        }
        if($this->marketplaceCheckoutHelper->isEssendantToggleEnabled()) {
            $subject->getConfig()->addBodyClass('with-essendant-toggle');
        }
        if($this->toggleConfig->getToggleConfigValue(self::TIGER_D214669_ADOBE_LIVESEARCH_THUMBNAIL_CUT_OFF_FIX)) {
            $subject->getConfig()->addBodyClass('with-als-thumbnail-cut-off-fix');
        }
        if ($this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_E484727_COMMERCIAL_CATALOG_TYPE)) {
            $subject->getConfig()->addBodyClass('tech_titans_e_484727');
        }
        return [$response];
    }
}
