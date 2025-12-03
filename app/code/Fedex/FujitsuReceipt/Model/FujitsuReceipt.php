<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\FujitsuReceipt\Model;

use Fedex\FujitsuReceipt\Model\TransactionReceiptApi\FujitsuTransactionReceiptApiHandler;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * FujitsuReceipt Model
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class FujitsuReceipt
{
    /**
     * FujitsuReceipt constructor
     *
     * @param FujitsuTransactionReceiptApiHandler $apiHandler
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected FujitsuTransactionReceiptApiHandler $apiHandler,
        protected ToggleConfig                        $toggleConfig
    ) {
    }

    /**
     * Send Fujitsu Receipt Confirmation Email
     *
     * @param array $orderData
     * @return string|bool
     */
    public function sendFujitsuReceiptConfirmationEmail($orderData): bool|string
    {
        $newFijtsuToggle = $this->toggleConfig->getToggleConfigValue('new_fujitsu_receipt_approach');
        if (!$newFijtsuToggle) {
            $fujitsuReceiptRequestData = $this->apiHandler->prepareFujitsuReceiptApiRequestData($orderData);
            $responseData = $this->apiHandler->callCurlPost($fujitsuReceiptRequestData);

            if ($responseData && empty($responseData['error']) && empty($responseData['errors'])) {
                return $responseData["output"]["transactionReceipt"]["retailTransactionId"];
            }
        }

        return false;
    }
}
