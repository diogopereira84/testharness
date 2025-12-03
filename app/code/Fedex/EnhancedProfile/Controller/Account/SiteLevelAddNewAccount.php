<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\Account;

use Fedex\Recaptcha\Model\Validator;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Psr\Log\LoggerInterface;
use Fedex\Base\Helper\Auth as AuthHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Company\Api\CompanyRepositoryInterface;

class SiteLevelAddNewAccount implements  ActionInterface
{
    public const CHECKOUT_FEDEX_ACCOUNT_RECAPTCHA = 'checkout_fedex_account';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param EnhancedProfile $enhancedProfile
     * @param LoggerInterface $logger
     * @param Validator $recaptchaValidator
     * @param AuthHelper $authHelper
     * @param RequestInterface $request
     * @param CompanyRepositoryInterface $companyRepository
     */
    public function __construct(
        private Context $context,
        protected JsonFactory $jsonFactory,
        protected EnhancedProfile $enhancedProfile,
        protected LoggerInterface $logger,
        protected Validator $recaptchaValidator,
        protected AuthHelper $authHelper,
        private RequestInterface $request,
        protected CompanyRepositoryInterface $companyRepository
    )
    {
    }

    /**
     * Update account
     *
     * @return json
     */
    public function execute()
    {
        if ($this->recaptchaValidator->isRecaptchaEnabled(self::CHECKOUT_FEDEX_ACCOUNT_RECAPTCHA)) {
            $recaptchaValidation = $this->recaptchaValidator->validateRecaptcha(
                self::CHECKOUT_FEDEX_ACCOUNT_RECAPTCHA
            );
            if(is_array($recaptchaValidation)) {
                $result = $this->jsonFactory->create();
                $result->setData($recaptchaValidation);

                $this->logger->info(__METHOD__ . ':' . __LINE__ . 'Site Level Add Account API is not working: Recaptcha Error');
                return $result;
            }
        }
        $response = [];

        if ($this->authHelper->isLoggedIn()) {
                $accountNumber = $this->request->getPost('accountNumber');
                $companyId = $this->request->getPost('companyId');
                $siteAccountType = $this->request->getPost('accountType');
                try {
                    $company = $this->companyRepository->get((int) $companyId);
                    $editableFlag = 0;
                    $removeExistDiv = '';
                    if ($siteAccountType === 'ship') {
                        $editableFlag = $company->getData('shipping_account_number_editable');
                        $removeExistDiv = "payment_account_list_ship";
                    }
                    if ($siteAccountType == 'print') {
                        $editableFlag = $company->getData('fxo_account_number_editable');
                        $removeExistDiv = "payment_account_list_print";
                    }

                    $cardHtml = $this->enhancedProfile->siteLevelMakeNewAccountHtml($accountNumber, $siteAccountType, $editableFlag);
                    $response['info'] = $cardHtml;
                    $response['message'] = __("Site Level Payment Method has been successfully added.");
                    $response['status'] = true;
                    $response['isPayment'] = false;
                    $response['accountTypeDiv'] = $removeExistDiv;
                } catch (\Exception $e) {
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ . 'Site Level Add Account API is not working: '
                    . $e->getMessage());
                    $response = ["Failure" => "Site Level Payment is not validating."];
                }
            } else {
                $response = ["Failure" => "System Error, Please Try Again."];
            }
        $result = $this->jsonFactory->create();
        $result->setData($response);

        return $result;
    }
}
