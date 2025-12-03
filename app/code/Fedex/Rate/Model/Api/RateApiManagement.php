<?php

declare(strict_types=1);
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Rate\Model\Api;

use Fedex\Catalog\Model\Config;
use Fedex\Company\Helper\Data;
use Fedex\EnhancedProfile\Helper\Account;
use Fedex\Rate\Api\RateApiManagementInterface;
use Fedex\Rate\Helper\ApiRequest;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;
use function PHPUnit\Framework\arrayHasKey;

class RateApiManagement implements RateApiManagementInterface
{
    /**
     * RateApiManagement construct.
     *
     * @param Request $request
     * @param ApiRequest $apiRequest
     * @param Json $json
     * @param LoggerInterface $logger
     * @param Account $accountHelper
     * @param Data $companyHelper
     * @param Config $catalogConfig
     */
    public function __construct(
        protected Request $request,
        protected ApiRequest $apiRequest,
        private Json $json,
        protected LoggerInterface $logger,
        protected Account $accountHelper,
        protected Data $companyHelper,
        protected Config $catalogConfig
    )
    {
    }

    /**
     * @api
     * @return string
     */
    public function rateProduct()
    {
        try {
            $content = $this->request->getContent();
            if (($this->accountHelper->getIsSdeStore() || $this->accountHelper->getIsSelfRegStore())) {
                $fedexAccountNumber = $this->retrieveFedexAccountForCustomer();
                if ($fedexAccountNumber) {
                    $content = $this->addFedexAccountToPriceRequest($content, $fedexAccountNumber);
                }
            }
            $response = $this->apiRequest->priceProductApi($content);
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Unable to process request: ' . $e->getMessage());
            $response['error'] = __('Unable to process request');
            $response['message'] = $e->getMessage();
            $response['status'] = false;
        }

        return $this->json->serialize($response);
    }

    private function retrieveFedexAccountForCustomer()
    {
        $customerAccount = $this->companyHelper->getFedexAccountNumber() ?? false;
        if (!$customerAccount) {
            $personalAccountList = $this->accountHelper->getActivePersonalAccountList('payment');
            $customerAccount = array_search(
                1,
                array_column($personalAccountList, 'selected', 'account_number')
            );
        }

        return $customerAccount;
    }

    private function addFedexAccountToPriceRequest($content, $fedexAccountNumber)
    {
        $unserializedContent = $this->json->unserialize($content);
        if ($fedexAccountNumber && is_array($unserializedContent)
            && key_exists('rateRequest', $unserializedContent)) {
            $unserializedContent['rateRequest']['fedExAccountNumber'] = $fedexAccountNumber;
            $content = $this->json->serialize($unserializedContent);
        }

        return $content;
    }
}
