<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Fedex\Canva\Model\LoginConfig;

class LoginConfigTest extends TestCase
{
    private const GET_VALUE = 'getValue';

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private ScopeConfigInterface|MockObject $scopeConfigMock;

    /**
     * @var LoginConfig|MockObject
     */
    private LoginConfig|MockObject $loginConfig;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createPartialMock(
            ScopeConfigInterface::class,
            [self::GET_VALUE, 'isSetFlag']
        );

        $this->loginConfig = new LoginConfig($this->scopeConfigMock);
    }

    public function testGetTitle()
    {
        $title = 'Login to save your designs';
        $this->scopeConfigMock->expects($this->once())->method(self::GET_VALUE)
            ->with(LoginConfig::XML_PATH_FEDEX_CANVA_LOGIN_TITLE, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($title);
        $resul = $this->loginConfig->getTitle();
        $this->assertEquals($title, $resul);
    }

    public function testGetDescription()
    {
        $description = 'We’re working to create a better experience for you.......';
        $this->scopeConfigMock->expects($this->once())->method(self::GET_VALUE)
            ->with(LoginConfig::XML_PATH_FEDEX_CANVA_LOGIN_DESCRIPTION, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($description);
        $resul = $this->loginConfig->getDescription();
        $this->assertEquals($description, $resul);
    }

    public function testGetRegisterButtonLabel()
    {
        $label = 'Create a user ID';
        $this->scopeConfigMock->expects($this->once())->method(self::GET_VALUE)
            ->with(LoginConfig::XML_PATH_FEDEX_CANVA_LOGIN_REGISTER_BUTTON_LABEL, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($label);
        $resul = $this->loginConfig->getRegisterButtonLabel();
        $this->assertEquals($label, $resul);
    }

    public function testGetLoginButtonLabel()
    {
        $label = 'Log in';
        $this->scopeConfigMock->expects($this->once())->method(self::GET_VALUE)
            ->with(LoginConfig::XML_PATH_FEDEX_CANVA_LOGIN_LOGIN_BUTTON_LABEL, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($label);
        $resul = $this->loginConfig->getLoginButtonLabel();
        $this->assertEquals($label, $resul);
    }

    public function testContinueButtonLabel()
    {
        $label = 'Continue as a guest';
        $this->scopeConfigMock->expects($this->once())->method(self::GET_VALUE)
            ->with(LoginConfig::XML_PATH_FEDEX_CANVA_LOGIN_CONTINUE_BUTTON_LABEL, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($label);
        $resul = $this->loginConfig->getContinueButtonLabel();
        $this->assertEquals($label, $resul);
    }
}
