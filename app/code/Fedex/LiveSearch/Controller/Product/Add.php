<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\LiveSearch\Controller\Product;

use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\InBranch\Model\InBranchValidation;
use Fedex\LiveSearch\Model\ChildProductOffer;
use Fedex\MarketplaceCheckout\Helper\Data;
use Fedex\MarketplaceProduct\Helper\Data as MarketplaceProductHelper;
use Fedex\MarketplaceProduct\Model\AddToCartContext;
use Fedex\MarketplaceProduct\ViewModel\Product\Attribute;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class Add implements ActionInterface
{
    /**
     * @param ProductRepositoryInterface $productRepository
     * @param InBranchValidation $inBranchValidation
     * @param JsonFactory $resultJsonFactory
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param Cart $cart
     * @param CatalogMvp $catalogMvp
     * @param ChildProductOffer $childProductOffer
     * @param Data $marketplaceHelper
     * @param AddToCartContext $context
     * @param MarketplaceProductHelper $marketplaceProductHelper
     * @param Attribute $mktpProductViewModel
     */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly InBranchValidation         $inBranchValidation,
        private readonly JsonFactory                $resultJsonFactory,
        private readonly LoggerInterface            $logger,
        private readonly RequestInterface           $request,
        private Cart                                $cart,
        protected CatalogMvp                        $catalogMvp,
        private ChildProductOffer                   $childProductOffer,
        private Data                                $marketplaceHelper,
        private AddToCartContext                    $context,
        private MarketplaceProductHelper            $marketplaceProductHelper,
        private Attribute                           $mktpProductViewModel
    ) {
    }

    /**
     * @return ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        try {
            $resultJsonData = $this->resultJsonFactory->create();
            $sku = $this->request->getPostValue('sku');
            $params = [];
            if ($this->request->isAjax() == true && !empty($sku)) {
                try {
                    $product = $this->productRepository->get($sku);
                    $isEnableStopRedirectMvpAddToCart = (boolean)$this->catalogMvp->isEnableStopRedirectMvpAddToCart();
                    if ($isEnableStopRedirectMvpAddToCart) {
                        $qty = 0;
                        if (!empty($product->getExternalProd())) {
                            $externalProd = json_decode((string)$product->getExternalProd(), true);
                            if (isset($externalProd['qty'])) {
                                $qty = $externalProd['qty'];
                            }
                        }
                        if (!$qty) {
                            $qty = 1;
                        }
                    } else {
                        $qty = 1;
                    }

                    //Inbranch Implementation
                    $isInBranchProductExist = $this->inBranchValidation->isInBranchValid($product);
                    if ($isInBranchProductExist) {
                        return $resultJsonData->setData(['isInBranchProductExist' => true]);
                    }

                    // Essendant (Marketplace) Implementation
                    if ($this->marketplaceHelper->isEssendantToggleEnabled()
                        && $product->getData('page_layout') === 'third-party-product-full-width') {
                        $params = [
                            'sku' => $product->getSku(),
                            'qty' => $qty,
                            'isMarketplaceProduct' => true,
                        ];

                        $offer = $this->marketplaceProductHelper->getBestOffer($product);
                        [$minQty, $maxQty, $punchoutDisabled] = $this->mktpProductViewModel->getMinMaxPunchoutInfo($offer, $product);

                        if ($product->getTypeId() === 'configurable') {
                            $selectedOptions = $this->request->getParam('selectedOptions') ?? [];
                            $params['super_attribute'] = $selectedOptions;
                            $offerId = $this->childProductOffer->getChildProductOfferId($product, $selectedOptions);
                            $this->request->setPostValue('super_attribute', $this->arrayToCustomString($selectedOptions));
                        } else {
                            $offerId = $offer->getId();
                        }

                        $params['offer_id'] = $offerId;
                        $this->request->setPostValue('offer_id', $offerId);
                        $this->request->setPostValue('punchout_disabled', $punchoutDisabled);
                        $this->request->setPostValue('qty', $qty);

                        $this->context->getQuoteProductAdd()->addItemToCart($params);
                    } else {
                        // Non-Essendant Implementation
                        $this->cart->addProduct($product, $qty)->save();
                    }

                    return $resultJsonData->setData(['success' => true]);
                } catch (NoSuchEntityException $e) {
                    return $resultJsonData->setData(['error' => true, 'errorMsg' => $e->getMessage()]);
                }
            }
            return $resultJsonData->setData(['error' => true]);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    private function arrayToCustomString(array $arr): string
    {
        $items = [];
        foreach ($arr as $key => $value) {
            $items[] = $key . '=>' . $value;
        }
        return '[' . implode(',', $items) . ']';
    }

}
