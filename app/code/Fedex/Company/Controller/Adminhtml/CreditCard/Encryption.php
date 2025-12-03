<?php
/**
 * Copyright © FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Company\Controller\Adminhtml\CreditCard;

use Fedex\Delivery\Model\CreditCard\EncryptionHandler;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * B-1205796 : API integration for CC details and Billing details in Magento Admin
 */
class Encryption implements ActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Fedex_Delivery::creditcard';

    /**
     * Encryption Constructor
     *
     * @param JsonFactory $resultJsonFactory
     * @param EncryptionHandler $encryptionHandler
     * @return void
     */
    public function __construct(
        protected JsonFactory $resultJsonFactory,
        protected EncryptionHandler $encryptionHandler
    )
    {
    }

    /**
     * Get encryption key data and returns the response in JSON format
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $encryptionKey = $this->encryptionHandler->getEncryptionKey();

        return $this->resultJsonFactory->create()->setData($encryptionKey);
    }
}
