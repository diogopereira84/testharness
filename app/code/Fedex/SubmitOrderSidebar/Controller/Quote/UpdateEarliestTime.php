<?php

namespace Fedex\SubmitOrderSidebar\Controller\Quote;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Fedex\Delivery\Helper\Data;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\CartFactory;

class UpdateEarliestTime implements HttpPostActionInterface
{
    public function __construct(
        private JsonFactory $resultJsonFactory,
        protected CheckoutSession $checkoutSession,
        private CartRepositoryInterface $quoteRepository,
        private LoggerInterface $logger,
        private RequestInterface $request,
        protected Data $helper,
        protected CartFactory $cartFactory
    ) {}

    /**
     * Execute method to update the earliest pickup time
     */
    public function execute(): \Magento\Framework\Controller\Result\Json
    {
        $resultJson = $this->resultJsonFactory->create();
        if (!$this->helper->isPromiseTimeWarningtoggleEnabled()) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Feature disabled.')
            ]);
        }
        try {
            $pickupTime = $this->request->getParam('data');
            if (empty($pickupTime)) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Invalid pickup time.')
                ]);
            }
            $quote = $this->cartFactory->create()->getQuote();
            $quote->setData('estimated_pickup_time', $pickupTime);
            $this->quoteRepository->save($quote);
            return $resultJson->setData([
                'success' => true,
                'message' => __('Pickup time updated successfully.')
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Error updating pickup time.')
            ]);
        }
    }
}
