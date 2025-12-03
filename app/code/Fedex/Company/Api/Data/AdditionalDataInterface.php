<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface AdditionalDataInterface
 * @api
 * @since 100.0.2
 */
interface AdditionalDataInterface extends ExtensibleDataInterface
{
    /**
     * Get id
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set id
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get company id
     *
     * @return int|null
     */
    public function getCompanyId();

    /**
     * Set company id
     *
     * @param int $companyId
     * @return $this
     */
    public function setCompanyId($companyId);

    /**
     * Get store view id
     *
     * @return int|null
     */
    public function getStoreViewId();

    /**
     * Set store view id
     *
     * @param int $storeViewId
     * @return $this
     */
    public function setStoreViewId($storeViewId);

    /**
     * Get store id
     *
     * @return int|null
     */
    public function getStoreId();

    /**
     * Set store id
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId);

    /**
     * Get new store view id
     *
     * @return int|null
     */
    public function getNewStoreViewId();

    /**
     * Set new store view id
     *
     * @param int $newStoreViewId
     * @return $this
     */
    public function setNewStoreViewId($newStoreViewId);

    /**
     * Get new store id
     *
     * @return int|null
     */
    public function getNewStoreId();

    /**
     * Set new store id
     *
     * @param int $newStoreId
     * @return $this
     */
    public function setNewStoreId($newStoreId);

    /**
     * Get cc token
     *
     * @return string|null
     */
    public function getCcToken();

    /**
     * Set cc token
     *
     * @param string|null $ccToken
     * @return $this
     */
    public function setCcToken($ccToken);

    /**
     * Get cc data
     *
     * @return string|null
     */
    public function getCcData();

    /**
     * Set cc data
     *
     * @param string|null $ccData
     * @return $this
     */
    public function setCcData($ccData);

    /**
     * Get company payment options
     *
     * @return string|null
     */
    public function getCompanyPaymentOptions();

    /**
     * Set company payment options
     *
     * @param string|null $companyPaymentOptions
     * @return $this
     */
    public function setCompanyPaymentOptions($companyPaymentOptions);

    /**
     * Get creditcard options
     *
     * @return string|null
     */
    public function getCreditcardOptions();

    /**
     * Set creditcard options
     *
     * @param string|null $creditcardOptions
     * @return $this
     */
    public function setCreditcardOptions($creditcardOptions);

    /**
     * Get fedex account options
     *
     * @return string|null
     */
    public function getFedexAccountOptions();

    /**
     * Set fedex account options
     *
     * @param string|null $fedexAccountOptions
     * @return $this
     */
    public function setFedexAccountOptions($fedexAccountOptions);

    /**
     * Get default payment method
     *
     * @return string|null
     */
    public function getDefaultPaymentMethod();

    /**
     * Set default payment method
     *
     * @param string|null $defaultPaymentMethod
     * @return $this
     */
    public function setDefaultPaymentMethod($defaultPaymentMethod);

    /**
     * Get cc token expiry date time
     *
     * @return string|null
     */
    public function getCcTokenExpiryDateTime();

    /**
     * Set cc token expiry date time
     *
     * @param string|null $ccTokenExpiryDateTime
     * @return $this
     */
    public function setCcTokenExpiryDateTime($ccTokenExpiryDateTime);

    /**
     * Get is promo discount enable/disable
     *
     * @return bool|null
     */
    public function getIsPromoDiscountEnabled();

    /**
     * Set whether is promo discount enable/disable
     *
     * @param bool $isPromoDiscountEnabled
     * @return $this
     */
    public function setIsPromoDiscountEnabled($isPromoDiscountEnabled);

    /**
     * Get is account discount enable/disable
     *
     * @return bool|null
     */
    public function getIsAccountDiscountEnabled();

    /**
     * Set whether is account discount enable/disable
     *
     * @param bool $isAccountDiscountEnabled
     * @return $this
     */
    public function setIsAccountDiscountEnabled($isAccountDiscountEnabled);

    /**
     * Get is reorder enable/disable
     *
     * @return bool|null
     */
    public function getIsReorderEnabled();

    /**
     * Set whether is reorder enable/disable
     *
     * @param bool $isReorderEnabled
     * @return $this
     */
    public function setIsReorderEnabled($isReorderEnabled);

    /**
     * Get is terms and conditions
     *
     * @return bool|null
     */
    public function getTermsAndConditions();

    /**
     * Set whether is terms and conditions enable/disable
     *
     * @param bool $termsAndConditions
     * @return $this
     */
    public function setTermsAndConditions($termsAndConditions);

    /**
     * Get order notes
     *
     * @return string|null
     */
    public function getOrderNotes();

    /**
     * Set order notes
     *
     * @param string|null $orderNotes
     * @return $this
     */
    public function setOrderNotes($orderNotes);

    /**
     * Get is banner enable
     *
     * @return bool|null
     */
    public function getIsBannerEnable();

    /**
     * Set whether is banner enable/disable
     *
     * @param bool $isBannerEnable
     * @return $this
     */
    public function setIsBannerEnable($isBannerEnable);

    /**
     * Get banner title
     *
     * @return string|null
     */
    public function getBannerTitle();

    /**
     * Set banner title
     *
     * @param string|null $bannerTitle
     * @return $this
     */
    public function setBannerTitle($bannerTitle);

    /**
     * Get iconography
     *
     * @return string|null
     */
    public function getIconography();

    /**
     * Set iconography
     *
     * @param string|null $iconography
     * @return $this
     */
    public function setIconography($iconography);

    /**
     * Get cta text
     *
     * @return string|null
     */
    public function getCtaText();

    /**
     * Set cta text
     *
     * @param string|null $ctaText
     * @return $this
     */
    public function setCtaText($ctaText);

    /**
     * Get cta link
     *
     * @return string|null
     */
    public function getCtaLink();

    /**
     * Set cta link
     *
     * @param string|null $ctaLink
     * @return $this
     */
    public function setCtaLink($ctaLink);

    /**
     * Get is link open in new tab
     *
     * @return bool|null
     */
    public function getLinkOpenInNewTab();

    /**
     * Set whether is link open in new tab
     *
     * @param bool $linkOpenInNewTab
     * @return $this
     */
    public function setLinkOpenInNewTab($linkOpenInNewTab);

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Set description
     *
     * @param string|null $description
     * @return $this
     */
    public function setDescription($description);

    /**
     * Get is non editable payment method
     *
     * @return bool|null
     */
    public function getIsNonEditableCcPaymentMethod();

    /**
     * Set whether is NonEditableCcPaymentMethod
     *
     * @param bool $isNonEditableCcPaymentMethod
     * @return $this
     */
    public function setIsNonEditableCcPaymentMethod($isNonEditableCcPaymentMethod);

    public function getAllPrintProductsCmsBlockIdentifier();

    public function setAllPrintProductsCmsBlockIdentifier($allPrintProductsCmsBlockIdentifier);

    public function getHomepageCmsBlockIdentifier();

    public function setHomepageCmsBlockIdentifier($homepageCmsBlockIdentifier);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Fedex\Company\Api\Data\AdditionalDataExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Fedex\Company\Api\Data\AdditionalDataExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Fedex\Company\Api\Data\AdditionalDataExtensionInterface $extensionAttributes
    );
}
