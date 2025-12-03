<?php
declare(strict_types=1);
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\AccountValidation\Controller\Adminhtml\Account;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Fedex\AccountValidation\Model\AccountValidation;

class Validateaccount implements ActionInterface
{
    /**
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param AccountValidation $accountValidation
     */
    public function __construct(
        protected JsonFactory $jsonFactory,
        protected LoggerInterface $logger,
        protected RequestInterface $request,
        protected AccountValidation $accountValidation
    ) {
    }

    /**
     * Validate account
     *
     * @return Json
     */
    public function execute(): Json
    {
        $response = ['status' => false];
        try {
            $printAccountNumber = $this->request->getParam('fxo-account-number') ?? '';
            $discountAccountNumber = $this->request->getParam('fxo-discount-account-number') ?? '';
            $shippingAccountNumber = $this->request->getParam('fxo-shipping-account-number') ?? '';
            $response = $this->accountValidation->validateAccount($printAccountNumber, $discountAccountNumber, $shippingAccountNumber);
        } catch (\Exception $e) {
            $this->logger->error('Error during account validation: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            $response['error'] = $e->getMessage();
        }
        $result = $this->jsonFactory->create();
        $result->setData($response);
        return $result;
    }
}
