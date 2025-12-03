<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model\RateQuoteRequestData;

use Fedex\SubmitOrderSidebar\Api\RateQuoteRequestDataInterface;

class ProductsMapper
{
    /**
     * @param RateQuoteRequestDataInterface $rateQuoteRequestData
     * @param $items
     * @return void
     */
    public function populateWithArray(RateQuoteRequestDataInterface $rateQuoteRequestData, $items): void
    {
        $id = 0;
        $products = [];
        $productAssociations = [];
        foreach ($items as $item) {
            $additionalOption = $item->getOptionByCode('info_buyRequest');
            $additionalOptions = $additionalOption->getValue();
            $productJson = (array) json_decode($additionalOptions)->external_prod[0];
            if (isset($productJson['catalogReference'])) {
                $productJson['catalogReference'] = (array) $productJson['catalogReference'];
            }
            if (isset($productJson['preview_url'])) {
                unset($productJson['preview_url']);
            }
            if (isset($productJson['fxo_product'])) {
                unset($productJson['fxo_product']);
            }
            $productJson['instanceId'] = $id;
            $productJson['qty'] = $item->getQty();
            $products[] = $productJson;
            $productAssociations[] = ['id' => $productJson['instanceId'], 'quantity' => $item->getQty()];

            $id++;
        }
        $rateQuoteRequestData->setProducts($products);
        $rateQuoteRequestData->setProductAssociations($productAssociations);
    }
}
