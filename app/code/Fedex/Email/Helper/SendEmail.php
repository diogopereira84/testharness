<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Email\Helper;

use Fedex\Header\Helper\Data as HeaderData;
use Magento\Store\Model\ScopeInterface;
use Fedex\MarketplaceCheckout\Model\Email;

/**
 * SendEmail Helper
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class SendEmail extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const TAZ_EMAIL_API_ERROR = "Taz Email API Error:";

    /**
     * Data Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $configInterface
     * @param \Psr\Log\LoggerInterface $logger
     * @param HeaderData $headerData
     * @param Email $emailHelper
     *
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        protected \Magento\Framework\App\Config\ScopeConfigInterface $configInterface,
        protected \Psr\Log\LoggerInterface $logger,
        protected HeaderData $headerData,
        protected Email $emailHelper
    ) {
        parent::__construct($context);
    }

    /**
     * Send Mail
     *
     * @param string $customerData
     * @param int $templateId
     * @param string $templateData
     * @param string $tokenData
     * @param string $payLoad
     * @return boolean|string
     */
    public function sendMail($customerData, $templateId, $templateData, $tokenData, $payLoad = null, $subject = null)
    {
        try {
            $accessToken = $tokenData['access_token'];
            $authToken = $tokenData['auth_token'];
            $setupURL = $this->getTazEmailUrl();
            $templateD = json_encode($templateData);

            if (!$payLoad) {
                $arrData = [
                    'email' => [
                        'from' => [
                            'address' => $this->configInterface->getValue(
                                'trans_email/ident_general/email',
                                ScopeInterface::SCOPE_STORE
                            ),
                        ],
                        'to' => [
                            0 => [
                                'address' => $customerData['email'],
                                'name' => $customerData['name'],
                            ],
                        ],
                        'templateId' => $templateId,
                        'templateData' => $this->emailHelper->convertBase64(stripslashes(json_decode($templateD, true))),
                        'directSMTPFlag' => 'false',
                        'mimeType' => 'Content-Type:text/html'
                    ],
                ];

                if($subject) {
                    $arrData['email']['subject'] = $subject;
                }

                $jdata = json_encode($arrData);
            }

            if (!empty($accessToken)) {
                if ($payLoad) {
                    $jdata = $payLoad;
                }
                $authHeaderVal = $this->headerData->getAuthHeaderValue();
                //return $jdata;
                $aouth = "Bearer=" . $accessToken;
                $headers = ["Content-Type: application/json", "Content-Length: " . strlen($jdata), $authHeaderVal . $authToken, "Cookie: " . $aouth];
                $ch = curl_init($setupURL);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_ENCODING, '');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jdata);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $output = curl_exec($ch);
                if ($output === false) {
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ . self::TAZ_EMAIL_API_ERROR . curl_error($ch));
                    return 'Curl error: ' . curl_error($ch);
                } else {
                    $response = curl_getinfo($ch);
                    curl_close($ch);
                    if ($response['http_code'] == 200) {
                        return $output;
                    } else {
                        $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Taz Email API Error: ' . $output);
                        return "Email not sent!";
                    }
                }
            } else {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Error retrieving access token.');
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Taz Email API Error: ' . $e->getMessage());
        }
    }

    /**
     * Get Email URL
     */

    public function getTazEmailUrl()
    {
        return $this->configInterface->getValue("fedex/taz/taz_email_api_url");
    }
}
