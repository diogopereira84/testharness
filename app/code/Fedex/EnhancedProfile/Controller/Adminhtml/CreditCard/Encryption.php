<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\EnhancedProfile\Controller\Adminhtml\CreditCard;

use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;

/**
 * B-1205796 : API integration for CC details and Billing details in Magento Admin
 */
class Encryption implements ActionInterface
{
    /**
     * Error status constant
     */
    public const ERROR = 'error';

    /**
     * Error message constant
     */
    public const SYSTEM_ERROR = 'Something went wrong with the request, Please try again later.';

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Fedex_EnhancedProfile::creditcard';

    /**
     * Encryption constructor
     *
     * @param JsonFactory $jsonFactory
     * @param EnhancedProfile $enhancedProfile
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @return void
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
     * Encrypt credit card data and get token
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $response = [];
        $ccFormData = $this->request->getParams();
        if (!empty($ccFormData)) {
            $ccFormData['requestId'] = uniqid();
            if ($postFields = $this->enhancedProfile->prepareCreditCardTokensJson($ccFormData)) {
                $endPointUrl = $this->enhancedProfile->getConfigValue(EnhancedProfile::CREDIT_CARD_TOKENS);
                $apiResponse = $this->enhancedProfile->apiCall('POST', $endPointUrl, $postFields);
                if (isset($apiResponse->errors)) {
                    $response['status'] = self::ERROR;
                    $response['message'] = reset($apiResponse->errors)->message;
                } elseif (isset($apiResponse->output->alerts)) {
                    $response['status'] = self::ERROR;
                    $response['message'] = reset($apiResponse->output->alerts)->message;
                } elseif (empty($apiResponse) || !isset($apiResponse->output->creditCardToken->token)) {
                    $response['status'] = self::ERROR;
                    $response['message'] = self::SYSTEM_ERROR;
                } else {
                    $response['info'] = $apiResponse;
                    $response['status'] = true;
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
