<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\CreditCard;

use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;

class Encryption implements ActionInterface
{
    public const ERROR = 'error';
    public const SYSTEM_ERROR = 'System error, Please try again.';

    /**
     * Initialize dependencies.
     *
     * @param JsonFactory $jsonFactory
     * @param EnhancedProfile $enhancedProfile
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     */
    public function __construct(
        protected JsonFactory $jsonFactory,
        protected EnhancedProfile $enhancedProfile,
        protected LoggerInterface $logger,
        protected RequestInterface $request
    )
    {
    }

    /**
     * Remove credit cart information
     *
     * @return json
     */
    public function execute()
    {
        $ccFormData = $this->request->getParams();
        $response = [];
        if ($ccFormData) {
            $ccFormData['requestId'] = uniqid();
            $postFields = $this->enhancedProfile->prepareCreditCardTokensJson($ccFormData);
            if ($postFields) {
                $endPointUrl = $this->enhancedProfile->getConfigValue(EnhancedProfile::CREDIT_CARD_TOKENS);
                $apiResponse = $this->enhancedProfile->apiCall('POST', $endPointUrl, $postFields);
                if (!isset($apiResponse->errors)) {
                    $response['info'] = $apiResponse;
                    $response['status'] = true;
                } else {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Credit Card Encryption Error Response: '
                    . var_export($apiResponse, true));
                    $response['status'] = self::ERROR;
                    $response['info'] = $apiResponse;
                    $response['message'] = false;
                }
            } else {
                $response['status'] = self::ERROR;
                $response['message'] = self::SYSTEM_ERROR;
            }
        } else {
            $response['status'] = self::ERROR;
            $response['message'] = self::SYSTEM_ERROR;
        }
        $result = $this->jsonFactory->create();
        $result->setData($response);

        return $result;
    }
}
