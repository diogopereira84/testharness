<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\Quote\ThirdPartyProduct;

use Fedex\MarketplaceProduct\Model\ShopManagement;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ExternalProd
{
    /**
     * @param ShopManagement $shopManagement
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private ShopManagement $shopManagement,
        private ToggleConfig $toggleConfig
    ) {
    }

    /**
     * return external_prod data.
     *
     * @param $item
     * @param $decodedData
     * @return array
     */
    public function createAdditionalData($item, $decodedData): array
    {
        $decodedData               = (array) $decodedData;
        $existingExternalProdData  = isset($decodedData["external_prod"][0]["product"]);
        if ($existingExternalProdData) {
            return $decodedData;
        }
        $shop                      = $this->shopManagement->getShopByProduct($item->getProduct());
        $additionalData            = json_decode($item->getAdditionalData(), true);
        $instanceIdFixToggle = $this->instanceIdFixToggle();
        if ($instanceIdFixToggle && empty($item->getItemId())) {
            $instanceId = mt_rand(pow(10, (12 - 1)), pow(10, 12) - 1);
        } else {
            $instanceId = $item->getItemId();
        }
        $decodedData['external_prod'][] = [
            'product' => [
                'id' => $item->getProduct()->getProductId(),
                'qty' => $item->getQty(),
                'name' => $item->getName(),
                'version' => '1',
                'instanceId' => $instanceId,
                'vendorReference' => [
                    'vendorId' =>  $shop->getId(),
                    'vendorProductName' => $item->getName(),
                    'vendorProductDesc' => $item->getName(),
                    'altName' => $shop->getSellerAltName(),
                ]
            ],
            'externalSkus' => [
                [
                    'skuDescription' => $item->getName(),
                    'skuRef' => $item->getProduct()->getData('map_sku'),
                    'code' => $item->getProduct()->getData('map_sku'),
                    'unitPrice' => $additionalData['unit_price'] ?? 0,
                    'price' => $additionalData['total'] ?? 0,
                    'qty' => $item->getQty()
                ]
            ],
            'sensitiveData' => false,
            'priceable' => true
        ];


        $decodedData['external_prod'][count($decodedData['external_prod']) - 1]['is_marketplace'] = true;


        return $decodedData;
    }

    /**
     * Check if Instance Id Toggle fix is enabled
     *
     * @return boolean
     */
    public function instanceIdFixToggle()
    {
        return (bool)$this->toggleConfig->getToggleConfigValue('explorers_instanceid_d_199138_fix');
    }
}
