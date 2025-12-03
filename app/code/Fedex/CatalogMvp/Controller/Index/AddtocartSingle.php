<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Controller\Index;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\InBranch\Model\InBranchValidation;
use Magento\Catalog\Model\ProductRepository;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Event\ManagerInterface;

/**
 * Class AddtocartSingle
 * Handle the addToCartsingle of the CatalogMvp
 */
class AddtocartSingle implements ActionInterface
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
     * @param Session $checkoutSession
     * @param DataObjectFactory $dataObjectFactory
     * @param ManagerInterface $eventManager
     * @param PublisherInterface $publisher
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
        private readonly AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle,
        private Session $checkoutSession,
        private DataObjectFactory $dataObjectFactory,
        private ManagerInterface $eventManager,
        private PublisherInterface $publisher
    ) { }
    public function execute()
    {
        if ($this->helper->isMvpSharedCatalogEnable()) {
            $json = $this->jsonFactory->create();
            $addedToCart = 0;
            $cartPerformanceToggle = $this->addToCartPerformanceOptimizationToggle->isActive();

//            if ($cartPerformanceToggle) {
//                $requestData = $this->request->getParams();
//                $productId = isset($requestData['id']) ? $requestData['id'] : '';
//                $qty = isset($requestData['qty']) ? $requestData['qty'] : null;
//            } else {
                $productId = $this->request->getParam('id');
                $qty = $this->request->getParam('qty');
//            }

            if($productId){
                try {
                    
                        $productObj = $this->productRepository->getById($productId);
                    
                    if (!$qty) {
                        $externalProductData =  $productObj->getExternalProd();
                        if ($externalProductData) {
                            $externalProd = json_decode((string)$externalProductData, true);
                            if (isset($externalProd['qty'])) {
                                $qty = $externalProd['qty'];
                            }
                        }
                    }
                    $params = array(
                        'form_key' => $this->formKey->getFormKey(),
                        'product' => $productId,
                        'qty'   => $qty,
                    );
                    //Inbranch Implementation
                    if ($cartPerformanceToggle) {
                        $requestedProduct = $this->productRepository->getById($productId);
//                        $isInBranchProductExist = $this->inBranchValidation->isInBranchValid( $productObj);
                        $isInBranchProductExist = $this->inBranchValidation->isInBranchValid( $requestedProduct);
                     } else {
                        $requestedProduct = $this->productRepository->getById($productId);
                        $isInBranchProductExist = $this->inBranchValidation->isInBranchValid( $requestedProduct);
                    }

                    if($isInBranchProductExist){
                        return $json->setData(['isInBranchProductExist' => true]);
                    }


                    $this->cart->addProduct($productObj, $params);
                    
                        $this->cartRepositoryInterface->save($this->cart->getQuote());
                    


                    $addedToCart = 1;
                } catch (\Exception $error) {
                    $this->logger->critical(__METHOD__ . ":" . __LINE__ .
                        " Add to cart error " . $error->getMessage());
                    $this->logger->critical(__METHOD__ . ":" . __LINE__ .
                        " AddtocartSingle Trace: " . $error->getTraceAsString());
                }
            }
            $json->setData($addedToCart);
            return $json;
        }
    }
}
