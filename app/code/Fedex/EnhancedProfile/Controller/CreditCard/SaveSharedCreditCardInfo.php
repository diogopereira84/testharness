<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\CreditCard;

use Fedex\Recaptcha\Model\Validator;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Psr\Log\LoggerInterface;
use Fedex\EnhancedProfile\ViewModel\CompanyPaymentData;
use Magento\Framework\App\RequestInterface;

class SaveSharedCreditCardInfo implements ActionInterface
{
    public const ERROR_AUTH = 'auth_failed';
    public const NICK_NAME_STATUS = 'nick_name_status';
    public const ERROR = 'error';
    public const SYSTEM_ERROR = 'System error, Please try again.';
    public const SHARED_CC_RECAPTCHA = 'shared_cc';

    /**
     * Initialize dependencies.
     *
     * @param JsonFactory $jsonFactory
     * @param EnhancedProfile $enhancedProfile
     * @param LoggerInterface $logger
     * @param CompanyPaymentData $companyPaymentData
     * @param Validator $recaptchaValidator
     * @param RequestInterface $request
     */
    public function __construct(
        protected JsonFactory $jsonFactory,
        protected EnhancedProfile $enhancedProfile,
        protected LoggerInterface $logger,
        protected CompanyPaymentData $companyPaymentData,
        protected Validator $recaptchaValidator,
        protected RequestInterface $request
    )
    {
    }

    /**
     * Add/Update Credit Card Information
     *
     * @return json
     */
    public function execute()
    {
        $ccFormData = $this->request->getParams();
        try {
            if ($this->recaptchaValidator->isRecaptchaEnabled(self::SHARED_CC_RECAPTCHA)) {
                $recaptchaValidation = $this->recaptchaValidator->validateRecaptcha(
                    self::SHARED_CC_RECAPTCHA
                );
                if(is_array($recaptchaValidation)) {
                    $result = $this->jsonFactory->create();
                    $result->setData($recaptchaValidation);

                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Error while saving the shared credit card info: Recaptcha Error');
                    return $result;
                }
            }
            $response = [];
            $ccFormData['requestId'] = uniqid();
            $ccFormData['nameOnCard'] = $ccFormData['cardHolderName'];
            $postFields = $this->enhancedProfile->prepareCreditCardTokensJson($ccFormData);
            $endPointUrl = $this->enhancedProfile->getConfigValue(EnhancedProfile::CREDIT_CARD_TOKENS);
            $apiResponse = $this->enhancedProfile->apiCall('POST', $endPointUrl, $postFields);
            $response = $this->validateResponse($apiResponse, $ccFormData);
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Error while saving the shared credit card info: '
            . $e->getMessage());
            $response['status'] = self::ERROR;
            $response['message'] = self::SYSTEM_ERROR;
        }
        $result = $this->jsonFactory->create();
        $result->setData($response);

        return $result;
    }

    /**
     * Validate credit card api response
     *
     * @param array|string|object|null $apiResponse
     * @param array $ccFormData
     * @return array
     */
    public function validateResponse($apiResponse, $ccFormData)
    {
        if (!isset($apiResponse->errors)) {
            if (isset($apiResponse->output->creditCardToken)) {
                $ccFormData['creditCardToken'] = $apiResponse->output->creditCardToken->token;
                $ccFormData['tokenExpirationDate'] = $apiResponse->output->creditCardToken->expirationDateTime;
                $response = $this->addCreditCard($ccFormData);
            } else {
                $this->logger->error(
                    __METHOD__ . ':' . __LINE__ . 'Shared credit card encryption error response: '
                    . var_export($apiResponse, true)
                );
                $response['status'] = self::ERROR_AUTH;
                $response['info'] = $apiResponse;
                $response['message'] = false;
            }
        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Shared credit card error response: '
            . var_export($apiResponse, true));
            $response['status'] = self::ERROR;
            $response['info'] = $apiResponse;
            $response['message'] = false;
        }

        return $response;
    }

    /**
     * Add Credit Card Information
     *
     * @param array $ccFormData
     * @return array
     */
    public function addCreditCard($ccFormData)
    {
        $response = [];

        $saveCreditCardStatus = $this->saveCreditDetailsforCompany($ccFormData);

        if ($saveCreditCardStatus) {
            $response['info'] = $this->companyPaymentData->makeCreditCardViewHtml();
            $response['ccData'] = json_encode($this->companyPaymentData->getCompanyCcData());
            $response['status'] = false;
            $response['message'] = __("Payment method has been successfully added.");
        } else {
            $response['status'] = self::ERROR;
            $response['message'] = self::SYSTEM_ERROR;
        }

        return $response;
    }

    /**
     * Save company credit details
     *
     * @param array $ccFormData
     * @return array
     */
    public function saveCreditDetailsforCompany($ccFormData)
    {
        try {
            $companyObject = $this->companyPaymentData->getCompanyDataById();

            if (!$companyObject->isEmpty()) {

                $streetAddress = explode('||', $ccFormData['streetLines']);
                $ccData = [
                    "nameOnCard" => $ccFormData['cardHolderName'],
                    "ccNumber" => $ccFormData['maskedCreditCardNumber'],
                    "ccType" => $ccFormData['creditCardType'],
                    "ccExpiryMonth" => $ccFormData['expirationMonth'],
                    "ccExpiryYear" => $ccFormData['expirationYear'],
                    "addressLine1" => $streetAddress[0],
                    "addressLine2" => (!empty($streetAddress[1])) ? $streetAddress[1] : '',
                    "city" => $ccFormData['city'],
                    "state" => $ccFormData['stateOrProvinceCode'],
                    "country" => $ccFormData['countryCode'],
                    "zipCode" => $ccFormData['postalCode'],
                    'nickName' => $ccFormData['creditCardLabel'],
                    'ccCompanyName' => $ccFormData['company']
                ];

                $ccData = json_encode($ccData);
                $ccToken = $ccFormData['creditCardToken'];
                $ccExpiryTime = $ccFormData['tokenExpirationDate'];

                $companyObject->setCcToken($ccToken);
                $companyObject->setCcData($ccData);
                $companyObject->setCcTokenExpiryDateTime($ccExpiryTime);
                $companyObject->save();

                return true;
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Error while saving the shared credit card info: '
            . $e->getMessage());

            return false;
        }
    }
}
