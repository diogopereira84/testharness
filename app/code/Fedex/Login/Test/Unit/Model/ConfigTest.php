<?php
/**
 * @category    Fedex
 * @package     Fedex_Login
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Austin King <austin.king@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Login\Test\Unit\Model;

use Fedex\Login\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    protected $scopeConfigMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $configMock;
    private const IS_CONFIRMATION_EMAIL_REQUIRED = 'customer/create_account/confirm';
    private const VERIFICATION_EMAIL_FROM = 'sso/user_email_verification/verification_from_email';
    private const VERIFICATION_EMAIL_SUBJECT = 'sso/user_email_verification/verification_email_subject';
    private const LINK_EXPIRATION_TIME = 'sso/user_email_verification/verification_email_expiry_duration';
    private const EMAIL_VERIFICATION_TEMPLATE = 'fedex_fcl_user_email_verification_template';

    protected function setUp(): void
    {

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->onlyMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->configMock = $this->objectManager->getObject(
            Config::class,
            [
                'scopeConfig' => $this->scopeConfigMock
            ]
        );
    }

    /**
     * Test the isConfirmationEmailRequired method
     */
    public function testIsConfirmationEmailRequired()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::IS_CONFIRMATION_EMAIL_REQUIRED, ScopeInterface::SCOPE_STORE)
            ->willReturn(true);

        $this->assertTrue($this->configMock->isConfirmationEmailRequired());
    }

    /**
     * Test the getVerificationEmailFrom method
     */
    public function testGetVerificationEmailFrom()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::VERIFICATION_EMAIL_FROM, ScopeInterface::SCOPE_STORE)
            ->willReturn('');

        $this->assertIsString($this->configMock->getVerificationEmailFrom());
    }

    /**
     * Test the getVerificationEmailSubject method
     */
    public function testGetVerificationEmailSubject()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::VERIFICATION_EMAIL_SUBJECT, ScopeInterface::SCOPE_STORE)
            ->willReturn('');

        $this->assertIsString($this->configMock->getVerificationEmailSubject());
    }

    /**
     * Test the getLinkExpirationTime method
     */
    public function testGetLinkExpirationTime()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::LINK_EXPIRATION_TIME, ScopeInterface::SCOPE_STORE)
            ->willReturn('');

        $this->assertIsString($this->configMock->getLinkExpirationTime());
    }

    /**
     * Test the getEmailVerificationTemplate method
     */
    public function testGetEmailVerificationTemplate()
    {

        $this->assertEquals(self::EMAIL_VERIFICATION_TEMPLATE, $this->configMock->getEmailVerificationTemplate());
    }

}
