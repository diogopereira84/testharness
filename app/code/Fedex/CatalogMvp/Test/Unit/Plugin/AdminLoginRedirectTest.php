<?php
/**
 * Fedex
 * Copyright (C) 2024 Fedex
 * PHPUnit Test for AdminLoginRedirectTest
 */

namespace Fedex\CatalogMvp\Test\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Plugin\AdminLoginRedirect;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Backend\Model\Url;
use Magento\Framework\Stdlib\CookieManagerInterface;

class AdminLoginRedirectTest extends TestCase
{
    protected $redirectInterfaceMock;
    protected $cookieManagerInterfaceMock;
    protected $adminLoginRedirect;
    /**
     * @var AdminLoginRedirect
     */
    protected $plugin;

    /**
     * @var RedirectInterface
     */
    protected $redirectInterface;

    /**
     * @var ToggleConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $toggleConfigMock;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManagerInterface;

    /**
     * Setup method
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->redirectInterfaceMock = $this->getMockBuilder(RedirectInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRefererUrl'])
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cookieManagerInterfaceMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->setMethods(['getCookie'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->adminLoginRedirect = $objectManagerHelper->getObject(
            AdminLoginRedirect::class,
            [
                'redirect' => $this->redirectInterfaceMock,
                'toggleConfig' => $this->toggleConfigMock,
                'cookieManager' => $this->cookieManagerInterfaceMock
            ]
        );
    }

    /**
     * Test after get startup page with if
     *
     * @return void
     */
    public function testAfterGetStartupPageUrl() : void
    {
        $this->redirectInterfaceMock->expects($this->once())
            ->method('getRefererUrl')
            ->willReturn('www.test.com?email=1');

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('explorers_non_standard_catalog')
            ->willReturn(true);

        $subject = $this->createMock(Url::class);
        $result = 'www.test.com';

        $actualResult = $this->adminLoginRedirect->afterGetStartupPageUrl($subject, $result);

        $this->assertNotNull($actualResult);
    }

    /**
     * Test after get startup page withought if
     *
     * @return void
     */
    public function testAfterGetStartupPageUrlWithIfTwo() : void
    {
        $this->redirectInterfaceMock->expects($this->once())
            ->method('getRefererUrl')
            ->willReturn('www.test.com');

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('explorers_non_standard_catalog')
            ->willReturn(true);
        
        $this->cookieManagerInterfaceMock->expects($this->any())
            ->method('getCookie')
            ->willReturn(1);

        $subject = $this->createMock(Url::class);
        $result = 'www.test.com';

        $actualResult = $this->adminLoginRedirect->afterGetStartupPageUrl($subject, $result);

        $this->assertNotNull($actualResult);
    }

    /**
     * Test after get startup page withought if
     *
     * @return void
     */
    public function testAfterGetStartupPageUrlWithoutIf() : void
    {
        $this->redirectInterfaceMock->expects($this->once())
            ->method('getRefererUrl')
            ->willReturn('www.test.com');

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('explorers_non_standard_catalog')
            ->willReturn(false);
        
        $this->cookieManagerInterfaceMock->expects($this->any())
            ->method('getCookie')
            ->willReturn(0);

        $subject = $this->createMock(Url::class);
        $result = 'www.test.com';

        $actualResult = $this->adminLoginRedirect->afterGetStartupPageUrl($subject, $result);

        $this->assertNotNull($actualResult);
    }
}
