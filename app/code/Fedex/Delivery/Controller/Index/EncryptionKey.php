<?php

/**
 * Copyright © FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Controller\Index;

use Fedex\Delivery\Model\CreditCard\EncryptionHandler;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class EncryptionKey implements ActionInterface
{    
    /**
     * EncryptionKey class Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param EncryptionHandler $encryptionHandler
     * @return void
     */
    public function __construct(
        protected Context $context,
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
