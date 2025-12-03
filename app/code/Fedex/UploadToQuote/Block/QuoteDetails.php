<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Block;

use Magento\Company\Model\CompanyRepository;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\CatalogDocumentUserSettings\Helper\Data as CatalogDocumentUserSettingsHelper;

/**
 * QuoteDetails Block class
 */
class QuoteDetails extends Template
{
    public const RETAILSTORECODE = 'main_website_store';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     * @param CompanyRepository $companyRepository
     * @param UploadToQuoteViewModel $uploadToQuoteViewModel
     * @param AdminConfigHelper $adminConfigHelper
     * @param DeliveryHelper $deliveryHelper
     * @param CatalogDocumentUserSettingsHelper $catalogDocumentUserSettingsHelper
     */
    public function __construct(
        Context $context,
        protected StoreManagerInterface $storeManager,
        protected CustomerSession $customerSession,
        protected CompanyRepository $companyRepository,
        protected UploadToQuoteViewModel $uploadToQuoteViewModel,
        protected AdminConfigHelper $adminConfigHelper,
        private DeliveryHelper $deliveryHelper,
        private CatalogDocumentUserSettingsHelper $catalogDocumentUserSettingsHelper
    ) {
        parent::__construct($context);
    }

    /**
     * Get next step content
     *
     * @return string
     */
    public function getNextStepContent()
    {
        $companyData = $this->customerSession->getOndemandCompanyInfo();
        $companyId = (isset($companyData['company_id']) && !empty($companyData['company_id']))
        ? trim($companyData['company_id']) : null;

        $storeId = $this->storeManager->getStore()->getId();
        if ($storeId && $companyId && $this->companyRepository->get((int) $companyId)
            ->getuploadToQuoteNextStepContent()) {
            return $this->companyRepository->get((int) $companyId)->getuploadToQuoteNextStepContent();
        }
    }

    /**
     * Get next step content toggle value
     *
     * @return bool
     */
    public function getAllowNextStepContent()
    {
        $companyData = $this->customerSession->getOndemandCompanyInfo();
        $companyId = (isset($companyData['company_id']) && !empty($companyData['company_id']))
        ? trim($companyData['company_id']) : null;
        $storeId = $this->storeManager->getStore()->getId();
        if ($storeId && $this->storeManager->getGroup()->getCode() == self::RETAILSTORECODE && !$companyId) {
            return true;
        }
        if (!$companyId) {
            return false;
        }
        $isAllowNextStep = $this->companyRepository->get((int) $companyId)->getAllowNextStepContent();
        $isUploadToQuoteEnabled = $this->uploadToQuoteViewModel->isUploadToQuoteEnable();

        if ($storeId && $companyId && $isAllowNextStep && $isUploadToQuoteEnabled) {

            return $isAllowNextStep;
        }
    }

    /**
     * Get SI Items for review popup.
     *
     * @return array
     */
    public function getSiItems()
    {
        return $this->customerSession->getSiItems() ?? [];
    }

    /**
     * Get quote request change message
     *
     * @return string
     */
    public function getRequestChangeMessage()
    {
        return $this->adminConfigHelper->getUploadToQuoteConfigValue('request_change_modal_message');
    }

    /**
     * Get quote request change Cancel CTA Label
     *
     * @return string
     */
    public function getRequestChangeCancelCTALabel()
    {
        return $this->adminConfigHelper->getUploadToQuoteConfigValue('request_change_cancel_cta_label');
    }

    /**
     * Get quote request change CTA Label
     *
     * @return string
     */
    public function getRequestChangeCTALabel()
    {
        return $this->adminConfigHelper->getUploadToQuoteConfigValue('request_change_cta_label');
    }

    /**
     * Is Epro Customer
     *
     * @return boolean
     */
    public function isEproCustomer()
    {
        return $this->deliveryHelper->isEproCustomer();
    }

    /**
     * Get Company Configuration
     *
     * @return object
     */
    public function getCompanyConfiguration()
    {
        return $this->catalogDocumentUserSettingsHelper->getCompanyConfiguration();
    }
}
