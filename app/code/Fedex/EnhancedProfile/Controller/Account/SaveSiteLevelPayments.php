<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
*/

namespace Fedex\EnhancedProfile\Controller\Account;

use Fedex\Recaptcha\Model\Validator;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Fedex\EnhancedProfile\ViewModel\CompanyPaymentData;
use Magento\Framework\App\RequestInterface;
use Fedex\Company\Model\Config\Source\PaymentOptions;
use Magento\Company\Api\CompanyRepositoryInterface;
use Fedex\EnvironmentManager\Model\Config\NdcAccountStripsAfterSaveCreditDetailsToggle;

/**
 * SaveSiteLevelPayments Controller class
 */
class SaveSiteLevelPayments implements ActionInterface
{
    public const ERROR_AUTH = 'auth_failed';
    public const ERROR = 'error';
    public const SYSTEM_ERROR = 'System error, Please try again.';

    /**
     * Initialize dependencies.
     *
     * @param JsonFactory $jsonFactory
     * @param EnhancedProfile $enhancedProfile
     * @param LoggerInterface $logger
     * @param CompanyPaymentData $companyPaymentData
     * @param Validator $recaptchaValidator
     * @param RequestInterface $request
     * @param Json $json
     * @param CompanyRepositoryInterface $companyRepository
     * @param NdcAccountStripsAfterSaveCreditDetailsToggle $ndcAccountStripsAfterSaveCreditDetailsToggle
     */
    public function __construct(
        protected JsonFactory $jsonFactory,
        protected EnhancedProfile $enhancedProfile,
        protected LoggerInterface $logger,
        protected CompanyPaymentData $companyPaymentData,
        protected Validator $recaptchaValidator,
        protected RequestInterface $request,
        protected Json $json,
        protected CompanyRepositoryInterface $companyRepository,
        readonly NdcAccountStripsAfterSaveCreditDetailsToggle $ndcAccountStripsAfterSaveCreditDetailsToggle
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
        $requestParams = $this->request->getParams();
        $toggleSaveFlag = true;
        try {
            $response = [];
            if (!empty($requestParams['creditCardDataParams'])) {
                $ccFormData = $requestParams['creditCardDataParams'];
                $ccFormData['requestId'] = uniqid();
                $ccFormData['nameOnCard'] = $ccFormData['cardHolderName'];
                $postFields = $this->enhancedProfile->prepareCreditCardTokensJson($ccFormData);
                $endPointUrl = $this->enhancedProfile->getConfigValue(EnhancedProfile::CREDIT_CARD_TOKENS);
                $apiResponse = $this->enhancedProfile->apiCall('POST', $endPointUrl, $postFields);
                $response = $this->validateResponse($apiResponse, $ccFormData);
            }
            if (!empty($requestParams['creditCardDataParams']) &&
                isset($response['status']) && $status = $response['status']) {
                if ($status === self::ERROR_AUTH || $status === self::ERROR) {
                    $toggleSaveFlag = false;
                }
            }
            if ($toggleSaveFlag) {
                $companyObject = $this->companyPaymentData->getCompanyDataById();
                if ($companyObject) {
                    $companyPaymentOptions = [];
                    if($requestParams['creditCardToggle'] == 1) {
                        $companyPaymentOptions[] = PaymentOptions::CREDIT_CARD;
                    }
                    if($requestParams['fedexAccountToggle'] == 1) {
                        $companyPaymentOptions[] = PaymentOptions::FEDEX_ACCOUNT_NUMBER;
                    }
                    $saveAccountNumberFlag = false;
                    $company = $this->companyRepository->get((int) $companyObject->getCompanyId());
                    if (isset($requestParams['shipFedexAccountToggle'])) {
                        $saveAccountNumberFlag = true;
                        $company->setData('shipping_account_number_editable',
                            $requestParams['shipFedexAccountToggle']);
                    }
                    if (isset($requestParams['printFedexAccountToggle'])) {
                        $saveAccountNumberFlag = true;
                        $company->setData('fxo_account_number_editable',
                            $requestParams['printFedexAccountToggle']);
                    }
                    if (isset($requestParams['shipFedexAccount'])) {
                        $saveAccountNumberFlag = true;
                        $company->setData('shipping_account_number',
                            $requestParams['shipFedexAccount']);
                    }

                    // Fix toggle of D-209098: Saving a new site level credit card strips the NDC from the site
                    if ($this->ndcAccountStripsAfterSaveCreditDetailsToggle->isActive()) {
                        if (isset($requestParams['printFedexAccount']) &&
                        $company->getData('discount_account_number') != $requestParams['printFedexAccount']
                        ) {
                            $saveAccountNumberFlag = true;
                            $company->setData('fedex_account_number',
                                $requestParams['printFedexAccount']);
                        }
                    } else {
                        if (isset($requestParams['printFedexAccount']) &&
                        $company->getData('discount_account_number') != $requestParams['printFedexAccount']
                        ) {
                        $saveAccountNumberFlag = true;
                        $company->setData('fedex_account_number',
                            $requestParams['printFedexAccount']);
                        $company->setData('discount_account_number',
                            null);
                    }
                    }

                    if (isset($requestParams['discountFedexAccount']) && $requestParams['discountFedexAccount'] != null) {
                        $saveAccountNumberFlag = true;
                        $company->setData('discount_account_number',
                            $requestParams['discountFedexAccount']);
                        // Fix toggle of D-209098 Saving a new site level credit card strips the NDC from the site
                        if (!$this->ndcAccountStripsAfterSaveCreditDetailsToggle->isActive()) {
                            $company->setData('fedex_account_number', null);
                        }
                    }
                    if ($requestParams['removePrintFedexAccount'] == 1) {
                        $saveAccountNumberFlag = true;
                        $company->setData('fedex_account_number', '');
                        $company->setData('fxo_account_number_editable', 0);
                    }
                    if ($requestParams['removeShipFedexAccount'] == 1) {
                        $saveAccountNumberFlag = true;
                        $company->setData('shipping_account_number', '');
                        $company->setData('shipping_account_number_editable', 0);
                    }
                    if ($saveAccountNumberFlag) {
                        $company->save();
                    }
                    if($requestParams['removeCreditCard'] == 1) {
                        $companyObject->setCcToken(null);
                        $companyObject->setCcData(null);
                        $companyObject->setCcTokenExpiryDateTime(null);
                        $companyObject->setIsNonEditableCcPaymentMethod(0);
                    }
                    $companyObject->setCompanyPaymentOptions($this->json->serialize($companyPaymentOptions));
                    $companyObject->save();
                    $response['status'] = false;
                    $response['message'] = __("Site level payment method has been saved successfully.");
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Error while saving site level payments: '
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
                    __METHOD__ . ':' . __LINE__ . 'Site level credit card encryption error response: '
                    . var_export($apiResponse, true)
                );
                $response['status'] = self::ERROR_AUTH;
                $response['info'] = $apiResponse;
                $response['message'] = false;
            }
        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Site level payments error response: '
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
            $response['info'] = $this->companyPaymentData->siteLevelCreditCardViewHtml();
            $response['ccData'] = json_encode($this->companyPaymentData->getCompanyCcData());
            $response['status'] = false;
            $response['message'] = __("Site level payment method has been saved successfully.");
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
                $companyObject->setIsNonEditableCcPaymentMethod($ccFormData['nonEditableCcPayment']);
                $companyObject->save();

                return true;
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Error while saving the site level payemnts info: '
                . $e->getMessage());

            return false;
        }
    }
}
