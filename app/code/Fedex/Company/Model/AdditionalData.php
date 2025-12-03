<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Fedex\Company\Api\Data\AdditionalDataInterface;
use Fedex\Company\Model\ResourceModel\AdditionalData as AdditionalDataResourceModel;

/**
 * B-1250149 : Magento Admin UI changes to group all the Customer account details
 */
class AdditionalData extends AbstractExtensibleModel implements AdditionalDataInterface
{
    /**
     * Id field name
     */
    const ID = 'id';

    /**
     * Company id field name
     */
    const COMPANY_ID = 'company_id';

    /**
     * Credit card token field name
     */
    const CREDIT_CARD_TOKEN = 'cc_token';

    /**
     * Credit card data field name
     */
    const CREDIT_CARD_DATA = 'cc_data';

    /**
     * Store view id field name
     */
    const STORE_VIEW_ID = 'store_view_id';

    /**
     * store id field name
     */
    const STORE_ID = 'store_id';

    /**
     * New store id field name
     */
    const NEW_STORE_ID = 'new_store_id';

    /**
     * New store view id field name
     */
    const NEW_STORE_VIEW_ID = 'new_store_view_id';

    /**
     * company_payment_options field name
     */
    const COMPANY_PAYMENT_OPTIONS = 'company_payment_options';

    /**
     * creditcard_options field name
     */
    const CREDIT_CARD_OPTIONS = 'creditcard_options';

    /**
     * fedex_account_options field name
     */
    const FEDEX_ACCOUNT_OPTIONS = 'fedex_account_options';

    /**
     * default_payment_method field name
     */
    const DEFAULT_PAYMENT_METHOD = 'default_payment_method';

    /**
     * cc_token_expiry_date_time field name
     */
    const CC_TOKEN_EXPIRY_DATE_TIME = 'cc_token_expiry_date_time';

    /**
     * Is promo discount enabled field name
     */
    const IS_PROMO_DISCOUNT_ENABLED = 'is_promo_discount_enabled';

    /**
     * Is account discount enabled field name
     */
    const IS_ACCOUNT_DISCOUNT_ENABLED = 'is_account_discount_enabled';

    /**
     * ePro New Platform Order Creation field name
     */
    const EPRO_NEW_PLATFORM_ORDER_CREATION = 'epro_new_platform_order_creation';

    /**
     * Is reorder enabled field name
     */
    const IS_REORDER_ENABLED = 'is_reorder_enabled';

    /**
     * Is terms and conditions field name
     */
    const TERMS_AND_CONDITIONS = 'terms_and_conditions';

    /**
     * Is order notes field name
     */
    const ORDER_NOTES = 'order_notes';

    /**
     * Is banner enable field name
     */
    const IS_BANNER_ENABLE = 'is_banner_enable';

    /**
     * Is banner title field name
     */
    const BANNER_TITLE = 'banner_title';

    /**
     * Is iconography field name
     */
    const ICONOGRAPHY = 'iconography';

    /**
     * Is cta text field name
     */
    const CTA_TEXT = 'cta_text';

    /**
     * Cta link field name
     */
    const CTA_LINK = 'cta_link';

    /**
     * Is link open in new tab field name
     */
    const LINK_OPEN_IN_NEW_TAB = 'link_open_in_new_tab';

    /**
     * Description tab field name
     */
    const DESCRIPTION = 'description';

    /**
     * Is non editable payment method
     */
    const IS_NON_EDITABLE_CC_PAYMENT_METHOD = 'is_non_editable_cc_payment_method';

    const HOMEPAGE_CMS_BLOCK_IDENTIFIER = 'homepage_cms_block_identifier';


    public const ALL_PRINT_PRODUCTS_CMS_BLOCK_IDENTIFIER = 'all_print_products_cms_block_identifier';

    /**
     * Approval workflow enable/disable
     */
    const IS_B2B_ORDER_APPROVAL_ENABLED = 'is_b2b_order_approval_enabled';


    /**
     * Initialize model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(AdditionalDataResourceModel::class);
        parent::_construct();
    }

    /**
     * Get id
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * Set id
     *
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get company id
     *
     * @return int|null
     */
    public function getCompanyId()
    {
        return $this->getData(self::COMPANY_ID);
    }

    /**
     * Set company id
     *
     * @param int $companyId
     * @return $this
     */
    public function setCompanyId($companyId)
    {
        return $this->setData(self::COMPANY_ID, $companyId);
    }

    /**
     * Get store view id
     *
     * @return int|null
     */
    public function getStoreViewId()
    {
        return $this->getData(self::STORE_VIEW_ID);
    }

    /**
     * Set store view id
     *
     * @param int $storeViewId
     * @return $this
     */
    public function setStoreViewId($storeViewId)
    {
        return $this->setData(self::STORE_VIEW_ID, $storeViewId);
    }

    /**
     * Get store id
     *
     * @return int|null
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * Set store id
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * Get new store view id
     *
     * @return int|null
     */
    public function getNewStoreViewId()
    {
        return $this->getData(self::NEW_STORE_VIEW_ID);
    }

    /**
     * Set new store view id
     *
     * @param int $newStoreViewId
     * @return $this
     */
    public function setNewStoreViewId($newStoreViewId)
    {
        return $this->setData(self::NEW_STORE_VIEW_ID, $newStoreViewId);
    }

    /**
     * Get new store id
     *
     * @return int|null
     */
    public function getNewStoreId()
    {
        return $this->getData(self::NEW_STORE_ID);
    }

    /**
     * Set new store id
     *
     * @param int $newStoreId
     * @return $this
     */
    public function setNewStoreId($newStoreId)
    {
        return $this->setData(self::NEW_STORE_ID, $newStoreId);
    }

    /**
     * Get cc token
     *
     * @return string|null
     */
    public function getCcToken()
    {
        return $this->getData(self::CREDIT_CARD_TOKEN);
    }

    /**
     * Set cc token
     *
     * @param string|null $ccToken
     * @return $this
     */
    public function setCcToken($ccToken)
    {
        return $this->setData(self::CREDIT_CARD_TOKEN, $ccToken);
    }

    /**
     * Get cc data
     *
     * @return string|null
     */
    public function getCcData()
    {
        return $this->getData(self::CREDIT_CARD_DATA);
    }

    /**
     * Set cc data
     *
     * @param string|null $ccData
     * @return $this
     */
    public function setCcData($ccData)
    {
        return $this->setData(self::CREDIT_CARD_DATA, $ccData);
    }

    /**
     * Get company payment options
     *
     * @return string|null
     */
    public function getCompanyPaymentOptions()
    {
        return $this->getData(self::COMPANY_PAYMENT_OPTIONS);
    }

    /**
     * Set company payment options
     *
     * @param string|null $companyPaymentOptions
     * @return $this
     */
    public function setCompanyPaymentOptions($companyPaymentOptions)
    {
        return $this->setData(self::COMPANY_PAYMENT_OPTIONS, $companyPaymentOptions);
    }

    /**
     * Get creditcard options
     *
     * @return string|null
     */
    public function getCreditcardOptions()
    {
        return $this->getData(self::CREDIT_CARD_OPTIONS);
    }

    /**
     * Set creditcard options
     *
     * @param string|null $creditcardOptions
     * @return $this
     */
    public function setCreditcardOptions($creditcardOptions)
    {
        return $this->setData(self::CREDIT_CARD_OPTIONS, $creditcardOptions);
    }

    /**
     * Get fedex account options
     *
     * @return string|null
     */
    public function getFedexAccountOptions()
    {
        return $this->getData(self::FEDEX_ACCOUNT_OPTIONS);
    }

    /**
     * Set fedex account options
     *
     * @param string|null $fedexAccountOptions
     * @return $this
     */
    public function setFedexAccountOptions($fedexAccountOptions)
    {
        return $this->setData(self::FEDEX_ACCOUNT_OPTIONS, $fedexAccountOptions);
    }

    /**
     * Get cc token expiry date time
     *
     * @return string|null
     */
    public function getCcTokenExpiryDateTime()
    {
        return $this->getData(self::CC_TOKEN_EXPIRY_DATE_TIME);
    }

    /**
     * Set cc token expiry date time
     *
     * @param string|null $ccTokenExpiryDateTime
     * @return $this
     */
    public function setCcTokenExpiryDateTime($ccTokenExpiryDateTime)
    {
        return $this->setData(self::CC_TOKEN_EXPIRY_DATE_TIME, $ccTokenExpiryDateTime);
    }

    /**
     * Get default payment method
     *
     * @return string|null
     */
    public function getDefaultPaymentMethod()
    {
        return $this->getData(self::DEFAULT_PAYMENT_METHOD);
    }

    /**
     * Set default payment method
     *
     * @param string|null $defaultPaymentMethod
     * @return $this
     */
    public function setDefaultPaymentMethod($defaultPaymentMethod)
    {
        return $this->setData(self::DEFAULT_PAYMENT_METHOD, $defaultPaymentMethod);
    }

    /**
     * Get is promo discount enable/disable
     *
     * @return bool|null
     */
    public function getIsPromoDiscountEnabled()
    {
        return $this->getData(self::IS_PROMO_DISCOUNT_ENABLED);
    }

    /**
     * Set whether is promo discount enable/disable
     *
     * @param bool $isPromoDiscountEnabled
     * @return $this
     */
    public function setIsPromoDiscountEnabled($isPromoDiscountEnabled)
    {
        return $this->setData(self::IS_PROMO_DISCOUNT_ENABLED, $isPromoDiscountEnabled);
    }

    /**
     * Get is account discount enable/disable
     *
     * @return bool|null
     */
    public function getIsAccountDiscountEnabled()
    {
        return $this->getData(self::IS_ACCOUNT_DISCOUNT_ENABLED);
    }

    /**
     * Set whether is account discount enable/disable
     *
     * @param bool $isAccountDiscountEnabled
     * @return $this
     */
    public function setIsAccountDiscountEnabled($isAccountDiscountEnabled)
    {
        return $this->setData(self::IS_ACCOUNT_DISCOUNT_ENABLED, $isAccountDiscountEnabled);
    }

    /**
     * Get ePro New Platform Order Creation enable/disable
     *
     * @return bool|null
     */
    public function getEproNewPlatformOrderCreation()
    {
        return $this->getData(self::EPRO_NEW_PLATFORM_ORDER_CREATION);
    }

    /**
     * Set whether ePro New Platform Order Creation enable/disable
     *
     * @param bool $isAccountDiscountEnabled
     * @return $this
     */
    public function setEproNewPlatformOrderCreation($isAccountDiscountEnabled)
    {
        return $this->setData(self::EPRO_NEW_PLATFORM_ORDER_CREATION, $isAccountDiscountEnabled);
    }

    /**
     * Get order Approval workflow enable/disable
     *
     * @return bool|null
     */
    public function getIsApprovalWorkflowEnabled()
    {
        return $this->getData(self::IS_B2B_ORDER_APPROVAL_ENABLED);
    }

    /**
     * Set whether order Approval workflow enable/disable
     *
     * @param bool $isApprovalWorkflowEnabled
     * @return $this
     */
    public function setIsApprovalWorkflowEnabled($isApprovalWorkflowEnabled)
    {
        return $this->setData(self::IS_B2B_ORDER_APPROVAL_ENABLED, $isApprovalWorkflowEnabled);
    }

    /**
     * Get is reorder enable/disable
     *
     * @return bool|null
     */
    public function getIsReorderEnabled()
    {
        return $this->getData(self::IS_REORDER_ENABLED);
    }

    /**
     * Set whether is reorder enable/disable
     *
     * @param bool $isReorderEnabled
     * @return $this
     */
    public function setIsReorderEnabled($isReorderEnabled)
    {
        return $this->setData(self::IS_REORDER_ENABLED, $isReorderEnabled);
    }

    /**
     * Get is terms and conditions
     *
     * @return bool|null
     */
    public function getTermsAndConditions()
    {
        return $this->getData(self::TERMS_AND_CONDITIONS);
    }

    /**
     * Set whether is terms and conditions enable/disable
     *
     * @param bool $termsAndConditions
     * @return $this
     */
    public function setTermsAndConditions($termsAndConditions)
    {
        return $this->setData(self::TERMS_AND_CONDITIONS, $termsAndConditions);
    }

    /**
     * Get order notes
     *
     * @return string|null
     */
    public function getOrderNotes()
    {
        return $this->getData(self::ORDER_NOTES);
    }

    /**
     * Set order notes
     *
     * @param string $orderNotes
     * @return $this
     */
    public function setOrderNotes($orderNotes)
    {
        return $this->setData(self::ORDER_NOTES, $orderNotes);
    }

    /**
     * Get is banner enable
     *
     * @return bool|null
     */
    public function getIsBannerEnable()
    {
        return $this->getData(self::IS_BANNER_ENABLE);
    }

    /**
     * Set whether is banner enable/disable
     *
     * @param bool $isBannerEnable
     * @return $this
     */
    public function setIsBannerEnable($isBannerEnable)
    {
        return $this->setData(self::IS_BANNER_ENABLE, $isBannerEnable);
    }

    /**
     * Get banner title
     *
     * @return string|null
     */
    public function getBannerTitle()
    {
        return $this->getData(self::BANNER_TITLE);
    }

    /**
     * Set banner title
     *
     * @param string $bannerTitle
     * @return $this
     */
    public function setBannerTitle($bannerTitle)
    {
        return $this->setData(self::BANNER_TITLE, $bannerTitle);
    }

    /**
     * Get iconography
     *
     * @return string|null
     */
    public function getIconography()
    {
        return $this->getData(self::ICONOGRAPHY);
    }

    /**
     * Set iconography
     *
     * @param string $iconography
     * @return $this
     */
    public function setIconography($iconography)
    {
        return $this->setData(self::ICONOGRAPHY, $iconography);
    }

    /**
     * Get cta text
     *
     * @return string|null
     */
    public function getCtaText()
    {
        return $this->getData(self::CTA_TEXT);
    }

    /**
     * Set cta text
     *
     * @param string $ctaText
     * @return $this
     */
    public function setCtaText($ctaText)
    {
        return $this->setData(self::CTA_TEXT, $ctaText);
    }

    /**
     * Get cta link
     *
     * @return string|null
     */
    public function getCtaLink()
    {
        return $this->getData(self::CTA_LINK);
    }

    /**
     * Set cta link
     *
     * @param string $ctaLink
     * @return $this
     */
    public function setCtaLink($ctaLink)
    {
        return $this->setData(self::CTA_LINK, $ctaLink);
    }

    /**
     * Get is link open in new tab
     *
     * @return bool|null
     */
    public function getLinkOpenInNewTab()
    {
        return $this->getData(self::LINK_OPEN_IN_NEW_TAB);
    }

    /**
     * Set whether is link open in new tab
     *
     * @param bool $linkOpenInNewTab
     * @return $this
     */
    public function setLinkOpenInNewTab($linkOpenInNewTab)
    {
        return $this->setData(self::LINK_OPEN_IN_NEW_TAB, $linkOpenInNewTab);
    }

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * Set description
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * Get is non editable credit card payment method
     *
     * @return bool|null
     */
    public function getIsNonEditableCcPaymentMethod()
    {
        return $this->getData(self::IS_NON_EDITABLE_CC_PAYMENT_METHOD);
    }

    /**
     * Set whether is NonEditableCcPaymentMethod
     *
     * @param bool $isNonEditableCcPaymentMethod
     * @return $this
     */
    public function setIsNonEditableCcPaymentMethod($isNonEditableCcPaymentMethod)
    {
        return $this->setData(self::IS_NON_EDITABLE_CC_PAYMENT_METHOD, $isNonEditableCcPaymentMethod);
    }

    /**
     * Get is all print products cms block identifier
     *
     * @return bool|null
     */
    public function getAllPrintProductsCmsBlockIdentifier()
    {
        return $this->getData(self::ALL_PRINT_PRODUCTS_CMS_BLOCK_IDENTIFIER);
    }

    /**
     * Set allPrintProductsCmsBlockIdentifier Value
     *
     * @param bool $allPrintProductsCmsBlockIdentifier
     * @return $this
     */
    public function setAllPrintProductsCmsBlockIdentifier($allPrintProductsCmsBlockIdentifier)
    {
        return $this->setData(self::ALL_PRINT_PRODUCTS_CMS_BLOCK_IDENTIFIER, $allPrintProductsCmsBlockIdentifier);
    }
    /**
     * Get is homepage cms block identifier
     *
     * @return bool|null
     */
    public function getHomepageCmsBlockIdentifier()
    {
        return $this->getData(self::HOMEPAGE_CMS_BLOCK_IDENTIFIER);
    }

    /**
     * Set homepageCmsBlockIdentifier Value
     *
     * @param bool $homepageCmsBlockIdentifier
     * @return $this
     */
    public function setHomepageCmsBlockIdentifier($homepageCmsBlockIdentifier)
    {
        return $this->setData(self::HOMEPAGE_CMS_BLOCK_IDENTIFIER, $homepageCmsBlockIdentifier);
    }


    /**
     * {@inheritdoc}
     *
     * @return \Fedex\Company\Api\Data\AdditionalDataExtensionInterface|null
     * @codeCoverageIgnore
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * {@inheritdoc}
     *
     * @param \Fedex\Company\Api\Data\AdditionalDataExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Fedex\Company\Api\Data\AdditionalDataExtensionInterface $extensionAttributes
        )
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
