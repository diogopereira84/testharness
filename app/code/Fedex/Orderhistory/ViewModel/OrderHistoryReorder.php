<?php

namespace Fedex\Orderhistory\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\Orderhistory\Helper\Data;

class OrderHistoryReorder implements ArgumentInterface
{
    /**
     * @param Data $orderHistoryhelper
     */
    public function __construct(
        protected Data $orderHistoryhelper
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function isRetailOrderHistoryReorderEnabled()
    {
        return $this->orderHistoryhelper->isRetailOrderHistoryReorderEnabled();
    }

    /**
     * Order product option to get prepare serialize Product Data
     *
     * @param array $infoBuyRequestData
     * @return array
     */
     public function serializeProductData($infoBuyRequestData)
    {
        $externalProductData = $infoBuyRequestData['external_prod'][0] ?? [];
         
        if (isset($infoBuyRequestData['fxoMenuId'])) {
            $serializedProductData = [];
            $serializedProductData['productionContentAssociations'] =
            $externalProductData['productionContentAssociations'] ?? [];
            $serializedProductData['userProductName'] = $externalProductData['userProductName'] ?? '';
            $serializedProductData['id'] = $externalProductData['id'] ?? null;
            $serializedProductData['version'] = $externalProductData['version'] ?? null;
            $serializedProductData['name'] = $externalProductData['name'] ?? '';
            $serializedProductData['qty'] = $externalProductData['qty'] ?? null;
            $serializedProductData['priceable'] = $externalProductData['priceable'] ?? false;
            $serializedProductData['instanceId'] = $externalProductData['instanceId'] ?? null;
            $serializedProductData['proofRequired'] = $externalProductData['proofRequired'] ?? false;
            $serializedProductData['isOutSourced'] = $externalProductData['isOutSourced'] ?? false;
            $serializedProductData['features'] = $externalProductData['features'] ?? [];
            $serializedProductData['pageExceptions'] = $externalProductData['pageExceptions'] ?? [];
            $serializedProductData['contentAssociations'] = $externalProductData['contentAssociations'] ?? [];
            $serializedProductData['properties'] = $externalProductData['properties'] ?? [];
            $productConfig = $infoBuyRequestData['productConfig'] ?? [];
            $productPresetId = $productConfig['productPresetId'] ?? null;
        } else {
            $fxoProductData = $externalProductData["fxo_product"] ?? [];
            $fxoProductData = json_decode($fxoProductData, true);
            $serializedProductData = [];
            $serializedProductData['productionContentAssociations'] =
            $externalProductData['productionContentAssociations'] ?? [];
            $serializedProductData['userProductName'] = $externalProductData['userProductName'] ?? '';
            $serializedProductData['id'] = $externalProductData['id'] ?? '';
            $serializedProductData['version'] = $externalProductData['version'] ?? null;
            $serializedProductData['name'] = $externalProductData['name'] ?? '';
            $serializedProductData['qty'] = $externalProductData['qty'] ?? null;
            $serializedProductData['priceable'] = $externalProductData['priceable'] ?? false;
            $serializedProductData['instanceId'] = $externalProductData['instanceId'] ?? '';
            $serializedProductData['proofRequired'] = $externalProductData['proofRequired'] ?? false;
            $serializedProductData['isOutSourced'] = $externalProductData['isOutSourced'] ?? false;
            $serializedProductData['features'] = $externalProductData['features'] ?? [];
            $serializedProductData['pageExceptions'] = $externalProductData['pageExceptions'] ?? [];
            $serializedProductData['contentAssociations'] = $externalProductData['contentAssociations'] ?? [];
            $serializedProductData['properties'] = $externalProductData['properties'] ?? [];
            $productPresetId = $fxoProductData ?
            isset($fxoProductData['fxoProductInstance']['productConfig']['productPresetId'])
            : null;
        }

        return [
            'serializedProductData' => $serializedProductData,
            'productPresetId' => $productPresetId
        ];
    }
}
