<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceProduct
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Observer;

use Fedex\Catalog\Model\Config;
use Magento\Catalog\Api\BasePriceStorageInterface;
use Magento\Catalog\Api\Data\BasePriceInterfaceFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\DirectoryList as DirectoryListFileSystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Store\Model\Store;
use Mirakl\Process\Model\Process;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class OfferImportAfterRefreshPriceObserver implements ObserverInterface
{
    const CSV_DELIMITER = ';';
    private const TIGER_D_232503 = 'tiger_d232503';

    public function __construct(
        private File $file,
        private Csv $csv,
        private DirectoryListFileSystem $directoryList,
        private BasePriceInterfaceFactory $basePriceInterfaceFactory,
        private BasePriceStorageInterface $basePriceStorage,
        private readonly Action $productAction,
        private readonly Product $productModel,
        private readonly Config $catalogConfig,
        private readonly ToggleConfig $toggleConfig
    ) {
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws FileSystemException
     */
    public function execute(Observer $observer)
    {
        $skus = $observer->getEvent()->getSkus();
        if (empty($skus) || !$observer->getFile()) {
            return;
        }

        /** @var Process $process */
        $process = $observer->getEvent()->getProcess();
        $csvFile = $this->directoryList->getPath(DirectoryList::MEDIA) . '/' . $observer->getFile();

        try {
            $process->output(__('Updating prices for products...'), true);

            if ($this->file->isExists($csvFile)) {
                $this->csv->setDelimiter(self::CSV_DELIMITER);

                $data = $this->csv->getData($csvFile);
                $skuKey = array_search('product-sku', $data[0]);
                $priceKey = array_search('price', $data[0]);
                $activeKey = array_search('active', $data[0]);

                $unitCost3P1PProductsToggle = $this->catalogConfig->getTigerDisplayUnitCost3P1PProducts();
                if ($unitCost3P1PProductsToggle) {
                    $unitCostKey = array_search('unit-cost', $data[0]);
                    $baseQuantityKey = array_search('base-qty', $data[0]);
                }

                $previousOfferActiveState = [];

                if ($skuKey !== false && $priceKey !== false) {
                    array_shift($data);
                    $unitCostData = [];
                    foreach ($data as $productData) {
                        /** @var \Magento\Catalog\Api\Data\BasePriceInterface $basePriceInterface */
                        $basePriceInterface = $this->basePriceInterfaceFactory->create();
                        $basePriceInterface->setStoreId(Store::DEFAULT_STORE_ID);
                        $basePriceInterface->setPrice((float) $productData[$priceKey]);
                        $basePriceInterface->setSku($productData[$skuKey]);
                        $this->basePriceStorage->update([$basePriceInterface]);

                        if ($this->toggleConfig->getToggleConfigValue(self::TIGER_D_232503)) {
                            $boolActiveKey = $productData[$activeKey] === 'true' ? true : false;
                            isset($previousOfferActiveState[$productData[$skuKey]]) ?
                                $previousOfferActiveState[$productData[$skuKey]] = $previousOfferActiveState[$productData[$skuKey]] || $boolActiveKey :
                                $previousOfferActiveState[$productData[$skuKey]] = $boolActiveKey;

                        }

                        if ($unitCost3P1PProductsToggle && isset($unitCostKey) && isset($baseQuantityKey)) {
                            $unitCostData[(string)$productData[$skuKey]] = [
                                'base_price' => (float)$productData[$priceKey],
                                'base_quantity' => (int)$productData[$baseQuantityKey],
                                'unit_cost' => (float)$productData[$unitCostKey],
                                'price' => (float)$productData[$unitCostKey],
                            ];
                        }

                        if ($this->toggleConfig->getToggleConfigValue(self::TIGER_D_232503)) {
                            $unitCostData[(string)$productData[$skuKey]]['is_unavailable'] = !$previousOfferActiveState[$productData[$skuKey]];
                        }
                    }

                    if ($unitCost3P1PProductsToggle) {
                        $this->updateProductAttributes($unitCostData, Store::DEFAULT_STORE_ID);
                    }

                } else {
                    throw new \Exception("CSV file doesn't contain product-sku or price");
                }
            }
        } catch (\Exception $e) {
            $process->output(__('[ERROR] %1: %2', $csvFile, $e->getMessage()));
        }

        $process->output(__('Done!'));
    }

    /**
     * Update Attributes for Products
     *
     * @param array $unitCostData
     * @param $storeId
     * @return void
     */
    protected function updateProductAttributes(array $unitCostData, $storeId) {
        foreach ($unitCostData as $sku => $data) {
            $productId = $this->productModel->getIdBySku($sku);
            if($productId) {
                $this->productAction->updateAttributes([$productId], $data, $storeId);
            }
        }
    }
}
