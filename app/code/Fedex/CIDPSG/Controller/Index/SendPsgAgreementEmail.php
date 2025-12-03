<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Controller\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Fedex\CIDPSG\Helper\AdminConfigHelper;
use Fedex\CIDPSG\Helper\Email;
use Fedex\CIDPSG\Helper\PsgHelper;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * SendPsgAgreementEmail Controller class
 */
class SendPsgAgreementEmail implements ActionInterface
{
    public $formData;
    public $successRespJson;

    /**
     * Initialize dependencies.
     *
     * @param RequestInterface $requestInterface
     * @param LoggerInterface $logger
     * @param AdminConfigHelper $adminConfigHelper
     * @param Email $email
     * @param PsgHelper $psgHelper
     * @param StoreManagerInterface $storeManager
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        protected RequestInterface $requestInterface,
        protected LoggerInterface $logger,
        protected AdminConfigHelper $adminConfigHelper,
        protected Email $email,
        protected PsgHelper $psgHelper,
        protected StoreManagerInterface $storeManager,
        protected ResultFactory $resultFactory
    )
    {
    }

    /**
     * To submit account request form data
     *
     * @return json
     */
    public function execute()
    {
        try {
            $formData = $this->requestInterface->getPostValue();
            $this->logger->info(__METHOD__ . ':' . __LINE__ . 'Form Data Value: ' .json_encode($formData));
            $emailStatus = $this->sendPaAgreementEmail($formData);

            $data = [
                "success" => $emailStatus,
                "message" => ($emailStatus) ? "Email Sent Successfully" : "Unable To Send Email",
                "account_type" => (int) $formData['account_type'] ?? 0,
                "source" => $formData['clientId'] == 'fdxform' ? 'PSG/FDX' : 'PSG',
            ];
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData($data);

            return $resultJson;
        } catch (\Exception $e) {
            if (isset($formData)) {
                $this->logger->info(
                    __METHOD__ . ':' . __LINE__ . 'Error occurred in Form Data Value: ' . json_encode($formData)
                );
            } else {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Form Data Value: Not Available');
            }
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ':Pa Agreement Request Error',
                ['exception' => $e->getMessage()]
            );
        }

        return false;
    }

    /**
     * Send participation agreement email
     *
     * @param array $formData
     * @return bool
     */
    public function sendPaAgreementEmail($formData)
    {
        $genericEmailData = $this->prepareGenericEmailRequest($formData);

        return $this->email->callGenericEmailApi($genericEmailData);
    }

    /**
     * Prepare generic email request for pa agreement Email
     *
     * @param array $formData
     * @return mixed
     */
    public function prepareGenericEmailRequest($formData)
    {
        try {
            $paAgreementDescription = '';
            $emailContentCompanyName = '';
            $paHeading = 'Participation Agreement';
            if ($formData["clientId"]) {
                $paAgreementData = $this->psgHelper->getPSGPaAgreementInfoByClientId($formData["clientId"]);
                $paAgreementDescription = $paAgreementData['pa_agreement'];
                $participationCode = $paAgreementData['participation_code'];
                if ($formData["clientId"] != 'default') {
                    $emailContentCompanyName = $paAgreementData['company_name'] ?? '';
                }

                if ($formData["clientId"] == 'fdxform') {
                    $paHeading = 'FedEx Business Account Setup Form';
                }

                if (!empty($participationCode)) {
                    $formData["participation_code"] = $participationCode;
                }

                unset($formData["clientId"]);
            }

            if (isset($formData["account_type"])) {
                unset($formData["account_type"]);
            }

            $firstName = $lastName = $companyName = '';
            $fromEmail = $this->adminConfigHelper->getFromEmail();
            $toEmail = $this->adminConfigHelper->getPaAgreementUserEmail();
            $storeId = $this->storeManager->getStore()->getId();

            $count = 1;
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ . 'Form Data Value prepareGenericEmailRequest: ' .json_encode($formData)
            );
            foreach (array_keys($formData) as $value) {
                if (!empty($formData[$value]) &&
                    str_contains(str_replace('_', ' ', trim(strtolower($value))), 'first name')) {
                    $firstName = $formData[$value];
                }
                if (!empty($formData[$value]) &&
                    str_contains(str_replace('_', ' ', trim(strtolower($value))), 'last name')) {
                    $lastName = $formData[$value];
                }
                if ($count == 1 && !empty($formData[$value]) &&
                str_contains(str_replace('_', ' ', trim(strtolower($value))), 'company name')) {
                    $companyName = $formData[$value];
                    $count++;
                }
            }

            $subject = "Generic PA Program request for " . $firstName . " " . $lastName .
                " at " . $companyName;

            $emailData = [
                'pa_heading' => $paHeading,
                'participation_agreement' => $paAgreementDescription,
                'company_name' => $emailContentCompanyName,
                'items' => $formData,
            ];

            $paAgreementEmailTemplateId = $this->adminConfigHelper->getPaAgreementEmailTemplate();
            $paAgreementEmailTemplateContent = $this->email->
                loadEmailTemplate($paAgreementEmailTemplateId, $storeId, $emailData);

            foreach (array_keys($formData) as $value) {
                if (!empty($formData[$value]) &&
                    str_contains(strtolower($value), 'mail') &&
                    preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $formData[$value])) {
                    $fromEmail = $formData[$value];
                    break;
                }
            }

            $data = [
                'templateData' => $paAgreementEmailTemplateContent,
                'templateSubject' => $subject,
                'toEmailId' => $toEmail,
                'fromEmailId'=> $fromEmail,
                'retryCount' => 0,
                'errorSupportEmailId' => "",
                'attachment' => ""
            ];

            $this->logger->info(__METHOD__ . ':' . __LINE__ . 'Email Data Format: ' .json_encode($data));
            return json_encode($data);
        } catch (\Exception $e) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . 'Error with Form Data Value: ' .json_encode($formData));
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ':Pa Agreement Prepare Generic Email Request Error',
                ['exception' => $e->getMessage()]
            );
        }

        return false;
    }
}
