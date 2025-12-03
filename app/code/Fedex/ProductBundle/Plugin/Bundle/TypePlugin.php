<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\Plugin\Bundle;

use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\ProductBundle\Service\BundleProductProcessor;
use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Phrase;

class TypePlugin
{
    private const BUNDLE_ID_HASH_OPTION = 'bundle_instance_id_hash';
    private const PRODUCTS_DATA_OPTION = 'productsData';
    private const INFO_BUYREQUEST = 'info_buyRequest';
    private const SELECT_QTY = 'selection_qty_';
    private const PRODUCT_QTY = 'product_qty_';

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly BundleProductProcessor $bundleProductProcessor,
        private readonly RequestInterface $request,
    ) {
    }

    /**
     * After plugin for prepareForCartAdvanced
     *
     * @param Type $subject
     * @param array|Phrase|string $result
     * @param DataObject $buyRequest
     * @param Product $product
     * @param string $processMode
     * @return array|Phrase|string
     */
    public function afterPrepareForCartAdvanced(
        Type $subject,
        array|Phrase|string $result,
        DataObject $buyRequest,
        Product $product,
        string $processMode
    ): array|Phrase|string {
        if (!$this->config->isTigerE468338ToggleEnabled() || $product->getTypeId() !== Type::TYPE_CODE) {
            return $result;
        }

        $bundleIdHashOption = $product->getCustomOption(self::BUNDLE_ID_HASH_OPTION);
        if ($bundleIdHashOption === null) {
            return $result;
        }

        $productsQtyData = json_decode($this->request->getParam('productsQtyData') ?? '', true);

        if (!is_array($result)) {
            return $result;
        }

        $parentItem = null;
        $parentBuyRequest = null;
        $productDataBySKU = [];

        foreach ($result as $item) {
            if ($item->getTypeId() === Type::TYPE_CODE) {
                $parentItem = $item;
                $parentBuyRequest = $item->getCustomOption(self::INFO_BUYREQUEST);
                $productsDataOption = $item->getCustomOption(self::PRODUCTS_DATA_OPTION);
                $item->addCustomOption(self::PRODUCTS_DATA_OPTION, null);
                $productDataBySKU = $this->bundleProductProcessor->mapProductsBySkuForQuoteApproval(
                    $productsDataOption ? $productsDataOption->getValue() : []
                );
                break;
            }
        }

        foreach ($result as $item) {
            if ($item instanceof Product && $item->getCustomOption(self::BUNDLE_ID_HASH_OPTION) === null) {
                $item->addCustomOption(self::BUNDLE_ID_HASH_OPTION, $bundleIdHashOption->getValue());
            }

            if ($this->isValidChildItem($item, $parentItem)) {
                $this->updateChildItemQty($item, $parentItem, $productsQtyData, $productDataBySKU);
            }

            if ($item->getTypeId() !== Type::TYPE_CODE
                && $item->getCustomOption(self::INFO_BUYREQUEST) !== null
                && $parentBuyRequest !== null
                && $item->getCustomOption(self::INFO_BUYREQUEST)->getValue() === $parentBuyRequest->getValue()
                && isset($productDataBySKU[$item->getSku()])
            ) {
                $item->addCustomOption(self::INFO_BUYREQUEST, $productDataBySKU[$item->getSku()]);
            }
        }

        return $result;
    }

    /**
     * Update child item qty based on productsQtyData or productsData
     * @param Product $item
     * @param Product $parentItem
     * @param array|null $productsQtyData
     * @param array $productDataBySKU
     * @return void
     */
    private function updateChildItemQty($item, $parentItem, $productsQtyData, $productDataBySKU): void
    {
        $sku = $item->getSku();
        $selectionQtyCustomOptionName = self::SELECT_QTY . $item->getSelectionId();
        $productQtyCustomOptionsName = self::PRODUCT_QTY . $item->getId();

        $qty = null;
        if (!empty($productsQtyData) && isset($productsQtyData[$sku])) {
            $qty = (float)$productsQtyData[$sku];
        } elseif (isset($productDataBySKU[$sku])) {
            $qty = $this->getQtyFromProductsData($productDataBySKU[$sku]);
        }

        if ($qty === null) {
            return;
        }

        $this->updateParentCustomOptions($parentItem, $selectionQtyCustomOptionName, $productQtyCustomOptionsName, $qty);
        $this->setItemQuantities($item, $qty);
    }

    /**
     * Update parent item's custom options for selection and product qty
     * @param Product $parentItem
     * @param string $selectionQtyCustomOptionName
     * @param string $productQtyCustomOptionsName
     * @param float $qty
     * @return void
     */
    private function updateParentCustomOptions($parentItem, $selectionQtyCustomOptionName, $productQtyCustomOptionsName, $qty): void
    {
        $selectionQtyCustomOption = $parentItem->getCustomOption($selectionQtyCustomOptionName);
        if ($selectionQtyCustomOption) {
            $selectionQtyCustomOption->setValue($qty);
        }
        $productQtyCustomOptions = $parentItem->getCustomOption($productQtyCustomOptionsName);
        if ($productQtyCustomOptions) {
            $productQtyCustomOptions->setValue($qty);
        }
    }

    /**
     * Set item qty and cart qty
     * @param Product $item
     * @param float $qty
     * @return void
     */
    private function setItemQuantities($item, $qty): void
    {
        $item->setQty($qty);
        $item->setCartQty($qty);
    }

    /**
     * Get qty from productsData custom option
     * @param string $childInstance
     * @return float
     */
    private function getQtyFromProductsData(string $childInstance): float
    {
        if(empty($childInstance)) {
            return 1.0;
        }

        $childInstanceDecoded = json_decode($childInstance, true);
        if (empty($childInstanceDecoded) && empty($childInstanceDecoded['external_prod'])) {
            return 1.0;
        }

        return (float)($childInstanceDecoded['external_prod'][0]['qty'] ?? 1);
    }

    /**
     * Check if item is valid child item of bundle
     * @param $item
     * @param $parentItem
     * @return bool
     */
    private function isValidChildItem($item, $parentItem): bool
    {
        return $item->getTypeId() !== Type::TYPE_CODE
            && $item->getCustomOption(self::INFO_BUYREQUEST) !== null
            && $item->getSelectionId() !== null
            && $item->getOptionId() !== null
            && $parentItem;
    }
}
