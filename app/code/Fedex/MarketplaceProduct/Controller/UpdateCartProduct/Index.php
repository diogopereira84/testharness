<?php


/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves
 */

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Controller\UpdateCartProduct;

use Fedex\Cart\Model\Quote\ThirdPartyProduct\Update;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Quote\Model\ResourceModel\QuoteItemRetriever;
use Fedex\MarketplaceProduct\Model\Context;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Index implements ActionInterface, HttpPostActionInterface
{
    public const MARKETPLACE_REORDER_TOGGLE = 'hawks_D224800_reorder_toggle';

    /**
     * @param Context $context
     * @param QuoteItemRetriever $quoteItemRetriever
     * @param Update $update
     * @param ProductRepositoryInterface $productRepository
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private Context $context,
        private QuoteItemRetriever $quoteItemRetriever,
        private Update $update,
        private ProductRepositoryInterface $productRepository,
        private ToggleConfig $toggleConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $requestData = $this->context->getRequestInterface()->getParams();
        $resultRedirect = $this->context->getRedirectFactory()->create();

        try {

            if (!isset($requestData['supplierPartAuxiliaryID'])) {
                $this->context->getManagerInterface()->addErrorMessage(__('An error occurred'));
                return $resultRedirect->setPath('checkout/cart');
            }

            if (isset($requestData['action']) && $requestData['action'] == 'cancel') {
                return $resultRedirect->setPath('checkout/cart');
            }

            $quoteItemId = (int)$this->context->getRequestInterface()->getParam('quote_item_id');
            $isReorder = $this->toggleConfig->getToggleConfigValue(self::MARKETPLACE_REORDER_TOGGLE);

            if (!$quoteItemId) {
                throw new \InvalidArgumentException((string)__('Quote Item Id not found. '));
            }

            $quoteItem = $this->quoteItemRetriever->getById($quoteItemId);

            if (!$quoteItem) {
                throw new \InvalidArgumentException((string)__('Quote Item not found.'));
            }

            $quoteItem = $this->update->updateThirdPartyItemSellerPunchout($quoteItem);

            $quoteItem->save();
            return $resultRedirect->setPath('checkout/cart');
        } catch (\Exception $e) {
            $this->context->getLogger()->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $this->context->getLogger()->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getTraceAsString());
            $this->context->getManagerInterface()->addErrorMessage(__('An error occurred'));

            $product = $this->productRepository->get($requestData['sku']);
            return $resultRedirect->setPath($product->getUrlModel()->getUrl($product));
        }
    }
}
