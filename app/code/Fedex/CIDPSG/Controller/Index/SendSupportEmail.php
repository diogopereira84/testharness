<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Controller\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Fedex\CIDPSG\Helper\AdminConfigHelper;
use Fedex\CIDPSG\Helper\PegaHelper;
use Fedex\CIDPSG\Helper\Email;
use Psr\Log\LoggerInterface;

/**
 * SendSupportEmail Controller class
 */
class SendSupportEmail implements ActionInterface
{
    public $reqJson;
    public $errorRespJson;
    public $successRespJson;
    public $failureData;
    public $successData;

    /**
     * Initialize dependencies.
     *
     * @param AdminConfigHelper $adminConfigHelper
     * @param Email $cidEmailHelper
     * @param ResultFactory $resultFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected AdminConfigHelper $adminConfigHelper,
        protected Email $cidEmailHelper,
        protected ResultFactory $resultFactory,
        protected LoggerInterface $logger
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
        $pegaUrl = $this->adminConfigHelper->getPegaAccountCreateApiUrl();
        $emailData['fromEmailId'] = $this->adminConfigHelper->getFromEmail();
        $emailData['toEmailId'] = $this->adminConfigHelper->getPegaApiSupportEmail();
        $reqData = $this->adminConfigHelper->getValue(PegaHelper::PEGA_API_REQUEST);
        $respData = $this->adminConfigHelper->getValue(PegaHelper::PEGA_API_RESPONSE);

        $reqDataArr = json_decode($reqData, true);
        $firstName = isset($reqDataArr['contact'][0]['firstName']) ? trim($reqDataArr['contact'][0]['firstName']) : '';
        $lastName = isset($reqDataArr['contact'][0]['lastName']) ? ' '.trim($reqDataArr['contact'][0]['lastName']) : '';
        $emailData['templateSubject'] = 'FXO Account Creation - '.$firstName.$lastName .
        ' - Email Routing - Backend not reachable';
        $emailData['templateData'] = $this->getTemplateData($reqData, $respData, $pegaUrl);

        $this->adminConfigHelper->clearValue(PegaHelper::PEGA_API_REQUEST);
        $this->adminConfigHelper->clearValue(PegaHelper::PEGA_API_RESPONSE);

        if ($this->cidEmailHelper->sendEmail($emailData)) {
            $data = [
                "success" => true,
                "message" => "Email Sent Successfully to Suppprt Team"
            ];

        } else {
            $data = [
                "error" => true,
                "message" => "Some Error Occured in Sending Mail"
            ];
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .' PEGA Email Routed '.$firstName.' - Fail'
            );
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($data);

        return $resultJson;
    }

    /**
     * To convert the data in email template format
     *
     * @param string $req
     * @param string $resp
     * @param string $pegaUrl
     * @return string
     */
    public function getTemplateData($req, $resp, $pegaUrl)
    {
        $dataString = '<div><h3>PEGA API EndPoint URL:</h3>';
        $dataString .= '<p>'.$pegaUrl.'</p>';
        $dataString .= '<h3>Request:</h3>';
        $dataString .= '<p style=\\"word-break:break-all;\\">'.str_replace("\"", "\\\"", $req).'</p>';
        $dataString .= '<h3>Response:</h3>';
        $dataString .= '<p style=\\"word-break:break-all;\\">'.str_replace("\"", "\\\"", $resp).'</p></div>';

        return $dataString;
    }
}
