<?php
/**
 * Copyright © FedEX, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\EnhancedProfile\Controller\Account;

use Magento\Framework\App\ActionInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\Company\Model\AdditionalDataFactory;
use Magento\Framework\App\RequestInterface;

class SetNonEditablePaymentMethod implements ActionInterface
{
    /**
     * Initialize dependencies.
     *
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     * @param AdditionalDataFactory $additionalDataFactory
     * @param RequestInterface $request
     */
    public function __construct(
        protected JsonFactory $jsonFactory,
        protected LoggerInterface $logger,
        protected AdditionalDataFactory $additionalDataFactory,
        protected RequestInterface $request
    )
    {
    }

    public function execute()
    {
        $response = [];
        $requestData = $this->request->getParams();
        try {
            if ($requestData) {
                $nonEditablePaymentMethod = $requestData['non_editable_payment_method'];
                $companyId = $requestData['company_id'];
                $compAddDataCollection = $this->additionalDataFactory->create()
                    ->getCollection()
                    ->addFieldToSelect('*')
                    ->addFieldToFilter('company_id', ['eq' => $companyId])
                    ->getFirstItem();

                if ($compAddDataCollection) {
                    $compAddDataCollection->setData('is_non_editable_cc_payment_method', $nonEditablePaymentMethod);
                    $compAddDataCollection->save();
                    $this->logger->info(__METHOD__ . ':' . __LINE__. ' Non-editable payment method saved successfully');
                    $response = ['status' => 'success', 'message' => 'Non-editable payment method saved successfully.'];
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ' Error while saving non-editable payment method: ' . $e->getMessage()
            );
            $response = ['status' => 'error', 'message' => 'Error while saving non-editable payment method.'];
        }

        $result = $this->jsonFactory->create();
        $result->setData($response);

        return $result;
    }
}
