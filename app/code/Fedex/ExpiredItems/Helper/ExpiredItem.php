<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\ExpiredItems\Helper;

use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\ExpiredItems\Model\Config;
use Fedex\ExpiredItems\Model\ConfigProvider;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\MarketplacePunchout\Model\Config\Marketplace;
use Fedex\MarketplacePunchout\Model\ExpiredProducts;
use Fedex\ProductBundle\Api\ConfigInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item;

/**
 * ExpiredItem Helper
 */
class ExpiredItem extends AbstractHelper
{
    public const EXPIRED_CODE = "RATEREQUEST.PRODUCTS.INVALID";

    public const CATALOG_ITEM_EXPIRED_CODE = "PRODUCTS.CATALOGREFERENCE.INVALID";
    public const TOGGLE_D178760_PRODUCT_ID_MISSING = "tigerteam_d178760_product_id_missing_property";

     /**
     * @param Context $context
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param CheckoutSession $checkoutSession
     * @param TimezoneInterface $timezone
     * @param FXORate $fxoHelper
     * @param ConfigProvider $configProvider
     * @param HttpContext $httpContext
     * @param CustomerSession $customerSession
     * @param ExpiredProducts $expiredProducts
     * @param Marketplace $config
     * @param AuthHelper $authHelper
     * @param FXORateQuote $fxoRateQuote
     * @param ToggleConfig $toggleConfig
     * @param ConfigInterface $productBundleConfig
     * @param Config $expiredConfig
     */
    public function __construct(
        Context $context,
        protected CookieManagerInterface $cookieManager,
        protected CookieMetadataFactory $cookieMetadataFactory,
        protected CheckoutSession $checkoutSession,
        protected TimezoneInterface $timezone,
        protected FXORate $fxoHelper,
        protected ConfigProvider $configProvider,
        private HttpContext $httpContext,
        protected CustomerSession $customerSession,
        protected ExpiredProducts $expiredProducts,
        protected Marketplace $config,
        protected AuthHelper $authHelper,
        private readonly FXORateQuote $fxoRateQuote,
        private readonly ToggleConfig $toggleConfig,
        private readonly ConfigInterface $productBundleConfig,
        private Config $expiredConfig
    ) {
        parent::__construct($context);
    }

    /**
     * To clear the cookie for modal popup
     *
     * @return void
     */
    public function clearExpiredModalCookie()
    {
        $metadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setDomain(".fedex.com")
                ->setPath("/")
                ->setHttpOnly(false);

        $this->cookieManager->deleteCookie("expired_expiry_modal_closed", $metadata);
    }

    /**
     * To check item is expirying soon
     *
     * @param $itemId
     * @param $item
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isItemExpiringSoon($itemId, $item = null): bool
    {
        $quote = $this->checkoutSession->getQuote();
        $quoteItem = $item ?: $quote->getItemById($itemId);

        if ($quoteItem && $quoteItem->getId() && $this->authHelper->isLoggedIn()) {
            if ($this->isNonCustomizableProduct($quoteItem)) {
                return false;
            }
            $date = $this->timezone->date($quoteItem->getCreatedAt());
            if ($quoteItem->getMiraklOfferId() &&
                isset(json_decode($quoteItem->getAdditionalData() ?? '')->expire_soon)) {
                $date->modify("+" . json_decode($quoteItem->getAdditionalData())->expire_soon . " day");
            } else {
                $date->modify("+" . $this->configProvider->getExpiryThresholdTime() . " day");
                $date->modify("-" . $this->configProvider->getExpiryTime() . " day");
            }
            $itemExpiryDate = $this->timezone->convertConfigTimeToUtc($date);
            $currentDate = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
            if ($currentDate >= $itemExpiryDate) {
                return true;
            }
        }

        return false;
    }

    /**
     * To check if bundle item is expiring soon
     *
     * @param Item|CartItemInterface $bundleItem
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isBundleItemExpiringSoon($bundleItem): bool
    {
        if(
            $this->productBundleConfig->isTigerE468338ToggleEnabled()
            && $this->authHelper->isLoggedIn()
            && $bundleItem
            && $bundleItem->getId()
            && $bundleItem->getChildren()
        ) {
            foreach ($bundleItem->getChildren() as $childQuoteItem) {
                if ($this->isItemExpiringSoon($childQuoteItem->getId(), $childQuoteItem)) {
                    return true;
                }

            }
        }
        return false;
    }

    /**
     * To check item is expired
     *
     * @param Object $item
     * @return bool
     */
    public function isItemExpired($item)
    {
        if ($this->isNonCustomizableProduct($item)) {
            return false;
        }
        if (!isset(json_decode($item->getAdditionalData() ?? '')->expire)) {
            return false;
        }
        $date = $this->timezone->date($item->getCreatedAt());
        $date->modify("+".json_decode($item->getAdditionalData())->expire." day");
        $itemExpiryDate = $this->timezone->convertConfigTimeToUtc($date);
        $currentDate = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
        return $currentDate >= $itemExpiryDate;
    }

    /**
     * Call rate API to get expired instance ids
     *
     * @param object $quote
     * @return void
     * @throws GraphQlFujitsuResponseException
     */
    public function callRateApiGetExpiredInstanceIds($quote)
    {
        $d188760Toggle = (bool) $this->toggleConfig->getToggleConfigValue(self::TOGGLE_D178760_PRODUCT_ID_MISSING);
        if ($d188760Toggle &&
            !$this->fxoHelper->isEproCustomer()) {
            $response =$this->fxoRateQuote->getFXORateQuote($quote);
        }else{
            $response = $this->fxoHelper->getFXORate($quote, null, true);
        }
        $this->setExpiredItemIdsCustomerSession($quote, $response);
        $this->setExpireItemIdsInSessionByIds($this->expiredProducts->execute());
    }

    /**
     * Set expired item ids in customer session
     *
     * @param object $quote
     * @param array $rateApiResponse
     * @return void
     */
    public function setExpiredItemIdsCustomerSession($quote, $rateApiResponse)
    {

        if (isset($rateApiResponse['errors'][0]['code'])
        && $rateApiResponse['errors'][0]['code'] == self::CATALOG_ITEM_EXPIRED_CODE) {
            $this->setExpiredItemIdsInSession($rateApiResponse);
            $response = $this->fxoHelper->getFXORate($quote, null, true);

            if (isset($response['errors'][0]['code'])
            && $response['errors'][0]['code'] == self::EXPIRED_CODE) {
                $this->setExpiredItemIdsInSession($response);
            }
        } elseif (isset($rateApiResponse['errors'][0]['code'])
        && $rateApiResponse['errors'][0]['code'] == self::EXPIRED_CODE
        ) {
            $this->setExpiredItemIdsInSession($rateApiResponse);
        } else {
            $this->customerSession->unsExpiredItemIds();
        }
    }

    /**
     * Set expired item ids in customer session
     *
     * @param array $rateApiResponse
     * @return void
     */
    public function setExpiredItemIdsInSession($rateApiResponse)
    {
        $message = explode(":", $rateApiResponse['errors'][0]['message']);
        $expiredIds = array_map('trim', explode(",", end($message)));
        $this->setExpireItemIdsInSessionByIds($expiredIds);
        if(isset($rateApiResponse['transactionId'])) {
            $this->customerSession->setExpiredItemTransactionId('Transaction ID:' . $rateApiResponse['transactionId']);
        }
    }

    /**
     * @param $expiredIds
     * @return void
     */
    public function setExpireItemIdsInSessionByIds($expiredIds): void
    {
        $previoustExpired = $this->customerSession->getExpiredItemIds()
            ? $this->customerSession->getExpiredItemIds() : [];
        $this->customerSession->setExpiredItemIds(array_merge($previoustExpired, $expiredIds));
    }

    /**
     * Set expired item message in customer session
     *
     * @return void
     */
    public function setExpiredItemMessageCustomerSession()
    {
        $this->customerSession->setExpiredMessage($this->configProvider->getMiniCartExpiredMessage());
        $this->customerSession->setExpiryMessage($this->configProvider->getMiniCartExpiryMessage());
    }

    /**
     * To get the expired instance ids
     *
     * @return array
     */
    public function getExpiredInstanceIds()
    {
        return $this->customerSession->getExpiredItemIds();
    }

    /**
     * To get the expired transaction ID
     *
     * @return string
     */
    public function getExpiredInstanceIdsTransactionID()
    {
        return $this->customerSession->getExpiredItemTransactionId();
    }

    /**
     * Check if the product is non-customizable product (CBB Sample Pack / Essendant)
     * @param Item $quoteItem
     * @return bool
     */
    private function isNonCustomizableProduct(Item $quoteItem): bool
    {
        if (!$this->expiredConfig->isIncorrectCartExpiryMassageToggleEnabled()) {
            return false;
        }

        if (!$quoteItem->getMiraklOfferId()) {
            return false;
        }

        $additionalData = $quoteItem->getAdditionalData();

        if (is_null($additionalData)) {
            return false;
        }

        $additionalInfo = json_decode($additionalData);
        if (isset($additionalInfo->punchout_enabled) && (bool)$additionalInfo->punchout_enabled === false) {
            return true;
        }

        return false;
    }
}
