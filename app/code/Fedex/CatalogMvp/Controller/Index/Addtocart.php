<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Controller\Index;

use Exception;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\ProductFactory;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\InBranch\Model\InBranchValidation;
use Magento\Catalog\Model\ProductRepository;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;

/**
 * Class Addtocart
 * Handle the addToCart of the CatalogMvp
 */
class Addtocart implements ActionInterface
{

    /**
     * Addtocart Constructor
     *
     * @param Context $context
     * @param FormKey $formKey
     * @param Cart $cart
     * @param ProductFactory $product
     * @param LoggerInterface $logger
     * @param CatalogMvp $helper
     * @param JsonFactory $jsonFactory
     * @param InBranchValidation $inBranchValidation
     * @param ProductRepository $productRepository
     * @param ToggleConfig $toggleConfig
     * @param RequestInterface $request
     * @param CartRepositoryInterface $cartRepositoryInterface
     * @param AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle
     */
    public function __construct(
        private Context $context,
        private FormKey $formKey,
        private Cart $cart,
        private ProductFactory $product,
        private LoggerInterface $logger,
        private CatalogMvp $helper,
        private JsonFactory $jsonFactory,
        private RequestInterface $request,
        public readonly InBranchValidation $inBranchValidation,
        private ProductRepository $productRepository,
        private ToggleConfig $toggleConfig,
        private CartRepositoryInterface $cartRepositoryInterface,
        private readonly AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle
    ) { }
    public function execute()
    {
        if ($this->helper->isMvpSharedCatalogEnable()) {
            $json = $this->jsonFactory->create();
            $addedToCart = 0;
            $cartPerformanceToggle = $this->addToCartPerformanceOptimizationToggle->isActive();
            $minicartPriceFixToggle = $this->toggleConfig->getToggleConfigValue('tech_titan_d_202382');

            if ($cartPerformanceToggle) {
                $requestData = $this->request->getParams();
                $productIds = isset($requestData['id']) ? $requestData['id'] : '';
                $qty = isset($requestData['qty']) ? $requestData['qty'] : 1;
            } else {
                $productIds = $this->request->getParam('id');
                $qty = $this->request->getParam('qty');
            }

            $qtyValue = ($qty)??1;
            if (!empty($productIds)) {
                foreach ($productIds as $key => $productId) {
                    $params[$key] = [
                        'form_key' => $this->formKey->getFormKey(),
                        'product' => $productId,
                        'qty' => $qtyValue
                    ];
                }

                if($minicartPriceFixToggle){
                    try {
                        foreach ($params as $productData) {
                            $productModel = $this->productRepository->getById($productData['product']);

                            $getExternalProd = $productModel->getExternalProd();
                            if ($getExternalProd) {
                                $getExternalProd = ltrim((string)$getExternalProd, "external_prod=");
                                $externalData = json_decode($getExternalProd, true);
                                if (isset($externalData['qty'])) {
                                    $productData['qty'] = $externalData['qty'];
                                }
                            }
                            $isInBranchProductExist = $this->inBranchValidation->isInBranchValid($productModel);
                            if ($isInBranchProductExist) {
                                return $json->setData(['isInBranchProductExist' => true]);
                            }
                            $this->cart->addProduct($productModel, $productData);
                        }
                        $this->cartRepositoryInterface->save($this->cart->getQuote());
                        $addedToCart = count($params);

                    } catch (\Exception $error) {
                        $this->logger->critical(
                            __METHOD__ . ":" . __LINE__ . " Add to cart error " . $error->getMessage()
                        );
                    }
                } else {
                    foreach ($params as $product) {
                        try {
                            //Load the product based on productID

                            $productModel= $this->productRepository->getById($product['product']);

                            $getExternalProd = $productModel->getExternalProd();
                            if ($getExternalProd) {
                                $getExternalProd = ltrim((string) $getExternalProd, "external_prod=");
                                $externalData = json_decode($getExternalProd, true);
                                if (isset($externalData['qty'])) {
                                    $newQty =  $externalData['qty'];
                                    $product['qty'] = $newQty;
                                }
                            }
                            //Inbranch Implementation
                            if ($cartPerformanceToggle) {
                                $isInBranchProductExist = $this->inBranchValidation->isInBranchValid( $productModel);
                            } else {
                                $requestedProduct = $this->productRepository->getById($product['product']);
                                $isInBranchProductExist = $this->inBranchValidation->isInBranchValid( $requestedProduct);
                            }

                            if($isInBranchProductExist) {
                                return $json->setData(['isInBranchProductExist' => true]);
                            }

                            $this->cart->addProduct($productModel, $product);

                            $this->cartRepositoryInterface->save($this->cart->getQuote());

                            if ($this->toggleConfig->getToggleConfigValue('E443304_stop_redirect_mvp_addtocart')) {
                                $addedToCart = $addedToCart + 1;
                            } else {
                                $addedToCart = 1;
                            }
                        } catch (\Exception $error) {
                            $this->logger->critical(__METHOD__ . ":" . __LINE__ ." Add to cart error " . $error->getMessage());
                            continue;
                        }
                    }
                }

            }

            $json->setData($addedToCart);

            return $json;
        }
     }

}
