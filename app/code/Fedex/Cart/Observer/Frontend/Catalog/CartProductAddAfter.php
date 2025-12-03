<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Cart\Observer\Frontend\Catalog;

use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\ProductBundle\Model\Config;
use Fedex\ProductBundle\Service\BundlePriceCalculator;
use Fedex\ProductBundle\Service\BundleProductProcessor;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

class CartProductAddAfter implements ObserverInterface
{
    public const EXPLORERS_HANDLE_PRINTREADYFLAG = 'explorers_handle_printReadyFlag';

    protected const INSTANCE_ID = 'instanceId';

    /**
     * Execute observer construct
     *
     * @param SerializerInterface $serializer
     * @param RequestInterface $request
     * @param AttributeSetRepositoryInterface $attributeSetRepositoryInterface
     * @param FXORate $fxoRateHelper
     * @param FXORateQuote $fxoRateQuote
     * @param ManagerInterface $messageManager ,
     * @param ToggleConfig $toggleConfig
     * @param Product $productInstance
     * @param InstoreConfig $instoreConfig
     * @param UploadToQuoteViewModel $uploadToQuoteViewModel
     * @param BundleProductProcessor $bundleProductProcessor
     * @param BundlePriceCalculator $bundlePriceCalculator
     */
    public function __construct(
        protected SerializerInterface $serializer,
        protected RequestInterface $request,
        protected AttributeSetRepositoryInterface $attributeSetRepositoryInterface,
        protected FXORate $fxoRateHelper,
        protected FXORateQuote $fxoRateQuote,
        protected ManagerInterface $messageManager,
        protected ToggleConfig $toggleConfig,
        private Product $productInstance,
        private InstoreConfig $instoreConfig,
        protected UploadToQuoteViewModel $uploadToQuoteViewModel,
        private BundleProductProcessor $bundleProductProcessor,
        private BundlePriceCalculator $bundlePriceCalculator,
        private LoggerInterface $logger,
        private Config $bundleConfig
    )
    {
    }

    /**
     * Execute Observer method
     *
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        if ($this->request->getPostValue('isMarketplaceProduct') == null) {
            /** @var Quote\Item $quoteItem */
            $quoteItem = $observer->getEvent()->getQuoteItem();
            /** @var Quote $quote */
            $quote = $quoteItem->getQuote();

            if($quote && $this->toggleConfig->getToggleConfigValue('tech_titan_d_202382') && $quoteItem->getProductType() !== Type::TYPE_BUNDLE){
                foreach ($quote->getAllItems() as $item) {
                    $product = $item->getProduct();
                    $qty = $item->getQty();
                    $externalData = "";

                    $isAttribute = $product->getAttributeSetId();
                    $attributeSetRepository = $this->attributeSetRepositoryInterface->get($isAttribute);
                    $attributeSetName = $attributeSetRepository->getAttributeSetName();
                    $isCustomize = $product->getCustomizable();
                    $instanceId = 0;

                    if ($attributeSetName != 'FXOPrintProducts' && !$isCustomize) {
                        $getExternalProd = $product->getExternalProd();

                        if (!$getExternalProd) {
                            $loadedProduct = $this->productInstance->load($product->getId());
                            $getExternalProd = $loadedProduct->getExternalProd();
                        }
                        $getExternalProd = ltrim((string)$getExternalProd, "external_prod=");
                        $externalData = json_decode($getExternalProd, true);

                        // Check and update printReady Flag
                        $handlePrintReadyFlagToggle = $this->toggleConfig
                            ->getToggleConfigValue(self::EXPLORERS_HANDLE_PRINTREADYFLAG);
                        if ($handlePrintReadyFlagToggle) {
                            $externalData = $this->handlePrintReadyFlag($product, $externalData);
                        }

                        if (!empty($externalData)) {
                            $externalData['qty'] = "$qty";
                        }

                        if (isset($externalData['fxoProductInstance']) && $attributeSetName == 'PrintOnDemand') {
                            $productData = $externalData['fxoProductInstance']['productConfig']['product'];
                            unset($productData['instanceId']);
                            $externalData = array_merge($externalData, $productData);
                        }

                        $infoBuyRequest = ['external_prod' => [0 => $externalData]];
                    } else {
                        // Handle customized products
                        $externalData = $this->request->getPostValue('configutorData');
                        $externalData = json_decode((string)$externalData, true);
                        $itemDetails = $this->request->getPostValue('itemDetails');
                        $itemDetails = json_decode($itemDetails, true);

                        $externalData['preview_url'] = $itemDetails['previewUrl'] ?? '';
                        if (!empty($itemDetails) && array_key_exists('fxoProduct', $itemDetails)) {
                            $fxoProduct = json_decode($itemDetails['fxoProduct'], true);
                            $fxoProduct[self::INSTANCE_ID] = $externalData['instanceId'] ?? rand();
                            $instanceId = $fxoProduct[self::INSTANCE_ID];
                            $fxoProduct = json_encode($fxoProduct, true);
                            $externalData['fxo_product'] = $fxoProduct;
                        }

                        $infoBuyRequest = [
                            'external_prod' => [0 => $externalData],
                        ];
                    }

                    if (!empty($infoBuyRequest)) {
                        $serializeInfoByReq = $this->serializer->serialize($infoBuyRequest);
                        $item->addOption([
                            'product_id' => $item->getProductId(),
                            'code' => 'info_buyRequest',
                            'value' => $serializeInfoByReq,
                        ]);
                        $siType = $this->uploadToQuoteViewModel->getSiType($serializeInfoByReq);
                        $item->setSiType($siType);
                    }

                    $item->setInstanceId($instanceId);
                }

                if (!$this->fxoRateHelper->isEproCustomer()) {
                    try {
                        $fxoRateResponse = $this->fxoRateQuote->getFXORateQuote($quote);
                        if(isset($fxoRateResponse['errors'])
                            && (is_string($fxoRateResponse['errors'])
                                || (is_array($fxoRateResponse['errors']) && count($fxoRateResponse['errors']))
                            )
                        ) {
                            $quote->setFxoRateError(true);
                        }
                    } catch (GraphQlFujitsuResponseException $e) {
                        if ($this->instoreConfig->isEnabledThrowExceptionOnGraphqlRequests()) {
                            throw new GraphQlFujitsuResponseException(__($e->getMessage()));
                        }
                    }
                } else {
                    $fxoRateResponse = $this->fxoRateHelper->getFXORate($quote);
                }
            } elseif ($this->bundleConfig->isTigerE468338ToggleEnabled() && $quoteItem->getProductType() === Type::TYPE_BUNDLE) {
                $productsData = $this->request->getParam('productsData') ?? null;
                $fxoRateResponse = $this->bundleProductProcessor->processBundleItems($productsData, $quoteItem, $quote);
                $this->bundlePriceCalculator->calculateBundlePrice($fxoRateResponse,$quoteItem);
            } else {
                $product = $quoteItem->getProduct();
                $qty = $quoteItem->getQty();
                $externalData = "";
                $isAttribute = $product->getAttributeSetId();
                $attributeSetRepository = $this->attributeSetRepositoryInterface->get($isAttribute);
                $attributeSetName = $attributeSetRepository->getAttributeSetName();
                $instanceId = 0;
                $isCustomize = $product->getCustomizable();
                if ($attributeSetName != 'FXOPrintProducts' && !$isCustomize) {
                    $getExternalProd = $product->getExternalProd();

                    if (!$getExternalProd) {
                        $loadedProduct = $this->productInstance->load($product->getId());
                        $getExternalProd = $loadedProduct->getExternalProd();
                    }
                    $getExternalProd = ltrim((string)$getExternalProd, "external_prod=");
                    $externalData = json_decode($getExternalProd, true);

                    // Check and update printReady Flag based on purpose value under content reference
                    $handlePrintReadyFlagToggle = $this->toggleConfig
                        ->getToggleConfigValue(self::EXPLORERS_HANDLE_PRINTREADYFLAG);
                    if ($handlePrintReadyFlagToggle) {
                        $externalData = $this->handlePrintReadyFlag($product, $externalData);
                    }

                    if (!empty($externalData)) {
                        $externalData['qty'] = "$qty";
                    }
                    if (isset($externalData['fxoProductInstance']) && $attributeSetName == 'PrintOnDemand') {
                        $productData =  $externalData['fxoProductInstance']['productConfig']['product'];
                        unset($productData['instanceId']);
                        $externalData = array_merge($externalData, $productData);
                    }

                    $infoBuyRequest = ['external_prod' => [0 => $externalData]];
                } else {
                    $externalData = $this->request->getPostValue('configutorData');
                    $externalData = json_decode((string)$externalData, true);
                    $itemDetails = $this->request->getPostValue('itemDetails');
                    $itemDetails = json_decode($itemDetails, true);
                    $externalData['preview_url'] = isset($itemDetails['previewUrl']) ? $itemDetails['previewUrl'] : '';
                    if (!empty($itemDetails) && array_key_exists('fxoProduct', $itemDetails)) {
                        $fxoProduct = $itemDetails['fxoProduct'];
                        $fxoProduct = json_decode($fxoProduct, true);
                        // for FXO CM configurator
                        $fxoProduct[self::INSTANCE_ID] = $externalData['instanceId'];
                        // Generate instance id
                        if (empty($fxoProduct[self::INSTANCE_ID])) {
                            $r = rand(1, 100000000000000);
                            $pId = $quoteItem->getProduct()->getId();
                            $fxoProduct[self::INSTANCE_ID] = $pId . $r;
                        }
                        $instanceId = $fxoProduct[self::INSTANCE_ID];

                        $fxoProduct = json_encode($fxoProduct, true);
                        $externalData['fxo_product'] = $fxoProduct;

                    }
                    if ($itemDetails && isset($itemDetails['infoBuyRequest'])) {
                        $infoBuyRequest = $itemDetails['infoBuyRequest'];
                    } else {
                        $infoBuyRequest = [
                            'external_prod' => [
                                0 => $externalData,
                            ]
                        ];
                    }
                }
                if (!empty($infoBuyRequest)) {
                    $serializeInfoByReq = $this->serializer->serialize($infoBuyRequest);
                    $quoteItem->addOption([
                        'product_id' => $quoteItem->getProductId(),
                        'code' => 'info_buyRequest',
                        'value' => $serializeInfoByReq,
                    ]);
                    $siType = $this->uploadToQuoteViewModel->getSiType($serializeInfoByReq);
                    $quoteItem->setSiType($siType);
                }

                $quoteItem->setInstanceId($instanceId);
                if (!$this->fxoRateHelper->isEproCustomer()) {
                    try {
                        $fxoRateResponse = $this->fxoRateQuote->getFXORateQuote($quote);
                        if(isset($fxoRateResponse['errors'])
                            && (is_string($fxoRateResponse['errors'])
                                || (is_array($fxoRateResponse['errors']) && count($fxoRateResponse['errors']))
                            )
                        ) {
                            $quote->setFxoRateError(true);
                        }
                    } catch (GraphQlFujitsuResponseException $e) {
                        if ($this->instoreConfig->isEnabledThrowExceptionOnGraphqlRequests()) {
                            throw new GraphQlFujitsuResponseException(__($e->getMessage()));
                        }
                    }
                } else {
                    $fxoRateResponse = $this->fxoRateHelper->getFXORate($quote);
                }
            }
        }
    }

    /**
     * Handle print ready Flag when purpose is PRINT_INTENT
     * @param  object $product
     * @param  array  $externalData
     * @return array  $externalData
     */
    public function handlePrintReadyFlag($product, $externalData)
    {
        $printReadyUpdated = 0;

        if (isset($externalData['contentAssociations']) && is_array($externalData['contentAssociations'])) {
            foreach ($externalData['contentAssociations'] as $key => $contentAssociation) {
                if (isset($contentAssociation['purpose']) && $contentAssociation['purpose'] === 'PRINT_INTENT' &&
                    (
                        !isset($externalData['contentAssociations'][$key]['printReady']) ||
                        $externalData['contentAssociations'][$key]['printReady'] == true
                    )
                ) {
                    $externalData['contentAssociations'][$key]['printReady'] = false;
                    $printReadyUpdated = 1;
                }
            }

            if ($printReadyUpdated) {
                $product->setExternalProd(json_encode($externalData));
                $product->save();
            }
        }

        return $externalData;
    }
}
