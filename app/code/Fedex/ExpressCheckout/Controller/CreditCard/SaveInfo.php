<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpressCheckout\Controller\CreditCard;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ResponseInterface;
use Magento\Directory\Model\RegionFactory;
use Fedex\Recaptcha\Model\Validator;
use Fedex\Base\Helper\Auth as AuthHelper;

class SaveInfo extends Action
{
    public const ERROR_AUTH = 'auth_failed';
    public const ERROR = 'error';
    public const SUCCESS = 'success';
    public const SYSTEM_ERROR = 'System error, Please try again.';
    public const CHECKOUT_CC_RECAPTCHA = 'checkout_cc';

    /**
     * @var JsonFactory $jsonFactory
     */
    protected $jsonFactory;

    /**
     * @var EnhancedProfile $enhancedProfile
     */
    protected $enhancedProfile;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var Session $customerSession
     */
    protected $customerSession;

    /**
     * @var ResponseInterface $response
     */
    protected $response;

    protected RegionFactory $regionFactory;

    /**
     * @var Validator $recaptchaValidator
     */
    protected Validator $recaptchaValidator;

    /**
     * @var AuthHelper
     */
    protected AuthHelper $authHelper;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param EnhancedProfile $enhancedProfile
     * @param LoggerInterface $logger
     * @param Session $customerSession
     * @param ResponseInterface $response
     * @param RegionFactory $regionFactory
     * @param Validator $recaptchaValidator
     * @param AuthHelper $authHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        EnhancedProfile $enhancedProfile,
        LoggerInterface $logger,
        Session $customerSession,
        ResponseInterface $response,
        RegionFactory $regionFactory,
        Validator $recaptchaValidator,
        AuthHelper $authHelper
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->enhancedProfile = $enhancedProfile;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->response = $response;
        $this->regionFactory = $regionFactory;
        $this->recaptchaValidator = $recaptchaValidator;
        $this->authHelper = $authHelper;
    }

    /**
     * Save Credit Card Information
     *
     * @return json
     */
    public function execute()
    {
        $this->logger->info(__METHOD__ . ':' . __LINE__ . '
         Starting save credit card process with Express Checkout');

        if ($this->recaptchaValidator->isRecaptchaEnabled(self::CHECKOUT_CC_RECAPTCHA)) {
            $recaptchaValidation = $this->recaptchaValidator->validateRecaptcha(self::CHECKOUT_CC_RECAPTCHA);
            if(is_array($recaptchaValidation)) {
                $result = $this->jsonFactory->create();
                $result->setData($recaptchaValidation);

                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Error while saving credit card info: Recaptcha Error');
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Save credit card process ended at Express Checkout:');
                return $result;
            }
        }
        $response = [];

            $validationKeyParam = (string) $this->getRequest()->getParam('loginValidationKey');
            if (
                $this->authHelper->isLoggedIn() &&
                $this->customerSession->getLoginValidationKey() === $validationKeyParam
            ) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . '
                    Save CC request processed with request header ' .
                    var_export($this->getRequest()->getHeaders()->toString(), true));
                $ccFormData = $this->getRequest()->getParams();
                if(empty($ccFormData['stateOrProvinceCode']) && !empty($ccFormData['regionId'])){
                    $region = $this->regionFactory->create()->load($ccFormData['regionId']);
                    $ccFormData['stateOrProvinceCode'] = $region->getId() ? $region->getCode() : '';
                }
                try {
                    $ccFormData['requestId'] = uniqid();
                    $ccFormData['nameOnCard'] = $ccFormData['cardHolderName'];
                    $postFields = $this->enhancedProfile->prepareCreditCardTokensJson($ccFormData);
                    if ($postFields) {
                        $endPointUrl = $this->enhancedProfile->getConfigValue(EnhancedProfile::CREDIT_CARD_TOKENS);
                        $apiResponse = $this->enhancedProfile->apiCall('POST', $endPointUrl, $postFields);
                        if (!isset($apiResponse->errors)) {
                            if (isset($apiResponse->output->creditCardToken)) {
                                $ccFormData['creditCardToken'] = $apiResponse->output->creditCardToken->token;
                                $ccFormData['tokenExpirationDate'] = $apiResponse->output->creditCardToken->expirationDateTime;
                                $cardRequestJson = $this->enhancedProfile->prepareAddCerditCardJson($ccFormData);
                                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Add Credit Card Request: '
                                . json_encode($cardRequestJson, true));
                                $apiResponse = $this->enhancedProfile->saveCreditCard('POST', $cardRequestJson);
                                if (!isset($apiResponse->errors)) {
                                    $this->enhancedProfile->setProfileSession();
                                    $response['status'] = self::SUCCESS;
                                    $response['info'] = $apiResponse->output;
                                    $response['message'] = __("Credit Card added successfully.");
                                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Add Credit Card Response: '
                                    . json_encode($apiResponse, true));
                                } else {
                                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error(s) while saving credit card: '
                                    . json_encode($apiResponse->errors, true));
                                    $response['status'] = self::ERROR;
                                    $response['message'] = $apiResponse;
                                }
                            } else {
                                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Credit Card Encryption Error Response: '
                                . json_encode($apiResponse, true));
                                $response['status'] = self::ERROR_AUTH;
                                $response['info'] = $apiResponse;
                                $response['message'] = false;
                            }
                        } else {
                            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Credit Card Error Response: '
                            . json_encode($apiResponse, true));
                            $response['status'] = self::ERROR;
                            $response['info'] = $apiResponse;
                            $response['message'] = false;
                        }
                    } else {
                        $this->logger->error(__METHOD__ . ':' . __LINE__ . '
                            Unauthorized save Credit Card request was rejected. : Request headers: ' . $this->getRequest()->getHeaders()->toString());
                        $this->response->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_FORBIDDEN);
                        $this->response->sendHeaders();
                        $this->response->setBody('Access Denied');
                        $this->response->sendResponse();
                    }
                } catch (\Exception $e) {
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Error while saving credit card info: '
                    . $e->getMessage());
                    $response['status'] = self::ERROR;
                    $response['message'] = self::SYSTEM_ERROR;
                }
            } else {
                $response['status'] = self::ERROR;
                $response['message'] = self::SYSTEM_ERROR;
            }

        $result = $this->jsonFactory->create();
        $result->setData($response);
        $this->logger->info(__METHOD__ . ':' . __LINE__ . '
            Save credit card process ended at Express Checkout:');
        return $result;
    }
}
