<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Controller\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\CIDPSG\Helper\AdminConfigHelper;
use Fedex\CIDPSG\Helper\Email;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * AuthorizedFormSubmit Controller class
 */
class AuthorizedFormSubmit implements ActionInterface
{
    /**
     * Initialize dependencies.
     *
     * @param RequestInterface $requestInterface
     * @param JsonFactory $jsonFactory
     * @param AdminConfigHelper $adminConfigHelper
     * @param Email $email
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected RequestInterface $requestInterface,
        protected JsonFactory $jsonFactory,
        protected AdminConfigHelper $adminConfigHelper,
        protected Email $email,
        protected StoreManagerInterface $storeManager,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Use to submit authorized form data
     *
     * @return mixed
     */
    public function execute()
    {
        try {
            $resultJson = $this->jsonFactory->create();
            $formData = $this->requestInterface->getPostValue();
            $resultJson->setData($formData);
            $this->sendAuthorizedEmail($formData);

            return $resultJson;
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ':Authorized Request Error',
                ['exception' => $e->getMessage()]
            );
        }

        return false;
    }

    /**
     * Send AUthorized Email
     *
     * @param array $formData
     * @return void
     */
    public function sendAuthorizedEmail($formData)
    {
        $genericEmailData = $this->prepareGenericEmailRequest($formData);
        $this->email->callGenericEmailApi($genericEmailData);
    }

    /**
     * Prepare generic email request for Authrized Email
     *
     * @param array $formData
     * @return mixed
     */
    public function prepareGenericEmailRequest($formData)
    {
        try {
            $authorizedEmailTemplateId = $this->adminConfigHelper->getAuthorizedEmailTemplate();
            $storeId = $this->storeManager->getStore()->getId();

            $authorizedEmailTemplateContent = $this->email->loadEmailTemplate(
                $authorizedEmailTemplateId,
                $storeId
            );

            $subject = "AU Request - " . $formData["account_user_name"] . " - " .$formData["office_account_no"];

            $fromEmail = $this->adminConfigHelper->getFromEmail();
            $toEmail = $this->adminConfigHelper->getAuthorizedUserEmail();

            return '{
                "templateData": "' . $authorizedEmailTemplateContent . '",
                "templateSubject": "' . $subject . '",
                "toEmailId": "' . $toEmail . '",
                "fromEmailId": "' . $fromEmail . '",
                "retryCount": 0,
                "errorSupportEmailId": "",
                "attachment": "' . str_replace('"', '\\\\\"', json_encode($formData)) . '"
            }';
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ':Authorized Request Error',
                ['exception' => $e->getMessage()]
            );
        }

        return false;
    }
}
