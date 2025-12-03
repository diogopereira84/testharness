<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceProduct\Api\OfferRepositoryInterface;
use Fedex\MarketplaceProduct\Helper\Data;
use Magento\Framework\Exception\NoSuchEntityException;

class Marketplace
{
    private const FROM_ID = 'fedex/navink_configuration/from_id';
    private const TO_ID = 'fedex/navink_configuration/to_id';
    private const SENDER_IDENTITY = 'fedex/navink_configuration/sender_id';
    private const SENDER_SHARED_SECRET = 'fedex/navink_configuration/sender_shared_secret';
    private const ACCOUNT_NUMBER = 'fedex/navink_configuration/account_number';
    private const NAVITOR_URL = 'fedex/navink_configuration/navitor_url';
    private const NAVITOR_AUTH_URL = 'fedex/navink_configuration/navitor_auth_url';
    private const NAVITOR_PRODUCT_INFO_URL = 'fedex/navink_configuration/navitor_product_info_url';
    private const NAVITOR_REORDER_URL = 'fedex/navink_configuration/navitor_reorder_url';
    private const DOWNTIME_MESSAGE_TITLE = 'fedex/marketplace_configuration/marketplace_downtime_message_title';
    private const DOWNTIME_MESSAGE_BODY = 'fedex/marketplace_configuration/marketplace_downtime_message_body';

    private $productSku = '';

    /**
     * Xpath enable external product update to quote
     */
    private const XPATH_ENABLE_SHOPS_CONNECTION_DATA_CHANGE = 'environment_toggle_configuration/environment_toggle/tiger_e426628_shops_connection_data_change';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param ToggleConfig $toggleConfig
     * @param OfferRepositoryInterface $offerRepository
     * @param Data $data
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected EncryptorInterface   $encryptor,
        private ToggleConfig $toggleConfig,
        private OfferRepositoryInterface $offerRepository,
        private Data $data
    )
    {
    }

    /**
     * Method gets "From ID" from admin configuration
     *
     * @param string $scopeType
     * @param int|null $scopeCode
     * @return string
     */
    public function getFromId(string $scopeType = ScopeInterface::SCOPE_WEBSITE, int $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::FROM_ID,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Method gets "To ID" from admin configuration
     *
     * @param string $scopeType
     * @param int|null $scopeCode
     * @return string
     */
    public function getToId(string $scopeType = ScopeInterface::SCOPE_WEBSITE, int $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::TO_ID,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Method gets "Sender Identity" from admin configuration
     *
     * @param string $scopeType
     * @param int|null $scopeCode
     * @return string
     */
    public function getSenderIdentity(string $scopeType = ScopeInterface::SCOPE_WEBSITE, int $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::SENDER_IDENTITY,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Method gets "Sender Shared Secret" from admin configuration
     *
     * @param string $scopeType
     * @param int|null $scopeCode
     * @return string
     */
    public function getSenderSharedSecret(string $scopeType = ScopeInterface::SCOPE_WEBSITE, int $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::SENDER_SHARED_SECRET,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Method gets "Navitor URL" from admin configuration
     *
     * @param string $scopeType
     * @param int|null $scopeCode
     * @return string
     */
    public function getNavitorUrl(string $scopeType = ScopeInterface::SCOPE_WEBSITE, int $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::NAVITOR_URL,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Method gets "Account Number" from admin configuration
     *
     * @param string $scopeType
     * @param int|null $scopeCode
     * @return string
     */
    public function getAccountNumber(string $scopeType = ScopeInterface::SCOPE_WEBSITE, int $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::ACCOUNT_NUMBER,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Method gets "Marketplace Downtime Message Title" from admin configuration
     *
     * @param string $scopeType
     * @param int|null $scopeCode
     * @return string
     */
    public function getMarketplaceDowntimeTitle(string $scopeType = ScopeInterface::SCOPE_WEBSITE, int $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::DOWNTIME_MESSAGE_TITLE,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Method gets "Marketplace Downtime Message" from admin configuration
     *
     * @param string $scopeType
     * @param int|null $scopeCode
     * @return string
     */
    public function getMarketplaceDowntimeMsg(string $scopeType = ScopeInterface::SCOPE_WEBSITE, int $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::DOWNTIME_MESSAGE_BODY,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Method gets "Navitor Auth URL" from admin configuration
     *
     * @param string $scopeType
     * @param int|null $scopeCode
     * @return string
     */
    public function getNavitorAuthUrl(string $scopeType = ScopeInterface::SCOPE_WEBSITE, int $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::NAVITOR_AUTH_URL,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Method gets "Navitor Product Info URL" from admin configuration
     *
     * @param string $scopeType
     * @param int|null $scopeCode
     * @return string
     */
    public function getNavitorProductInfoUrl(string $scopeType = ScopeInterface::SCOPE_WEBSITE, int $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::NAVITOR_PRODUCT_INFO_URL,
            $scopeType,
            $scopeCode
        );
    }
    /**
     * Method gets "Navitor Reorder URL" from admin configuration
     *
     * @param string $scopeType
     * @param int|null $scopeCode
     * @return string
     */
    public function getNavitorReorderUrl(
        string $scopeType = ScopeInterface::SCOPE_WEBSITE,
        int $scopeCode = null
    ): string
    {
        return (string)$this->scopeConfig->getValue(
            self::NAVITOR_REORDER_URL,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Gets status of enable shops connection toggle.
     * @return bool
     */
    public function isEnableShopsConnection(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfig(self::XPATH_ENABLE_SHOPS_CONNECTION_DATA_CHANGE);
    }

    /**
     * Return custom shop attributes data by product Sku.
     *
     * @param $productSku
     * @return array
     * @throws NoSuchEntityException
     */
    public function getShopCustomAttributesByProductSku($productSku): array
    {
        $offer = $this->offerRepository->getById($productSku);
        return $this->data->getCustomAttributes([$offer]);
    }

    public function setProductSkuToGetAttributes($productSku)
    {
        $this->productSku = $productSku;
    }

    /**
     * @param $productSku
     * @return \Mirakl\Core\Model\Shop
     * @throws NoSuchEntityException
     */
    public function getShopByOffer($productSku)
    {
        $offer = $this->offerRepository->getById($productSku);
        return $this->data->getShopByOffer([$offer]);
    }

    /**
     * @param $productSku
     * @return \Fedex\MarketplaceProduct\Api\Data\OfferInterface
     * @throws NoSuchEntityException
     */
    public function getOffer($productSku)
    {
        return $this->offerRepository->getById($productSku);
    }
}
