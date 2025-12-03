<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Plugin\Controller\Sidebar;

use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Magento\Checkout\Controller\Sidebar\UpdateItemQty as Subject;
use Magento\Checkout\Model\Sidebar;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data;
use Magento\Quote\Model\Quote\Item;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Exception;
use Magento\Checkout\Model\Cart;

class UpdateItemQtyPlugin
{
    /**
     * UpdateItemQtyPlugin constructor
     *
     * @param Context $context
     * @param Sidebar $sidebar
     * @param LoggerInterface $logger
     * @param Data $jsonHelper
     * @param Item $quoteItem
     * @param StockRegistryInterface $stockRegistry
     * @param ToggleConfig $toggleConfig
     * @param Cart $cart
     * @param MarketplaceCheckoutHelper $marketplaceCheckoutHelper
     */
    public function __construct(
        protected Context $context,
        protected Sidebar $sidebar,
        protected LoggerInterface $logger,
        protected Data $jsonHelper,
        protected Item $quoteItem,
        protected StockRegistryInterface $stockRegistry,
        protected ToggleConfig $toggleConfig,
        private readonly Cart $cart,
        private MarketplaceCheckoutHelper $marketplaceCheckoutHelper
    )
    {
    }

    /**
     * After Execute
     *
     * @param Subject $subject
     * @param Http $result
     * @return Http
     */
    public function afterExecute(Subject $subject, Http $result)
    {
        $isEssendantToggleEnabled =
            $this->marketplaceCheckoutHelper->isEssendantToggleEnabled();
        if ($this->toggleConfig->getToggleConfigValue('explores_remove_adobe_commerce_override')) {
            try {
                $itemId = (int)$subject->getRequest()->getParam('item_id');
                $itemQty = $subject->getRequest()->getParam('item_qty') * 1;
                $productData = $this->quoteItem->load($itemId);
                $stockManager = $this->stockRegistry->getStockItem($productData->getProductId());
                $maxQtyForProduct = $stockManager->getMaxSaleQty();
                $error = $this->validateProductMaxQty($itemQty, $maxQtyForProduct);
                if($isEssendantToggleEnabled && $productData->getMiraklOfferId()){
                    $this->update3pAdditionalData($productData,$itemQty);
                }
                return $this->jsonResponse($error);
            } catch (LocalizedException $e) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                return $this->jsonResponse($e->getMessage());
            } catch (Exception $e) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                return $this->jsonResponse($e->getMessage());
            }
        } else {
            return $result;
        }
    }

    /**
     * Compile JSON response
     *
     * @param string $error
     * @return Http
     */
    protected function jsonResponse($error = '')
    {
        return $this->context->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($this->sidebar->getResponseData($error))
        );
    }

    /**
     * Validate Product Max Qty
     *
     * @param int $itemQty
     * @param int $maxQtyForProduct
     * @return string
     */
    protected function validateProductMaxQty($itemQty, $maxQtyForProduct)
    {
        $error = '';
        if ($itemQty > $maxQtyForProduct) {
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ . ' Item quantity is greater than max quantity allowed.'
            );
            $error = 'Max Qty Allowed : ' . $maxQtyForProduct;
        }

        return $error;
    }
    /**
     * @param $quoteItem
     * @param $quantity
     * @return void
     * @throws Exception
     */
    protected function update3pAdditionalData($quoteItem,$quantity): void
    {
        $item = $this->cart->getQuote()->getItemById($quoteItem->getItemId());
        if ($item->getAdditionalData()) {
            $additionalData = (array) json_decode($item->getAdditionalData());
            $additionalData['quantity'] = (int) $quantity;
            $additionalData['total'] = (double) $item->getBaseRowTotal();
            $item->setAdditionalData(json_encode($additionalData));
            $item->save();
        }
    }
}
