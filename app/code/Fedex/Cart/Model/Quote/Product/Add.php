<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\Quote\Product;

use Fedex\Cart\Model\BuyRequestBuilder;
use Fedex\Cart\Model\Quote\ThirdPartyProduct\AddThirdParty;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\ProductBundle\ViewModel\BundleProductHandler;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\CartFactory as Cart;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Quote\Model\Quote;
use Fedex\Cart\Model\Quote\ThirdPartyProduct\Add as ThirdParty;
use Fedex\FXOCMConfigurator\Helper\Data;
use \Psr\Log\LoggerInterface;

class Add
{
    protected const PRODUCT = 'product';
    protected const PRODUCT_CONFIG = 'productConfig';
    protected const INSTANCE_ID = 'instanceId';
    protected const FXO_PRODUCT_INSTANCE = 'fxoProductInstance';
    protected const INFO_BUY_REQUEST = 'infoBuyRequest';
    protected const VALUE = 'value';
    protected const FXO_PRODUCT = 'fxo_product';
    protected const INFO_BYREQUEST = 'info_buyRequest';
    protected const INTEGRATOR_PRODUCT_REF = 'integratorProductReference';

    protected $cartQuote;

    /**
     * @param Cart $cart
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param RequestInterface $request
     * @param SerializerInterface $serializer
     * @param FXORate $fxoRateHelper
     * @param FXORateQuote $fxoRateQuote
     * @param BuyRequestBuilder $buyRequestBuilder
     * @param ToggleConfig $toggleConfig
     * @param CartItemInterfaceFactory $cartItemFactory
     * @param Quote $quote
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ThirdParty $thirdParty
     * @param Data $fxocmhelper
     * @param RequestQueryValidator $requestQueryValidator
     * @param ContentAssociationsResolver $contentAssociationsResolver
     * @param LoggerInterface $loggerInterface
     * @param BundleProductHandler $bundleProductHandler
     */
    public function __construct(
        protected Cart                       $cart,
        protected ProductRepositoryInterface $productRepositoryInterface,
        protected RequestInterface           $request,
        protected SerializerInterface        $serializer,
        protected FXORate                    $fxoRateHelper,
        protected FXORateQuote               $fxoRateQuote,
        private BuyRequestBuilder          $buyRequestBuilder,
        protected ToggleConfig               $toggleConfig,
        protected CartItemInterfaceFactory   $cartItemFactory,
        protected Quote                      $quote,
        protected SearchCriteriaBuilder      $searchCriteriaBuilder,
        private ThirdParty                 $thirdParty,
        private Data $fxocmhelper,
        private RequestQueryValidator $requestQueryValidator,
        private ContentAssociationsResolver $contentAssociationsResolver,
        protected LoggerInterface $loggerInterface,
        protected BundleProductHandler $bundleProductHandler
    )
    {
    }

    /**
     * Adds product to cart
     *
     * @param $requestData
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function addItemToCart($requestData, $itemId = false)
    {
        if (isset($requestData['isMarketplaceProduct'])) {
            $this->thirdParty->addItemToCart($requestData);
            return ['updatedProductName' => null];
        }

        $requestData = json_decode($requestData, true);
        $isGraphQlRequest = $this->requestQueryValidator->isGraphQl();
        $cart = $this->getCartQuote();

        if($this->fxocmhelper->getFxoCMToggle() && !$isGraphQlRequest && isset($requestData[self::INTEGRATOR_PRODUCT_REF])){
            $this->loggerInterface->info(
                __METHOD__ . ':' . __LINE__ . ' RequestData : '.json_encode($requestData)
                );
            $customizeDocument = false;

            if (isset($requestData['customDocumentDetails'])) {
                foreach($requestData['customDocumentDetails'] as $customDocumentDetail) {
                    if (isset($customDocumentDetail['formFields']) && count($customDocumentDetail['formFields']) > 0) {
                        $customizeDocument = true;

                        $requestData[self::PRODUCT][self::INSTANCE_ID] = rand(11111111,99999999);
                        break;
                    }
                }
            }

            $instanceId = $requestData[self::PRODUCT][self::INSTANCE_ID];
            $productSku = $requestData[self::INTEGRATOR_PRODUCT_REF];
            $product = $this->productRepositoryInterface->get($productSku);
            if (isset($requestData[self::PRODUCT]['userProductName'])) {
                $itemName = $requestData[self::PRODUCT]['userProductName'];
            } elseif (isset($requestData[self::PRODUCT]['name'])) {
                $itemName = $requestData[self::PRODUCT]['name'];
            }
            $data = $requestData[self::PRODUCT];
            $infoBuyRequest = $this->buyRequestBuilder->build($requestData);

            $params = [
                self::PRODUCT => $product->getId(),
                'qty' => $data['qty'],
            ];
            unset($requestData[self::FXO_PRODUCT_INSTANCE]['link']);
            $fxoProduct = json_encode($requestData);
            $contentReference = null;
            if (isset($data['contentAssociations'])) {
                $contentReference = $data['contentAssociations'][0]['contentReference'];
            }
            $data = json_encode($data);

            $itemDetails = [
                'previewUrl' => $contentReference,
                'itemName' => $itemName,
                'fxoProduct' => $fxoProduct,
                self::INFO_BUY_REQUEST => $infoBuyRequest ?? []
            ];

            $itemDetails = json_encode($itemDetails);
            $this->loggerInterface->info(
                __METHOD__ . ':' . __LINE__ . ' ItemDetails : '.$itemDetails
                );

            if (!empty($requestData[self::FXO_PRODUCT_INSTANCE]['isEdited']) &&
                $requestData[self::FXO_PRODUCT_INSTANCE]['isEdited'] === true) {
                $requestData[self::INSTANCE_ID] = $instanceId;
            }

            if (empty($requestData[self::FXO_PRODUCT_INSTANCE]['isEdited'])
                && empty($requestData[self::INSTANCE_ID])
                && $this->bundleProductHandler->hasBundleProductInCart()
                && $this->bundleProductHandler->hasQuoteItemWithInstanceId($instanceId)
            ) {
                $requestData[self::INSTANCE_ID] = $instanceId;
            }

            $updatedProductName = null;
            if (
                !empty($requestData[self::INSTANCE_ID])
                && !$customizeDocument
            ) {
                $updatedProductName = $this->updateItem($requestData, $params, $itemDetails, $data);
            } else {
                $this->request->setPostValue('configutorData', $data);
                $this->request->setPostValue('itemDetails', $itemDetails);

                $getEproLegacyLineItemsToggle = $this->fxocmhelper->getEproLegacyLineItemsToggle();
                //D-193132: if product Customizable then set customizeDocument variable true
                //For ePro legacy synced custom doc (Get customDocumentDetails array blank for ePro legacy custom doc)
                if ($getEproLegacyLineItemsToggle && $product->getCustomizable() && $customizeDocument==false) {
                    $customizeDocument = true;
                }

                if ($customizeDocument) {
                    $customizeFields = ['label' => 'customizeFields', self::VALUE => $requestData['customDocumentDetails'], 'data' => rand()];
                    $product->addCustomOption('customize_fields', $this->serializer->serialize($customizeFields));
                } else {
                    $commercialCartLineItemsDefectToggle = $this->fxocmhelper->getCommercialCartLineItemsToggle();
                    //D-191716 Commercial checkout page line items
                    if ($commercialCartLineItemsDefectToggle) {
                        $check32byt = false;
                        $check32byt = preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $productSku);
                        //Add in same line item if product is 32byt sku type
                        if (!$check32byt) {
                            $customOptions = ['label' => self::FXO_PRODUCT_INSTANCE, self::VALUE => $instanceId];
                            $product->addCustomOption('custom_option', $this->serializer->serialize($customOptions));
                        }
                    } else {
                        $customOptions = ['label' => self::FXO_PRODUCT_INSTANCE, self::VALUE => $instanceId];
                        $product->addCustomOption('custom_option', $this->serializer->serialize($customOptions));
                    }
                }
                $this->loggerInterface->info(
                __METHOD__ . ':' . __LINE__ . ' Quote Data : '.json_encode($cart->getQuote()->getId())
                );
                if ($itemId && isset($customizeFields)) {
                    $updatedProductName = $this->updateItem($requestData, $params, $itemDetails, $data, $itemId, $customizeFields);
                } else {
                    $cart->addProduct($product, $params);
                    $cart->save();
                }
            }

            return ['updatedProductName' => $updatedProductName ?? null];

        }else{
            $instanceId = $requestData[self::FXO_PRODUCT_INSTANCE][self::PRODUCT_CONFIG][self::PRODUCT][self::INSTANCE_ID];
            $productSku = $requestData['fxoMenuId'];
            $product = $this->productRepositoryInterface->get($productSku);
            $itemName = $requestData[self::FXO_PRODUCT_INSTANCE]['name'];
            $data = $requestData[self::FXO_PRODUCT_INSTANCE][self::PRODUCT_CONFIG][self::PRODUCT];
            $infoBuyRequest = $this->buyRequestBuilder->build($requestData);

            $params = [
                self::PRODUCT => $product->getId(),
                'qty' => $data['qty'],
            ];
            unset($requestData[self::FXO_PRODUCT_INSTANCE]['link']);
            $fxoProduct = json_encode($requestData);

            $contentReference = $this->contentAssociationsResolver->getContentReference(
                $data['contentAssociations'] ?? null
            );

            $data = json_encode($data);

            $itemDetails = [
                'previewUrl' => $contentReference,
                'itemName' => $itemName,
                'fxoProduct' => $fxoProduct,
                self::INFO_BUY_REQUEST => $infoBuyRequest ?? []
            ];

            $itemDetails = json_encode($itemDetails);
            $this->loggerInterface->info(
                __METHOD__ . ':' . __LINE__ . 'InfoBuyRequest Else : '.$itemDetails
            );
            if (!empty($requestData[self::FXO_PRODUCT_INSTANCE]['isEdited']) &&
                $requestData[self::FXO_PRODUCT_INSTANCE]['isEdited'] === true) {
                $requestData[self::INSTANCE_ID] = $instanceId;
            }
            $updatedProductName = null;
            if (!empty($requestData[self::INSTANCE_ID])) {
                $updatedProductName = $this->updateItem($requestData, $params, $itemDetails, $data);
            } else {
                $this->request->setPostValue('configutorData', $data);
                $this->request->setPostValue('itemDetails', $itemDetails);
                $customOptions = ['label' => self::FXO_PRODUCT_INSTANCE, self::VALUE => $instanceId];
                $product->addCustomOption('custom_option', $this->serializer->serialize($customOptions));
                $cart->addProduct($product, $params);
                $cart->save();
            }

            return ['updatedProductName' => $updatedProductName ?? null];

        }


    }

    private function getCartQuote()
    {
        if (!$this->cartQuote) {
            $this->cartQuote = $this->cart->create();
        }

        return $this->cartQuote;
    }

    /**
     * Set current cart
     *
     * @param $cart
     */
    public function setCart($cart)
    {
        $this->getCartQuote()->setQuote($cart);
    }

    /**
     * Update Item Rates
     *
     * @param $requestData
     * @param $params
     * @param $itemDetails
     * @param $externalData
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function updateItem($requestData, $params, $itemDetails, $externalData, $itemId = "", $customizeOption = "")
    {
        $cart = $this->getCartQuote();
        if($itemId == "")
        {
            $items = $cart->getItems();
            $this->loggerInterface->info(
                __METHOD__ . ':' . __LINE__ . 'Item Data : '.json_encode($items)
            );
            foreach ($items as $item) {
                if ($requestData[self::INSTANCE_ID] == $item->getData('instance_id')) {
                    $this->loggerInterface->info(
                        __METHOD__ . ':' . __LINE__ . 'Item Id : '.$item->getId()
                    );
                    $itemId = $item->getId();
                    break;
                }
            }
        }

        if ($itemId === '') {
            $itemId = $this->findCartItemByInstanceIdExternal($requestData[self::INSTANCE_ID]);
        }

        $externalData = json_decode($externalData, true);
        $itemDetails = json_decode($itemDetails, true);

        $externalData['preview_url'] = $itemDetails['previewUrl'];
        $externalData['item_name'] = $itemDetails['itemName'];


        $fxoProduct = $itemDetails['fxoProduct'];
        $fxoProduct = json_decode($fxoProduct, true);

        $fxoProduct = json_encode($fxoProduct);
        $externalData[self::FXO_PRODUCT] = $fxoProduct;


        $qty = $params['qty'];
        $instanceId = $requestData[self::INSTANCE_ID] ?? $itemId;

        $isGraphQlRequest = $this->requestQueryValidator->isGraphQl();

        if($this->fxocmhelper->getFxoCMToggle() && !$isGraphQlRequest){
            $productName = $requestData[self::PRODUCT]['userProductName'];
        } else {
            $productName = $requestData[self::FXO_PRODUCT_INSTANCE][self::PRODUCT_CONFIG][self::PRODUCT]['userProductName'];
        }

        $quoteObj = $cart->getQuote();
        $this->loggerInterface->info(
                        __METHOD__ . ':' . __LINE__ . 'Item Id : '.$itemId
        );
        $item = $quoteObj->getItemById($itemId);

        if (!$item) {
            throw new NoSuchEntityException(__("No such item with instanceId = %itemId", ['itemId' => $instanceId]));
        }

        if (isset($itemDetails[self::INFO_BUY_REQUEST]) && !empty($itemDetails[self::INFO_BUY_REQUEST])) {
            $item->addOption([
                'product_id' => $item->getProductId(),
                'code' => self::INFO_BYREQUEST,
                self::VALUE => $this->serializer->serialize($itemDetails[self::INFO_BUY_REQUEST]),
            ]);
            $item->saveItemOptions();
        } elseif (!empty($externalData)) {
            $save = [
                'external_prod' => [
                    0 => $externalData,
                ],
            ];
            $item->addOption([
                'product_id' => $item->getProductId(),
                'code' => self::INFO_BYREQUEST,
                self::VALUE => $this->serializer->serialize($save),
            ]);
            $item->saveItemOptions();
        }

        if (isset($customizeOption) && !empty($customizeOption)) {
            $item->addOption([
                'product_id' => $item->getProductId(),
                'code' => 'customize_fields',
                self::VALUE => $this->serializer->serialize($customizeOption),
            ]);
            $item->saveItemOptions();
        }

        $item->setQty($qty);
        $item->setInstanceId($instanceId);
        if ((!empty($requestData)) && (is_array($requestData))) {
            $cartItemExtensionAttributes = $item->getExtensionAttributes();
            if ((!empty($cartItemExtensionAttributes))
                && (!empty($cartItemExtensionAttributes->getIntegrationItemData()))) {
                $item->getExtensionAttributes()->getIntegrationItemData()->setItemData(json_encode($requestData));
            }
        }

        $item->save();
        $cart->save();

        if (!$this->fxoRateHelper->isEproCustomer()) {
            $this->fxoRateQuote->getFXORateQuote($quoteObj);
        } else {
            // Call to common rate call function
            $this->fxoRateHelper->getFXORate($quoteObj);
        }

        return $productName;
    }

    /**
     * Find Cart Item By InstanceId
     *
     * @param int $instanceId
     *
     * @return int
     */
    public function findCartItemByInstanceIdExternal($instanceId)
    {
        $items = $this->getCartQuote()->getItems();
        $itemId = null;
        foreach ($items as $item) {
            $option = $item->getOptionByCode(self::INFO_BYREQUEST);
            if ($option) {
                $infoBuyRequest = $option->getValue();
                $externalProduct = end(json_decode($infoBuyRequest, true)['external_prod']);
                if (isset($externalProduct[self::FXO_PRODUCT])) {
                    $itemId = $this->getItemIdForFxoProduct($externalProduct, $item, $instanceId);
                } elseif (!isset($externalProduct[self::FXO_PRODUCT])) {
                    if (isset($externalProduct[self::INSTANCE_ID]) &&
                        (string)$externalProduct[self::INSTANCE_ID] === (string)$instanceId) {
                        $itemId = $item->getId();
                        break;
                    }
                }
            }
        }

        return $itemId;
    }

    /**
     * Get Item Id if External Product has FXO_Product
     */
    public function getItemIdForFxoProduct($externalProduct, $item, $instanceId)
    {
        $externalProduct = $externalProduct[self::FXO_PRODUCT];
        if (json_decode($externalProduct, true) !== null) {
            $externalProduct = json_decode($externalProduct, true);
        }

        if ($externalProduct) {
            if (isset($externalProduct[self::INSTANCE_ID]) &&
                (string)$externalProduct[self::INSTANCE_ID] === (string)$instanceId) {
                return $item->getId();
            }
            if (isset($externalProduct[self::FXO_PRODUCT_INSTANCE]
                    [self::PRODUCT_CONFIG][self::PRODUCT][self::INSTANCE_ID]) &&
                (string)$externalProduct[self::FXO_PRODUCT_INSTANCE]
                [self::PRODUCT_CONFIG][self::PRODUCT][self::INSTANCE_ID] ===
                (string)$instanceId) {
                return $item->getId();
            }
        }
    }
}
