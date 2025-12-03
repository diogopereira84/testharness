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
use Magento\Customer\Model\Session;
use Magento\Framework\App\ResponseInterface;
use Fedex\Base\Helper\Auth as AuthHelper;
use Magento\Framework\App\RequestInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class SaveInfo implements ActionInterface
{
    public const ERROR_AUTH = 'auth_failed';
    public const NICK_NAME_STATUS = 'nick_name_status';
    public const ERROR = 'error';
    public const SYSTEM_ERROR = 'System error, Please try again.';
    public const PROFILE_CC_RECAPTCHA = 'profile_cc';
    public const TOGGLE_KEY ='MazeGeeks_D_183093_fix_for_JCB_CreditCard';

    /**
     * Initialize dependencies.
     *
     * @param JsonFactory $jsonFactory
     * @param EnhancedProfile $enhancedProfile
     * @param LoggerInterface $logger
     * @param Session $customerSession
     * @param ResponseInterface $response
     * @param Validator $recaptchaValidator
     * @param AuthHelper $authHelper
     * @param RequestInterface $request
     * @param ToggleConfig $toggleConfig
    */
    public function __construct(
        protected JsonFactory $jsonFactory,
        protected EnhancedProfile $enhancedProfile,
        protected LoggerInterface $logger,
        protected Session $customerSession,
        protected ResponseInterface $response,
        protected Validator $recaptchaValidator,
        protected AuthHelper $authHelper,
        protected RequestInterface $request,
        protected ToggleConfig $toggleConfig        
        
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
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Starting save credit card process');
        if ($this->recaptchaValidator->isRecaptchaEnabled(self::PROFILE_CC_RECAPTCHA)) {
            $recaptchaValidation = $this->recaptchaValidator->validateRecaptcha(self::PROFILE_CC_RECAPTCHA);
            if(is_array($recaptchaValidation)) {
                $result = $this->jsonFactory->create();
                $result->setData($recaptchaValidation);

                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Error while saving credit card info: Recaptcha Error');
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Save credit card process ended with Recaptcha Not Valid');
                return $result;
            }
        }

        $response = [];

            $validationKeyParam = (string) $this->request->getParam('loginValidationKey');

            if (
                $this->authHelper->isLoggedIn() &&
                $this->customerSession->getLoginValidationKey() === $validationKeyParam
            ) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . '
                    Save CC request processed with request header ' .
                    var_export($this->request->getHeaders()->toString(), true));

                $ccFormData = $this->request->getParams();
                try {
                    if (!$this->getNickNameStatus($ccFormData)) {
                        $ccFormData['requestId'] = uniqid();
                        $ccFormData['nameOnCard'] = $ccFormData['cardHolderName'];
                        if ($this->toggleConfig->getToggleConfigValue(self::TOGGLE_KEY) && $ccFormData['creditCardType']==='GENERIC')
                        {
                            $ccFormData['creditCardType'] = 'DISCOVER';
                        }
                        $postFields = $this->enhancedProfile->prepareCreditCardTokensJson($ccFormData);
                        $endPointUrl = $this->enhancedProfile->getConfigValue(EnhancedProfile::CREDIT_CARD_TOKENS);
                        $apiResponse = $this->enhancedProfile->apiCall('POST', $endPointUrl, $postFields);
                        if (!isset($apiResponse->errors)) {
                            if (isset($apiResponse->output->creditCardToken)) {
                                $ccFormData['creditCardToken'] = $apiResponse->output->creditCardToken->token;
                                $ccFormData['tokenExpirationDate'] = $apiResponse->output->creditCardToken->expirationDateTime;
                                $saveStatus = $ccFormData['saveStatus'];
                                if ($saveStatus) {
                                    $response = $this->updateCreditCard($ccFormData);
                                } else {
                                    $response = $this->addCreditCard($ccFormData);
                                }
                            } else {
                                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Credit Card Encryption Error Response: '
                                . var_export($apiResponse, true));
                                $response['status'] = self::ERROR_AUTH;
                                $response['info'] = $apiResponse;
                                $response['message'] = false;
                            }
                        } else {
                            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Credit Card Error Response: '
                            . var_export($apiResponse, true));
                            $response['status'] = self::ERROR;
                            $response['info'] = $apiResponse;
                            $response['message'] = false;
                        }
                    } else {
                        $response['status'] = self::NICK_NAME_STATUS;
                        $response['message'] = self::SYSTEM_ERROR;
                    }
                } catch (\Exception $e) {
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Error while saving credit card info: '
                    . $e->getMessage());
                    $response['status'] = self::ERROR;
                    $response['message'] = self::SYSTEM_ERROR;
                }
            } else {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . '
                    Unauthorized save CC request was rejected' .
                    var_export($this->request->getHeaders()->toString(), true));
                $this->response->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_FORBIDDEN);
                $this->response->sendHeaders();
                $this->response->setBody('Access Denied');
                $this->response->sendResponse();
            }

        $result = $this->jsonFactory->create();
        $result->setData($response);

        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Save credit card process ended:');
        return $result;
    }

    /**
     * Update Credit Card Information
     *
     * @param array $ccFormData
     * @return array
     */
    public function updateCreditCard($ccFormData)
    {
        $response = [];
        $profileCardId = $ccFormData['profileCreditCardId'];
        $cardRequestJson = $this->enhancedProfile->prepareUpdateCerditCardJson($ccFormData);
        $this->logger->info(__METHOD__ . ':' . __LINE__ .
        ' Update Credit Card Request: ' . var_export($cardRequestJson, true));
        $apiResponse = $this->enhancedProfile->updateCreditCard(
            $cardRequestJson,
            $profileCardId,
            'PUT'
        );
        if (!isset($apiResponse->errors)) {
            $this->enhancedProfile->setProfileSession();
            $cardInfo = $apiResponse->output;
            $cardHtml = $this->enhancedProfile->makeCreditCardHtml($cardInfo, true);
            $response['info'] = $cardHtml;
            $response['message'] = __("Credit card data has been successfully updated.");
            $response['status'] = true;
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Update Credit Card Response: '
            . var_export($apiResponse, true));
        } else {
            $this->logger->error(__METHOD__ . ':'. __LINE__ .
            ' Error(s) while saving credit card info: ' . var_export($apiResponse->errors, true));
            $response['status'] = self::ERROR;
            $response['message'] = $apiResponse->errors[0]->message;
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
        $cardRequestJson = $this->enhancedProfile->prepareAddCerditCardJson($ccFormData);
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Add Credit Card Request: '
        . var_export($cardRequestJson, true));
        $apiResponse = $this->enhancedProfile->saveCreditCard('POST', $cardRequestJson);
        if (!isset($apiResponse->errors)) {
            $isPayment = $this->enhancedProfile->setPreferredPaymentMethod();
            $this->enhancedProfile->setProfileSession();
            $cartInfo = $apiResponse->output;
            $cardHtml = $this->enhancedProfile->makeCreditCardHtml($cartInfo, false);
            $response['info'] = $cardHtml;
            $response['message'] = __("Credit card has been successfully added.");
            $response['status'] = false;
            $response['isPayment'] = $isPayment;
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Add Credit Card Response: '
            . var_export($apiResponse, true));
        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ' Error(s) while saving credit card info: ' . var_export($apiResponse->errors, true));
            $response['status'] = self::ERROR;
            $response['message'] = $apiResponse->errors[0]->message;
        }
        return $response;
    }

    /**
     * Nick Name Unique Validation
     *
     * @param array $ccFormData
     * @return boolean
     */
    public function getNickNameStatus($ccFormData)
    {
        if ($ccFormData['isNickName'] == 'true') {
            $profileInfo = $this->enhancedProfile->getLoggedInProfileInfo();
            $creditCardList = [];
            if (isset($profileInfo->output->profile->creditCards)) {
                $creditCardList = $profileInfo->output->profile->creditCards;
            }

            foreach ($creditCardList as $list) {
                if (strtoupper($list->creditCardLabel) == strtoupper($ccFormData['creditCardLabel'])
                && $list->profileCreditCardId != $ccFormData['profileCreditCardId']) {
                    return true;
                }
            }
        }
    }
}
