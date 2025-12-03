<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Fedex\SelfReg\Helper\Email as SelfRegEmailHelper;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\Filesystem\Io\File as FileIo;

/**
 * CIDPSG Email class
 */
class Email extends AbstractHelper
{
    /**
     * Email Constructor
     *
     * @param Context $context
     * @param SelfRegEmailHelper $selfRegEmailHelper
     * @param Curl $curl
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param File $file
     * @param FileIo $fileIo
     * @param TransportBuilder $transportBuilder
     */
    public function __construct(
        Context $context,
        protected SelfRegEmailHelper $selfRegEmailHelper,
        protected Curl $curl,
        protected LoggerInterface $logger,
        private File $file,
        protected FileIo $fileIo,
        protected TransportBuilder $transportBuilder
    ) {
        parent::__construct($context);
    }

    /**
     * Call generic email API
     *
     * @param array $genericEmailData
     * @param boolean $isUploadtoQuote
     * @return boolean
     */
    public function callGenericEmailApi($genericEmailData, $isUploadtoQuote = false)
    {
        try {
            $url = $this->scopeConfig->getValue("web/unsecure/base_url") .
                "rest/V1/fedexoffice/genericemail/";
            if ($isUploadtoQuote) {
                $this->curl = new Curl();
            }
            $this->curl->addHeader("Content-Type", "application/json");
            // Check if bcc quote confirmation toggle is enabled
            $isBccEnabled = $this->scopeConfig->isSetFlag(
                'environment_toggle_configuration/environment_toggle/sales_rep_ctc_bcc_quote_confirmation',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            if ($isBccEnabled) {
                if (is_array($genericEmailData)) {
                    $genericEmailData = json_encode($genericEmailData);
                }
                $emailData = json_decode($genericEmailData, true);
                if (!empty($emailData['bccEmailIds'])) {
                    $bccArray = [];
                    foreach ($emailData['bccEmailIds'] as $bcc) {
                        $bcc = trim($bcc);
                        $bccArray[] = [
                            'address' => $bcc
                        ];
                    }
                    $emailData['bcc'] = $bccArray;
                        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' BCC transformed for API',['bcc_structure' => $emailData['bcc']]);
                }
                $genericEmailData = json_encode($emailData);
            }
            $this->curl->post($url, $genericEmailData);

            if ($this->curl->getStatus() == 200) {
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ':Generic email API call error message: ',
                ['exception' => $e->getMessage()]
            );
        }

        return false;
    }

    /**
     * Get email header logo image
     *
     * @return string
     */
    public function emailHeaderLogo()
    {
        return $this->scopeConfig->getValue(
            'selfreg_setting/email_setting/header_image_url',
            ScopeInterface::SCOPE_STORE,
        );
    }

    /**
     * Use to send email
     *
     * @param array $genericEmailData
     * @return boolean
     */
    public function sendEmail($genericEmailData)
    {
        try {
            $customerToData = $customerFromData = $templateSubject = $templateData = $attachment = null;
            if (isset($genericEmailData["toEmailId"])) {
                $toEmailId = explode(",", $genericEmailData["toEmailId"]);
                foreach ($toEmailId as $key => $value) {
                    $customerToData[$key] = [
                        "address" => trim($value),
                        "name" => null
                    ];
                }
            }

            if (isset($genericEmailData["fromEmailId"])) {
                $customerFromData = $genericEmailData["fromEmailId"];
            }

            if (isset($genericEmailData["templateSubject"])) {
                $templateSubject = $genericEmailData["templateSubject"];
            }

            if (isset($genericEmailData["templateData"]) && isset($genericEmailData["toEmailId"])) {
                $templateContent = $genericEmailData["templateData"];
                $emailHeaderLogo = $this->emailHeaderLogo();
                $templateData = '{"messages":{"statement":"' . $templateContent . '","url":"' .
                    $emailHeaderLogo . '"},"order":{"contact":{"email":"' .
                    $genericEmailData["toEmailId"] . '"}}}';
                $templateData = "base64:".base64_encode($templateData);
            }

            if (isset($genericEmailData["attachment"]) &&
                !empty($genericEmailData["attachment"]) &&
                $this->file->isExists($genericEmailData["attachment"])) {
                $csvData = $this->file->fileGetContents((string)$genericEmailData["attachment"]);
                $byteData = base64_encode((string)$csvData);
                $fileData = $this->fileIo->getPathInfo($genericEmailData["attachment"]);
                $fileName = "attachment.csv";
                if (!empty($fileData)) {
                    $fileName = $fileData["basename"];
                }
                $attachment = '"attachment":[
                    {
                        "mimeType":"text/csv",
                        "fileName": "' . $fileName . '",
                        "content":"' . $byteData . '"
                    }
                ],';
            }

            $this->logger->info(
                __METHOD__ . ':' . __LINE__ . "Generic email data send " .
                    $templateData . " " . $attachment . " " . $templateSubject
            );

            $sendEmailFlag = false;

            if ($customerToData && $templateData && $templateSubject && $customerFromData) {
                $sendEmail = $this->selfRegEmailHelper->sendMail(
                    $customerToData,
                    $templateData,
                    $templateSubject,
                    $customerFromData,
                    $attachment,
                    []
                );

                if ($sendEmail) {
                    $emailResponse = json_decode($sendEmail, true);

                    if (is_array($emailResponse) && array_key_exists("output", $emailResponse)
                        && $emailResponse["output"] == null) {
                        $sendEmailFlag = true;
                    }
                }
            }

            return $sendEmailFlag;
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ':Generic email send error ',
                ['exception' => $e->getMessage()]
            );
        }

        return false;
    }

    /**
     * Use to load email template content
     *
     * @param int|string $templateId
     * @param int $storeId
     * @param array $templateVars
     * @param boolean $isBackendEmail
     * @return boolean|string
     */
    public function loadEmailTemplate($templateId, $storeId = 0, $templateVars = [], $isBackendEmail = false)
    {
        try {
            $areaCode = $isBackendEmail ? Area::AREA_ADMINHTML : Area::AREA_FRONTEND;
            $templateContent = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area' => $areaCode,
                    'store' => $storeId
                ])
                ->setFrom(['name' => '', 'email' => ''])
                ->addTo("", "")
                ->setTemplateVars($templateVars)
                ->getTransport()
                ->getMessage()
                ->getBodyText();

            return $this->selfRegEmailHelper->minifyHtml(
                quoted_printable_decode(str_replace('"', '', $templateContent))
            );
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ': Email load
                error message ',
                ['exception' => $e->getMessage()]
            );
        }

        return false;
    }
}
