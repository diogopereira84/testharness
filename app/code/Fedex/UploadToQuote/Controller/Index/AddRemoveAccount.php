<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Controller\Index;

use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\FXOPricing\Model\FXORateQuote;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;

/**
 * Add or remove fedex account Controller
 */
class AddRemoveAccount implements ActionInterface
{
    /**
     * AddRemoveAccount class constructor
     *
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param CartFactory $cartFactory
     * @param CartDataHelper $cartDataHelper
     * @param FXORateQuote $fXORateQuote
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Context $context,
        protected CheckoutSession $checkoutSession,
        protected CartFactory $cartFactory,
        protected CartDataHelper $cartDataHelper,
        protected FXORateQuote $fxoRateQuote,
        protected JsonFactory $jsonFactory,
        protected LoggerInterface $logger
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
        $response = [
            "status" => 200,
            "isApplied" => false,
            'message' => 'Fedex account is not applied.'
        ];
        try {
            $accountNo = (string) $this->context->getRequest()->getParam('fedexAccount');
            $quote = $this->cartFactory->create()->getQuote();
            if ($accountNo) {
                $accountNo = $this->cartDataHelper->encryptData($accountNo);
                $quote->setData('fedex_account_number', $accountNo);
                $this->checkoutSession->setAppliedFedexAccNumber($accountNo);
                $this->checkoutSession->setAccountDiscountExist(true);
                $response = [
                    "status" => 200,
                    "isApplied" => true,
                    'message' => 'Fedex account is applied successfully.'
                ];
            } else {
                $quote->setData('fedex_account_number', '');
                if ($this->checkoutSession->getAccountDiscountExist()) {
                    $this->checkoutSession->unsAccountDiscountExist();
                }
                $this->checkoutSession->setRemoveFedexAccountNumber(true);
                $this->checkoutSession->setRemoveFedexAccountNumberWithSi(true);
                $response = [
                    "status" => 200,
                    "isRemoved" => true,
                    'message' => 'Fedex account is removed successfully.'
                ];
            }
            $this->fxoRateQuote->getFXORateQuote($quote);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__
            .': Fedex account is not add or remove : ' . $e->getMessage());
        }
        $result = $this->jsonFactory->create();
        $result->setData($response);

        return $result;
    }
}
