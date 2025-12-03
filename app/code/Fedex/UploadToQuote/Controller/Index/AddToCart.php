<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Controller\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\UploadToQuote\Helper\AddToCartHelper;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Fedex\UploadToQuote\Helper\QuoteEmailHelper;
use Fedex\FuseBiddingQuote\Helper\RateQuoteHelper;
use Magento\Quote\Model\QuoteFactory;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;

/**
 * Add Quote Item to Cart Controller
 */
class AddToCart implements ActionInterface
{
    /**
     * AddToCart class constructor
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param AddToCartHelper $addToCartHelper
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     * @param QuoteEmailHelper $quoteEmailHelper
     * @param RateQuoteHelper $rateQuoteHelper
     * @param QuoteFactory $quoteFactory
     * @param FuseBidViewModel $fuseBidViewModel
     */
    public function __construct(
        protected Context $context,
        protected StoreManagerInterface $storeManager,
        protected AddToCartHelper $addToCartHelper,
        protected JsonFactory $jsonFactory,
        protected LoggerInterface $logger,
        protected QuoteEmailHelper $quoteEmailHelper,
        protected RateQuoteHelper $rateQuoteHelper,
        protected QuoteFactory $quoteFactory,
        protected FuseBidViewModel $fuseBidViewModel
    )
    {
    }

    /**
     * Add quote item to cart
     *
     * @return json
     */
    public function execute()
    {
        $post = $this->context->getRequest()->getPostValue();
        $storeId = $this->storeManager->getStore()->getId();
        $response = [
            "status" => 200,
            'isItemAdded' => false,
            'message' => 'System error, Please try again.'
        ];
        try {
            if (isset($post['quoteId']) && $post['quoteId'] && $storeId) {
                $quoteId = $post['quoteId'];
                $quote = $this->quoteFactory->create()->load($quoteId);
                $isItemAddableToCart = true;
                if ($quote->getIsBid() && $this->fuseBidViewModel->isFuseBidToggleEnabled()
                && $this->fuseBidViewModel->isRateQuoteDetailApiEnabed()) {
                    $rateQuoteDetailsAprError = '';
                    $fjmpQuoteId = $quote->getFjmpQuoteId();
                    $rageQuoteDetails = $this->rateQuoteHelper->getRateQuoteDetails($fjmpQuoteId);
                    if ($rageQuoteDetails) {
                        $isItemAddableToCart = $rageQuoteDetails['isApiCallSucceed'];
                        $rateQuoteDetailsAprError = $rageQuoteDetails['message'];
                    }
                    $response = [
                        "status" => 200,
                        'isItemAdded' => false,
                        'message' => 'System error, Please try again.',
                        'rate_quote_details_api_error' => $rateQuoteDetailsAprError
                    ];
                }
                if ($isItemAddableToCart) {
                    $isCartActivated = $this->addToCartHelper->addQuoteItemsToCart($storeId, $quoteId);
                    $response = [
                        "status" => 200,
                        'isItemAdded' => false,
                        'message' => 'Order against this quote is already submitted.'
                    ];
                    if ($isCartActivated) {
                        $response = [
                            "status" => 200,
                            'isItemAdded' => true,
                            'message' => 'Quote item(s) are added to cart.'
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $this->addToCartHelper->deactivateQuote($quoteId);
            $this->logger->error(__METHOD__ . ':' . __LINE__
            .': Quote items are not added to cart : ' . $e->getMessage());
        }

        $result = $this->jsonFactory->create();
        $result->setData($response);

        return $result;
    }
}
