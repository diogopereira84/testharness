<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Model;

use Fedex\MarketplaceCheckout\Model\Config\MarketplaceConfigProvider;
use Fedex\MarketplaceProduct\Api\Data\ShopInterface;
use Fedex\MarketplaceProduct\Model\Shop\AdditionalInfo;
use Fedex\MarketplaceProduct\Model\Shop\AdditionalInfoFactory;
use Magento\Framework\Data\Collection\AbstractDb as AbstractDbCollection;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Framework\UrlInterface;
use Mirakl\Core\Model\Shop as MiraklShop;

class Shop extends MiraklShop implements ShopInterface
{
    private const ADDITIONAL_FIELD_VALUES       = 'additional_field_values';
    private const SELLER_ALT_NAME               = 'seller-alt-name';
    private const SELLER_TOOLTIP                = 'tooltip';
    private const SELLER_CART_QUANTITY_TOOLTIP  = 'cart-quantity-tooltip';
    private const SELLER_CART_EXPIRE            = 'expiry-configuration';
    private const SELLER_CART_EXPIRE_SOON       = 'expires-soon-configuration';
    private const SELLER_PACKAGE_API_ENDPOINT   = 'package-api-endpoint';
    public const DEFAULT_SELLER_ALT_NAME        = 'Marketplace Seller';
    private const SHIPPING_RATE_OPTIONS         = 'shipping-rate-options';
    private const DEFAULT_SHIPPING_RATE_OPTIONS = 'fedex-shipping-rates';
    private const SHIPPING_ACCOUNT_NUMBER = 'shipment-account';

    private const TIMEZONE = 'timezone';

    private const SHIPPING_INFO_FORMAT = 'shipping-info-format';
    private const SHIPPING_CUT_OFF_TIME = 'cut-off-limit';
    private const SHIPPING_CUT_OFF_TIME_DEFAULT = '12 PM';
    private const SHIPPING_SELLER_HOLIDAYS = 'seller-holidays';
    private const ORIGIN_CITY = 'origin-city';
    private const ORIGIN_STATE = 'origin-state';
    private const ORIGIN_ZIPCODE = 'origin-zipcode';
    private const DEFAULT_TIMEZONE = 'CST';
    private const ORIGIN_COMBINED_OFFERS = 'origin-combined-offers';
    private const ADDITIONAL_PROCESSING_DAYS = 'additional-production-days';
    private const CUSTOMER_SHIPPING_ACCOUNT_ENABLED = 'enable-customer-shipment-account';
    private const FREIGHT_ENABLED = 'freight-enabled';
    private const FREIGHT_ACCOUNT_NUMBER = 'freight-account-number';
    private const FREIGHT_CITY = 'freight-city';
    private const FREIGHT_POSTCODE = 'freight-postcode';
    private const FREIGHT_STATE = 'freight-state';
    private const FREE_SHIPPING_NO_CUSTOMIZABLE = 'free-shipping-no-customizable';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param UrlInterface $urlBuilder
     * @param Serialize $serializer
     * @param MarketplaceConfigProvider $marketplaceConfigProvider
     * @param AbstractResource|null $resource
     * @param AbstractDbCollection|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        UrlInterface $urlBuilder,
        Serialize $serializer,
        protected MarketplaceConfigProvider $marketplaceConfigProvider,
        AbstractResource $resource = null,
        AbstractDbCollection $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $urlBuilder,
            $serializer,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @inheritDoc
     */
    public function getId(): string|null
    {
        return $this->getData(self::SHOP_ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($shopId)
    {
        $this->setData(self::SHOP_ID, $shopId);
        return $this;
    }

    /**
     * Retrieve the seller alternative name
     *
     * @return string
     */
    public function getSellerAltName()
    {
        if (isset($this->getAdditionalInfo()[self::ADDITIONAL_FIELD_VALUES])) {
            foreach ($this->getAdditionalInfo()[self::ADDITIONAL_FIELD_VALUES] as $data) {
                if ($data['code'] == self::SELLER_ALT_NAME) {
                    return $data['value'];
                }
            }
        }
        return static::DEFAULT_SELLER_ALT_NAME;
    }

    /**
     * Retrieve the tooltip
     *
     * @return string
     */
    public function getTooltip()
    {
        if (isset($this->getAdditionalInfo()[self::ADDITIONAL_FIELD_VALUES])) {
            foreach ($this->getAdditionalInfo()[self::ADDITIONAL_FIELD_VALUES] as $data) {
                if ($data['code'] == self::SELLER_TOOLTIP) {
                    return $data['value'];
                }
            }
        }
        return static::DEFAULT_SELLER_ALT_NAME;
    }

    /**
     * Retrieve the cart quantity tooltip
     *
     * @return string
     */
    public function getCartQuantityTooltip(): string
    {
        if (isset($this->getAdditionalInfo()[self::ADDITIONAL_FIELD_VALUES])) {
            foreach ($this->getAdditionalInfo()[self::ADDITIONAL_FIELD_VALUES] as $data) {
                if ($data['code'] == self::SELLER_CART_QUANTITY_TOOLTIP) {
                    return $data['value'];
                }
            }
        }
        return $this->marketplaceConfigProvider->getCartQuantityTooltip();
    }

    /**
     * Retrieve the cart quantity tooltip
     *
     * @return int|null
     */
    public function getCartExpire(): ?int
    {
        if (isset($this->getAdditionalInfo()[self::ADDITIONAL_FIELD_VALUES])) {
            foreach ($this->getAdditionalInfo()[self::ADDITIONAL_FIELD_VALUES] as $data) {
                if ($data['code'] == self::SELLER_CART_EXPIRE) {
                    return (int) $data['value'];
                }
            }
        }
        return null;
    }

    /**
     * Retrieve the cart quantity tooltip
     *
     * @return int|null
     */
    public function getCartExpireSoon(): ?int
    {
        if (isset($this->getAdditionalInfo()[self::ADDITIONAL_FIELD_VALUES])) {
            foreach ($this->getAdditionalInfo()[self::ADDITIONAL_FIELD_VALUES] as $data) {
                if ($data['code'] == self::SELLER_CART_EXPIRE_SOON) {
                    return (int) $data['value'];
                }
            }
        }
        return null;
    }

    /**
     * Retrieve the cart quantity tooltip
     *
     * @return string
     */
    public function getTimezone(): string
    {
        if (isset($this->getAdditionalInfo()[self::ADDITIONAL_FIELD_VALUES])) {
            foreach ($this->getAdditionalInfo()[self::ADDITIONAL_FIELD_VALUES] as $data) {
                if ($data['code'] == self::TIMEZONE) {
                    return $data['value'];
                }
            }
        }
        return static::DEFAULT_TIMEZONE;
    }


    /**
     * Retrieve the shipping rate option
     *
     * @return mixed
     */
    public function getShippingRateOption(): array
    {
        $shippingRateOption = '';
        $shippingAccountNumber = '';
        $shippingCutOffTime = '';
        $sellerHolidays = '';
        $originCity = '';
        $originState = '';
        $originZipcode = '';
        $originCombinedOffers = null;
        $additionalProcessingDays = 0;
        $customer_shipping_account_enabled = false;
        $freightEnabled = false;
        $freightAccountNumber = '';
        $freightCity = '';
        $freightState = '';
        $freightPostCode = '';
        $freeShippingNoCustomizable = false;


        if (isset($this->getAdditionalInfo()[self::ADDITIONAL_FIELD_VALUES])) {
            foreach ($this->getAdditionalInfo()[self::ADDITIONAL_FIELD_VALUES] as $data) {
                if ($data['code'] == self::SHIPPING_RATE_OPTIONS) {
                    $shippingRateOption = $data['value'];
                }
                if ($data['code'] == self::SHIPPING_ACCOUNT_NUMBER) {
                    $shippingAccountNumber = $data['value'];
                }
                if ($data['code'] == self::SHIPPING_CUT_OFF_TIME) {
                    $shippingCutOffTime = $data['value'];
                }
                if ($data['code'] == self::SHIPPING_SELLER_HOLIDAYS) {
                    $sellerHolidays = $data['value'];
                }
                if ($data['code'] == self::ORIGIN_CITY) {
                    $originCity = $data['value'];
                }
                if ($data['code'] == self::ORIGIN_STATE) {
                    $originState = $data['value'];
                }
                if ($data['code'] == self::ORIGIN_ZIPCODE) {
                    $originZipcode = $data['value'];
                }
                if ($data['code'] == self::ORIGIN_COMBINED_OFFERS) {
                    //mirakl is sending a string and not a boolean
                    $originCombinedOffers = strcasecmp($data['value'], "true") === 0
                        ? true
                        : (strcasecmp($data['value'], "false") === 0 ? false : null);
                }
                if ($data['code'] == self::ADDITIONAL_PROCESSING_DAYS) {
                    $additionalProcessingDays = $data['value'];
                }
                if ($data['code'] == self::CUSTOMER_SHIPPING_ACCOUNT_ENABLED) {
                    $customer_shipping_account_enabled = $data['value'] === "true";
                }
                if ($data['code'] == self::FREIGHT_ENABLED) {
                    $freightEnabled = $data['value'] === "true";
                }
                if ($data['code'] == self::FREIGHT_ACCOUNT_NUMBER) {
                    $freightAccountNumber = $data['value'];
                }
                if ($data['code'] == self::FREIGHT_CITY) {
                    $freightCity = $data['value'];
                }
                if ($data['code'] == self::FREIGHT_STATE) {
                    $freightState = $data['value'];
                }
                if ($data['code'] == self::FREIGHT_POSTCODE) {
                    $freightPostCode = $data['value'];
                }
                if ($data['code'] == self::FREE_SHIPPING_NO_CUSTOMIZABLE) {
                    $freeShippingNoCustomizable = $data['value'] === "true";
                }
            }
        }

        return [
            'shipping_rate_option' => !empty($shippingRateOption) ? $shippingRateOption : static::DEFAULT_SHIPPING_RATE_OPTIONS,
            'shipping_account_number' => $shippingAccountNumber,
            'shipping_cut_off_time' => !empty($shippingCutOffTime) ? $shippingCutOffTime : static::SHIPPING_CUT_OFF_TIME_DEFAULT,
            'shipping_seller_holidays' => $sellerHolidays,
            'origin_shop_city' => $originCity,
            'origin_shop_state' => $originState,
            'origin_shop_zipcode' => $originZipcode,
            'origin_combined_offers' => $originCombinedOffers,
            'additional_processing_days' => $additionalProcessingDays,
            'customer_shipping_account_enabled' => $customer_shipping_account_enabled,
            'freight_enabled' => $freightEnabled,
            'freight_account_number' => $freightAccountNumber,
            'freight_city' => $freightCity,
            'freight_state' => $freightState,
            'freight_postcode' => $freightPostCode,
            'free-shipping-no-customizable' => $freeShippingNoCustomizable
        ];
    }

    /**
     * Retrieve the seller Package API endpoint
     *
     * @return string|null
     */
    public function getSellerPackageApiEndpoint(): ?string
    {
        if (isset($this->getAdditionalInfo()[self::ADDITIONAL_FIELD_VALUES])) {
            foreach ($this->getAdditionalInfo()[self::ADDITIONAL_FIELD_VALUES] as $data) {
                if ($data['code'] == self::SELLER_PACKAGE_API_ENDPOINT) {
                    return $data['value'] ?? null;
                }
            }
        }
        return null;
    }

    /**
     * Retrieve ShippingInfoFormat from shop.
     *
     * @return string
     */
    public function getShippingInfoFormat(): string
    {
        if (isset($this->getAdditionalInfo()[self::ADDITIONAL_FIELD_VALUES])) {
            foreach ($this->getAdditionalInfo()[self::ADDITIONAL_FIELD_VALUES] as $data) {
                if ($data['code'] == self::SHIPPING_INFO_FORMAT) {
                    return $data['value'];
                }
            }
        }
        return static::DEFAULT_TIMEZONE;
    }
}
