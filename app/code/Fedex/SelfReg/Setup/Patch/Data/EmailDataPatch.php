<?php
namespace Fedex\SelfReg\Setup\Patch\Data;

use Fedex\SelfReg\EmailContentReader\EmailContentReader;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 */
class EmailDataPatch implements DataPatchInterface
{
    /**
     * @var SimpleContentReader
     */
    private $emailContentReader;

    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private LoggerInterface $logger,
        EmailContentReader $emailContentReader
    ) {
        $this->emailContentReader = $emailContentReader;
    }

    public function apply()
    {

        try {
            $customerEmailApproveTemp = $this->emailContentReader->getContent('approve_customer_email.html');
            $customerEmailPendingTemp = $this->emailContentReader->getContent('pending_customer_email.html');

            if (!empty($customerEmailApproveTemp)) {

                $approveEmailTemplatedata[] = [
                    'template_code' => 'Approve_SelfReg_WLGN_User',
                    'template_text' => $customerEmailApproveTemp,
                    'template_styles' => null,
                    'template_type' => 2,
                    'template_subject' => $this->emailTemplatSubject('approve_user'),
                    'orig_template_code' => 'SelfReg_email_template',
                    'orig_template_variables' => $this->emailTemplateVarible('approve_user'),

                ];

                $this->moduleDataSetup->getConnection()->insertArray(
                    $this->moduleDataSetup->getTable('email_template'),
                    [
                        'template_code',
                        'template_text',
                        'template_styles',
                        'template_type',
                        'template_subject',
                        'orig_template_code',
                        'orig_template_variables',
                    ],
                    $approveEmailTemplatedata
                );
            }

            if (!empty($customerEmailPendingTemp)) {

                $pendingEmailTemplatedata[] = [
                    'template_code' => 'Pending_SelfReg_WLGN_User',
                    'template_text' => $customerEmailPendingTemp,
                    'template_styles' => null,
                    'template_type' => 2,
                    'template_subject' => $this->emailTemplatSubject('pending_user'),
                    'orig_template_code' => 'SelfReg_email_template',
                    'orig_template_variables' => $this->emailTemplateVarible('pending_user'),

                ];

                $this->moduleDataSetup->getConnection()->insertArray(
                    $this->moduleDataSetup->getTable('email_template'),
                    [
                        'template_code',
                        'template_text',
                        'template_styles',
                        'template_type',
                        'template_subject',
                        'orig_template_code',
                        'orig_template_variables',
                    ],
                    $pendingEmailTemplatedata
                );
            }
            $this->moduleDataSetup->endSetup();

        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }
    public static function getDependencies()
    {
        return [];
    }
    public static function getVersion()
    {
        return '';
    }

    public function getAliases()
    {
        return $this->getDependencies();
    }

    public function emailTemplatSubject($userType)
    {
        $emailSubject = 'Registered new customer %customer_name%';
        if ($userType == 'approve_user') {
            $emailSubject = 'Your company account successfully approved!';
        }

        return $emailSubject;
    }

    public function emailTemplateVarible($userType)
    {
        $emailTemplateVarible = '{
            "var admin_first_name":"admin first name",
            "var comapny_name":"company name",
            "var customer_name":"customer name"
            }';

        if ($userType == 'approve_user') {
            $emailTemplateVarible = '{
                    "var customer":"customer name",
                    "var comapny_login_url":"company login url",
                    "var company_name":"company name",
                    "var admin_full_name":"company admin name"
                    }';
        }
        return $emailTemplateVarible;
    }
}
