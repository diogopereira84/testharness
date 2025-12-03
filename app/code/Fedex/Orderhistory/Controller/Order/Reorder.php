<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Orderhistory\Controller\Order;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\RequestInterface;
use Fedex\Orderhistory\Model\Reorder\Reorder as ReorderModel;
use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Magento\Checkout\Helper\Cart;
use Fedex\InBranch\Model\InBranchValidation;

class Reorder extends \Magento\Framework\App\Action\Action
{
    /**
     * Constructor initialize.
     *
     * @param Context $context
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     * @param ReorderModel $reorderModel
     * @param CheckoutSession $checkoutSession
     * @param LoggerInterface $logger
     * @param CartDataHelper $cartDataHelper
     * @param Cart $cartData
     */
    public function __construct(
        Context $context,
        protected RequestInterface $request,
        protected JsonFactory $resultJsonFactory,
        private ReorderModel $reorderModel,
        private CheckoutSession $checkoutSession,
        protected LoggerInterface $logger,
        protected CartDataHelper $cartDataHelper,
        protected Cart $cartData,
        private InBranchValidation $inBranchValidation
    ) {
        parent::__construct($context);
    }

    /**
     * Execute reorder action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $reorderData = $this->request->getContent();
        $reorderDatas = json_decode($reorderData, true);
        $resultJsonData = $this->resultJsonFactory->create();
        $totalCartItemCount = $this->cartData->getItemsCount() + count($reorderDatas);
        $cartItemConf = $this->cartDataHelper->getMaxCartLimitValue();
        $cartItemLimitNum = (int) $cartItemConf['maxCartItemLimit'];
        //Inbranch Implementation
        $isInBranchProductExist = $this->inBranchValidation->isInBranchValidReorder($reorderDatas);
        if ($isInBranchProductExist) {
            return $resultJsonData->setData(['isInBranchProductExist' => true]);
        }
        //Inbranch Implementation

        if ($totalCartItemCount > $cartItemLimitNum) {
            return $resultJsonData->setData(['status' => false, 'error' => 'cart_max_limit_exceeded']);
        }

        try {
            $reorderOutput = $this->reorderModel->execute($reorderDatas);
        } catch (LocalizedException $localizedException) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $localizedException->getMessage());
            return $resultJsonData->setData(
                [
                    'status' => false,
                    'error' => $localizedException->getMessage()
                ]
            );
        }

        $this->checkoutSession->setQuoteId($reorderOutput->getCart()->getId());
        $errors = $reorderOutput->getErrors();

        if (empty($errors)) {
            $result = ['status' => true, 'success' => count($reorderDatas)];
        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $errors[0]->getMessage());
            $result = ['status' => false, 'error' => $errors[0]->getMessage()];
        }

        return $resultJsonData->setData($result);
    }
}
