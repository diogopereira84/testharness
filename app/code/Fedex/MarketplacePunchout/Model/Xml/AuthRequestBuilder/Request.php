<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplacePunchout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model\Xml\AuthRequestBuilder;

use Fedex\MarketplacePunchout\Api\BuilderInterface;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Magento\Framework\Simplexml\Element;
use Magento\Framework\Simplexml\ElementFactory;

class Request implements BuilderInterface
{
    /**
     * @param ElementFactory $xmlFactory
     * @param MarketplaceConfig $config
     */
    public function __construct(
        private ElementFactory $xmlFactory,
        private MarketplaceConfig $config
    ) {
    }

    /**
     * Build the request xml body
     *
     * @return Element
     */
    public function build($productSku = null, mixed $productConfigData = null): Element
    {
        $xml = $this->xmlFactory->create(
            ['data' => '<Request/>']
        );

        $accountNumber = $this->config->getAccountNumber();

        if ($this->config->isEnableShopsConnection() && $productSku !== null) {
            $shopCustomAttributes = $this->config->getShopCustomAttributesByProductSku($productSku);
            $accountNumber        = $shopCustomAttributes['account-number'] ?? $accountNumber;
        }

        $request = $xml->addChild('AuthRequest');
        $credential = $request->addChild('Credential');
        $credential->addAttribute('domain', 'NetworkId');;
        $credential->addChild('Identity', $accountNumber);

        return $xml;
    }
}
