<?php

namespace Fedex\SelfReg\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Email\Model\Template;
use Fedex\Email\Helper\SendEmail;
use Fedex\Punchout\Helper\Data as PunchoutHelper;

class Email extends AbstractHelper
{
   const CUSTOMER_NAME  = "%customer_name%";
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Template
     */
    protected $template;

    /**
     * @var SendEmail
     */
    protected $sendEmail;

    /**
     * @var PunchoutHelper
     */
    protected $punchoutHelper;



    /**
     * Data Class Constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Template $template
     * @param SendEmail $sendEmail
     * @param PunchoutHelper $punchoutHelper
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Template $template,
        SendEmail $sendEmail,
        PunchoutHelper $punchoutHelper
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->template = $template;
        $this->sendEmail = $sendEmail;
        $this->punchoutHelper = $punchoutHelper;
    }

    /**
     * @param string $companyUrl
     * @param string $companyName
     * @param string $customerName
     * @param string $customerEmail
     * @param string $adminEmail
     * @param string $adminName
     */
    public function sendPendingEmail($companyUrl, $companyName, $customerName, $customerEmail, $adminEmail, $adminName, $customerDataCC = [])
    {
            $pendingEmailTemplateId = $this->scopeConfig->getValue(
                'selfreg_setting/email_setting/admin_pending',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            );

            $imageUrl = $this->scopeConfig->getValue(
                'selfreg_setting/email_setting/header_image_url',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            );
            $htmlHyperlinkTag = '<a href=';

            $companyNameWithUrl = $htmlHyperlinkTag. $companyUrl . ' >' . $companyName . '</a>';
            //B-1500081
            $loginCompanyNameWithUrl = $htmlHyperlinkTag . $companyUrl . ' >Login to access store home page</a>';

            if ($pendingEmailTemplateId) {
                $templateData = $this->template->load($pendingEmailTemplateId);
                $templetContent = $templateData->getTemplateText();
                $templetSubject = $templateData->getTemplateSubject();

                if ($templetContent) {
                    $templetContent = str_replace('"', '', $templetContent);
                    $templetContent = str_replace("%company_name_with_url%", $companyNameWithUrl, $templetContent);
                    $templetContent = str_replace("%customer_email%", $customerEmail, $templetContent);
                    $templetContent = str_replace(self::CUSTOMER_NAME, $customerName, $templetContent);
                    $templetContent = str_replace("%admin_name%", $adminName, $templetContent);
                    $templetContent = str_replace(
                        "%login_company_name_with_url%",
                        $loginCompanyNameWithUrl,
                        $templetContent
                    );
                    $templetContent = $this->minifyHtml($templetContent);
                }

                if ($templetSubject) {
                    $templetSubject = str_replace(self::CUSTOMER_NAME, $customerName, $templetSubject);
                }

                $customerData = ['name' => $adminName, 'email' => $adminEmail];

                $finalTemplateData = '{\"messages\":{\"statement\":\"'.$templetContent.
                    '\",\"url\":\"'.$imageUrl.'\"},\"order\":{\"contact\":{\"email\":\"'.$customerData["email"].'\"}}}';

                $this->sendMail($customerData, $finalTemplateData, $templetSubject , null, null, $customerDataCC);
            }
    }

    /**
     * @param string $companyUrl
     * @param string $companyName
     * @param string $customerName
     * @param string $customerEmail
     */
    public function sendApprovalEmail($companyUrl, $companyName, $customerName, $customerEmail)
    {

            $pendingEmailTemplateId = $this->scopeConfig->getValue(
                'selfreg_setting/email_setting/user_approve',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            );

            $imageUrl = $this->scopeConfig->getValue(
                'selfreg_setting/email_setting/header_image_url',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            );
            $htmlHyperlinkTag = '<a href=';
            $companyNameWithUrl = $htmlHyperlinkTag . $companyUrl . ' >' . $companyName . '</a>';
            //B-1500081
            $loginCompanyNameWithUrl = $htmlHyperlinkTag . $companyUrl . ' >Login to access store home page</a>';

            if ($pendingEmailTemplateId) {
                $templateData = $this->template->load($pendingEmailTemplateId);
                $templetContent = $templateData->getTemplateText();
                $templetSubject = $templateData->getTemplateSubject();

                if ($templetContent) {
                    $templetContent = str_replace('"','', $templetContent);
                    $templetContent = str_replace("%company_name_with_url%", $companyNameWithUrl, $templetContent);
                    $templetContent = str_replace("%customer_email%", $customerEmail, $templetContent);
                    $templetContent = str_replace(self::CUSTOMER_NAME, $customerName, $templetContent);
                    $templetContent = str_replace(
                        "%login_company_name_with_url%",
                        $loginCompanyNameWithUrl,
                        $templetContent);
                    $templetContent = $this->minifyHtml($templetContent);
                }

                if ($templetSubject) {
                    $templetSubject = str_replace(self::CUSTOMER_NAME, $customerName, $templetSubject);
                }

                $customerData = ['name' => $customerName, 'email' => $customerEmail];

                $finalTemplateData = '{\"messages\":{\"statement\":\"'.$templetContent.
                    '\",\"url\":\"'.$imageUrl.'\"},\"order\":{\"contact\":{\"email\":\"'.$customerData["email"].'\"}}}';

                $this->sendMail($customerData, $finalTemplateData, $templetSubject);
            }
    }

    /**
     * Send Mail
     *
     * @param array $customerData
     * @param string $templateData
     * @param string $templetSubject
     * @param string|null $fromEmail
     * @param string|null $attachment
     * @param array $customerDataCC
     * @return boolean|string
     */
    public function sendMail(
        $customerData,
        $templateData,
        $templetSubject,
        $fromEmail = null,
        $attachment = null,
        $customerDataCC = []
    ){
        $tokenData['access_token'] = "";
        $tokenData['auth_token'] = "";
        $gatewayToken = $this->punchoutHelper->getAuthGatewayToken();
        $tazToken = $this->punchoutHelper->getTazToken();
        if ($tazToken && !empty($gatewayToken)) {
            $tokenData['access_token'] = $tazToken;
            $tokenData['auth_token'] = $gatewayToken;
        }

        if (!$fromEmail) {
            $fromEmail = $this->scopeConfig->getValue(
                'trans_email/ident_general/email',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            );
        }
        $templateId = 'generic_template';

        if (!array_key_exists("email", $customerData) || $attachment) {
            $customerData = json_encode($customerData);
            $payLoad = '{
                "email": {
                    "from": {
                        "address": "'.$fromEmail.'"
                    },
                    "to": ' . $customerData .',
                    "subject": "'.$templetSubject.'",
                    "templateId": "generic_template",
                    "templateData": "'.$templateData.'",
                    ' . $attachment . '
                    "directSMTPFlag": "false",
                    "mimeType": "Content-Type:text/html"
                }
            }';
        } else {

            $customerDataCC = json_encode($customerDataCC);
            $payLoad = '{
                "email": {
                    "from": {
                        "address": "'.$fromEmail.'"
                    },
                    "to": [{
                        "address": "'.$customerData["email"].'",
                        "name": "'.$customerData["name"].'"
                    }],
                    "cc": '.$customerDataCC.',
                    "subject": "'.$templetSubject.'",
                    "templateId": "generic_template",
                    "templateData": "'.$templateData.'",
                    "directSMTPFlag": "false",
                    "mimeType": "Content-Type:text/html"
                }
            }';
        }

        return $this->sendEmail->sendMail($customerData, $templateId, $templateData, $tokenData, $payLoad);
    }

    /**
     * Minify HTMl
     * @param string $html
     * @return string
     */
    public function minifyHtml($html)
    {
        $search = array(
            '/(\n|^)(\x20+|\t)/',
            '/(\n|^)\/\/(.*?)(\n|$)/',
            '/\n/',
            '/\<\!--.*?-->/',
            '/(\x20+|\t)/', # Delete multispace (Without \n)
            '/\>\s+\</', # strip whitespaces between tags
            '/(\"|\')\s+\>/', # strip whitespaces between quotation ("') and end tags
            '/=\s+(\"|\')/'
        ); # strip whitespaces between = "'

        $replace = array(
            "\n",
            "\n",
            " ",
            "",
            " ",
            "><",
            "$1>",
            "=$1"
        );

        $html = preg_replace($search, $replace, $html);
        return $html;
    }
}