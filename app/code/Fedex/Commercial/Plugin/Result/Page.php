<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Commercial\Plugin\Result;

use Fedex\Commercial\Helper\CommercialHelper;
use Fedex\CustomerDetails\Helper\Data;
use Fedex\CustomizedMegamenu\Helper\Data as DataHelper;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Request\Http;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Magento\Framework\View\Element\Context;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Fedex\LiveSearch\Model\SharedCatalogSkip;
use Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;

/**
 * Page Plugin
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Page
{
    /**
     * Page construct
     *
     * @param Context $context
     * @param Data $helperData
     * @param DeliveryHelper $deliveryHelper
     * @param DataHelper $dataHelper
     * @param SdeHelper $sdeHelper
     * @param CommercialHelper $commercialHelper
     * @param SelfReg $selfregHelper
     * @param Http $request
     * @param CatalogMvp $catalogMvp
     * @param UploadToQuoteViewModel $uploadToQuoteViewModel
     * @param SharedCatalogSkip $sharedCatalogSkip
     * @param OrderApprovalViewModel $orderApprovalViewModel
     * @param FuseBidViewModel $fuseBidViewModel
     */
    public function __construct(
        private Context $context,
        private Data $helperData,
        private DeliveryHelper $deliveryHelper,
        private DataHelper $dataHelper,
        protected SdeHelper $sdeHelper,
        protected CommercialHelper $commercialHelper,
        private SelfReg $selfregHelper,
        private Http $request,
        private CatalogMvp $catalogMvp,
        protected UploadToQuoteViewModel $uploadToQuoteViewModel,
        private SharedCatalogSkip $sharedCatalogSkip,
        private OrderApprovalViewModel $orderApprovalViewModel,
        private FuseBidViewModel $fuseBidViewModel
    ) {
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
        if ($this->commercialHelper->isGlobalCommercialCustomer() && (!$this->sdeHelper->getIsSdeStore())) {
            $subject->getConfig()->addBodyClass('commercial-epro-store');
        }
        if ($this->deliveryHelper->getToggleConfigurationValue('maze_geeks_catalog_mvp_breakpoints_and_ada') && $this->catalogMvp->isMvpCatalogEnabledForCompany()) {
            $subject->getConfig()->addBodyClass('catalog-mvp-break-points');
        }
        if ($this->deliveryHelper->getToggleConfigurationValue('maegeeks_pobox_toggle')) {
            $subject->getConfig()->addBodyClass('maegeeks_pobox_toggle');
        }
        $action = $this->request->getFullActionName();
        if ($this->commercialHelper->isGlobalCommercialCustomer()  && $action != 'selfreg_landing_index') {
            $subject->getConfig()->addBodyClass('commercial-store-home');
        }

        $this->addPaginationShowClass($subject, $action);

        if ($this->commercialHelper->isCommercialReorderEnable()) {
            $subject->getConfig()->addBodyClass('epro-order-history-reorder');
        }
        /* B-1339544-Implement UI screen for company users */
        $moduleName = $this->request->getModuleName();
        if ($this->selfregHelper->isSelfRegCustomer() || $this->selfregHelper->isSelfRegCustomerAdmin()
            && ($action == 'company_users_index' || $action == 'company_index_index'
                && $moduleName == 'company')) {
            $subject->getConfig()->addBodyClass('epro-company-user');
        }
        elseif($this->deliveryHelper->getToggleConfigurationValue('change_customer_roles_and_permissions')
           && $this->deliveryHelper->checkPermission('manage_users')
           && ($action == 'company_users_index' || $action == 'company_index_index'
           && $moduleName == 'company')){
            $subject->getConfig()->addBodyClass('epro-company-user');
           }

        /* B-1405455-Fix ADA issues for self-reg header Adding class in body only for self Reg store */
        if ($this->selfregHelper->isSelfRegCompany()) {
            $subject->getConfig()->addBodyClass('selfreg-store');
        }
        if ($this->deliveryHelper->isCustomerAdminUser()) {
            $subject->getConfig()->addBodyClass('commercial-admin');
        }
        if($this->selfregHelper->isSelfRegCustomerWithFclEnabled() || $this->sdeHelper->getIsRequestFromSdeStoreFclLogin()) {
            $subject->getConfig()->addBodyClass('selfreg-store-fcl');
        }

        $subject->getConfig()->addBodyClass('discount-breakdown');

        $subject->getConfig()->addBodyClass('mobile-mega-menu');

        $subject->getConfig()->addBodyClass('catalog-update-enabled');

        // B-1569415
        if ($this->deliveryHelper->isCommercialCustomer() &&
            $this->catalogMvp->isMvpSharedCatalogEnable()
        ) {
            if ($action == "catalog_category_view") {
                $isPrintProductCategory = $this->catalogMvp->checkPrintCategory();
                if (!$isPrintProductCategory) {
                    $subject->getConfig()->addBodyClass('catalog-mvp-shared-catalog');
                }
                if ($isPrintProductCategory) {
                    $subject->getConfig()->addBodyClass('categorypath-b2b-print-products');
                    $subject->getConfig()->addBodyClass('category-b2b-print-products');
                }
            }

            if ($this->selfregHelper->isSelfRegCustomerAdmin() || $this->catalogMvp->isSharedCatalogPermissionEnabled()) {
                $subject->getConfig()->addBodyClass('catalog-mvp-customer-admin');
            } else if ($action == 'catalog_category_view') {
                $subject->getConfig()->addBodyClass('catalog-mvp-customer-admin');
                //checked condition for Livesearch
                if ($this->sharedCatalogSkip->checkIsSharedCatalogPage() && !$this->catalogMvp->isSharedCatalogPermissionEnabled()) {
                    if($this->catalogMvp->getMergedSharedCatalogFilesToggle() && $this->catalogMvp->isMvpSharedCatalogEnable()) {
                        $subject->getConfig()->addBodyClass('catalog-mvp-customer-merged-user');
                    } else {
                        $subject->getConfig()->addBodyClass('catalog-mvp-customer-user');
                    }
                }
            }
        }

        /* B-1792786-Cart summary design for all breakpoint-UploadToQuote feature */
        if ($this->uploadToQuoteViewModel->isUploadToQuoteEnable() || $this->deliveryHelper->isEproCustomer()) {
            $subject->getConfig()->addBodyClass('upload-to-quote');
        }

        /* E-345638-Add class in header for character limit for all stores*/
        if ($this->deliveryHelper->getToggleConfigurationValue('character_limit_toggle')){
            $subject->getConfig()->addBodyClass('character_limit_hundred');
        }

        /* E443304 -Add class in header for Stop redirect to cart page after Add to Cart Catalog for Commercial*/
        if ($this->deliveryHelper->getToggleConfigurationValue('E443304_stop_redirect_mvp_addtocart') && $this->catalogMvp->isMvpCatalogEnabledForCompany()) {
            $subject->getConfig()->addBodyClass('add-to-cart-banner');
        }

        /* D-169822-EPRO_Print quote option is not displaying in My Quotes*/
        if ($this->deliveryHelper->getToggleConfigurationValue('xmen_D169822_fix') &&
        $this->deliveryHelper->isEproCustomer()) {
            $subject->getConfig()->addBodyClass('epro-quote-view-print-option');
        }

        if ($this->orderApprovalViewModel->isOrderApprovalB2bEnabled()) {
            $subject->getConfig()->addBodyClass('commercial-order-approval');
        }

        /* Add class for FuseBidding quote feature for Retail */
        if ($this->fuseBidViewModel->isFuseBidToggleEnabled()) {
            $subject->getConfig()->addBodyClass('fuse-bidding-quote');
        }

        return [$response];
    }

    /**
     * Add Pagination show class
     * @param object $subject
     * @param string $action
     */
    private function addPaginationShowClass($subject, $action)
    {
        $isPaginationShowToggleEnabled = (bool) $this->deliveryHelper->getToggleConfigurationValue('tech_titans_d_174909');

        if ($isPaginationShowToggleEnabled &&
            $this->deliveryHelper->isCommercialCustomer() &&
        (!$this->catalogMvp->isMvpSharedCatalogEnable()) &&
            $action == "catalog_category_view"
        ) {
            $isPrintProductCategory = $this->catalogMvp->checkPrintCategory();
            if (!$isPrintProductCategory) {
                $subject->getConfig()->addBodyClass('pagination-show');
            }
        }
    }
}
