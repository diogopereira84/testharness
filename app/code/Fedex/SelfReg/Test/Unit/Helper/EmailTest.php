<?php

namespace Fedex\SelfReg\Test\Unit\Helper;

use Fedex\SelfReg\Helper\Email;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Email\Model\Template;
use Fedex\Email\Helper\SendEmail;
use Fedex\Punchout\Helper\Data as PunchoutHelper;

class EmailTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $scopeConfig;
    protected $template;
    /**
     * @var (\Fedex\Email\Helper\SendEmail & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $sendEmail;
    protected $punchoutHelper;
    protected $data;
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->template = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getTemplateText', 'getTemplateSubject'])
            ->getMock();

        $this->sendEmail = $this->getMockBuilder(SendEmail::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendMail'])
            ->getMock();

        $this->sendEmail = $this->getMockBuilder(SendEmail::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendMail'])
            ->getMock();

        $this->punchoutHelper = $this->getMockBuilder(PunchoutHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAuthGatewayToken', 'getTazToken'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->data = $objectManagerHelper->getObject(
            Email::class,
            [
                'context' => $this->contextMock,
                'scopeConfig' => $this->scopeConfig,
                'template' => $this->template,
                'sendEmail' => $this->sendEmail,
                'punchoutHelper' => $this->punchoutHelper
            ]
        );
    }

    /**
     * @test testSendPendingEmail
     */
    public function testSendPendingEmail()
    {
        $companyUrl = 'https://staging3.office.fedex.com/me/';
        $companyName = 'Me';
        $customerName = 'Neeraj';
        $customerEmail = 'neeraj2.gupta@infogain.com';
        $adminEmail = 'test@test.com';
        $adminName = 'neeraj gupta';
        $templateContent = '<table class="main_content"><tr><td>Hi %admin_name%,</td></tr><tr><td>A new user request is pending your approval.<br /> Click the link below to review all pending requests.<br /><br /><br /></td></tr><tr><td>%login_company_name_with_url%</td></tr></table>';
        $templateSubject = 'Registered new customer %customer_name%';

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn('2');

        $this->template->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->template->expects($this->any())
            ->method('getTemplateText')
            ->willReturn($templateContent);

        $this->template->expects($this->any())
            ->method('getTemplateSubject')
            ->willReturn($templateSubject);

        $this->punchoutHelper->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn('GatewayToken');

        $this->punchoutHelper->expects($this->any())
            ->method('getTazToken')
            ->willReturn('TazToken');

        $this->assertEquals(null, $this->data->sendPendingEmail($companyUrl, $companyName, $customerName, $customerEmail, $adminEmail, $adminName));
    }

    /**
     * @test testSendApprovalEmail
     */
    public function testSendApprovalEmail()
    {
        $companyUrl = 'https://staging3.office.fedex.com/me/';
        $companyName = 'Me';
        $customerName = 'Neeraj';
        $customerEmail = 'neeraj2.gupta@infogain.com';
        $adminEmail = 'test@test.com';
        $adminName = 'neeraj gupta';
        $templateContent = '<table  class="main_content"><tr><td>Hi %customer_name%,</td></tr><tr><td>Your login request has been approved. Click the link below to access your Storefront.<br /><br /><br /></td></tr><tr><td>%login_company_name_with_url%</td></tr></table>';
        $templateSubject = 'Your company account successfully approved!';

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn('1');

        $this->template->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->template->expects($this->any())
            ->method('getTemplateText')
            ->willReturn($templateContent);

        $this->template->expects($this->any())
            ->method('getTemplateSubject')
            ->willReturn($templateSubject);

        $this->punchoutHelper->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn('GatewayToken');

        $this->punchoutHelper->expects($this->any())
            ->method('getTazToken')
            ->willReturn('{"access_token":"AccessTokenValue"}');

        $this->assertEquals(null, $this->data->sendApprovalEmail($companyUrl, $companyName, $customerName, $customerEmail));
    }
}