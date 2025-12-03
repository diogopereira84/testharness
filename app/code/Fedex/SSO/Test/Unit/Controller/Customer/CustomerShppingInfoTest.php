<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SSO\Test\Unit\Controller\Customer;

use PHPUnit\Framework\TestCase;
use Fedex\SSO\Controller\Customer\CustomerShppingInfo;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Message\ManagerInterface;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CustomerShppingInfoTest extends TestCase
{
    private $resultRawFactory;
    private $resultRaw;
    private $layoutFactory;
    private $layout;
    private $messageManager;
    private $ssoConfiguration;
    private $controller;
    private $toggleConfig;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->resultRawFactory = $this->createMock(RawFactory::class);
        $this->resultRaw = $this->createMock(Raw::class);
        $this->layoutFactory = $this->createMock(LayoutFactory::class);
        $this->layout = $this->createMock(Layout::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->ssoConfiguration = $this->createMock(SsoConfiguration::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);

        $this->controller = new CustomerShppingInfo(
            $this->resultRawFactory,
            $this->layoutFactory,
            $this->messageManager,
            $this->ssoConfiguration,
            $this->toggleConfig
        );
    }

    /**
     * @return void
     */
    public function testExecuteWithoutLogin()
    {
        $this->ssoConfiguration->method('isFclCustomer')->willReturn(false);
        $this->ssoConfiguration->method('getIsRequestFromSdeStoreFclLogin')->willReturn(false);
        $this->ssoConfiguration->method('isSelfRegCustomerWithFclEnabled')->willReturn(false);

        $this->resultRawFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRaw);

        $this->layoutFactory->expects($this->never())
            ->method('create');

        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with(__('Access denied.'));

        $result = $this->controller->execute();

        $this->assertSame($this->resultRaw, $result);
    }

    /**
     * @return void
     */
    public function testExecuteWithLogin()
    {
        $this->ssoConfiguration->method('isFclCustomer')->willReturn(true);
        $this->ssoConfiguration->method('getIsRequestFromSdeStoreFclLogin')->willReturn(false);
        $this->ssoConfiguration->method('isSelfRegCustomerWithFclEnabled')->willReturn(false);

        $this->resultRawFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRaw);

        $this->layoutFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->layout);

        $block = $this->createMock(Template::class);
        $block->expects($this->once())
            ->method('setTemplate')
            ->with('Fedex_SSO::customer/shipping_info.phtml')
            ->willReturnSelf();
        $block->expects($this->once())
            ->method('toHtml')
            ->willReturn('block_html_content');

        $this->layout->expects($this->once())
            ->method('createBlock')
            ->with(\Fedex\SSO\Block\LoginInfo::class)
            ->willReturn($block);

        $this->resultRaw->expects($this->once())
            ->method('setContents')
            ->with('block_html_content')
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->resultRaw, $result);
    }

    /**
     * @return void
     */
    public function testExecuteWithLoginSde()
    {
        $this->ssoConfiguration->method('isFclCustomer')->willReturn(false);
        $this->ssoConfiguration->method('getIsRequestFromSdeStoreFclLogin')->willReturn(true);
        $this->ssoConfiguration->method('isSelfRegCustomerWithFclEnabled')->willReturn(false);

        $this->resultRawFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRaw);

        $this->layoutFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->layout);

        $block = $this->createMock(Template::class);
        $block->expects($this->once())
            ->method('setTemplate')
            ->with('Fedex_SSO::customer/shipping_info.phtml')
            ->willReturnSelf();
        $block->expects($this->once())
            ->method('toHtml')
            ->willReturn('block_html_content');

        $this->layout->expects($this->once())
            ->method('createBlock')
            ->with(\Fedex\SSO\Block\LoginInfo::class)
            ->willReturn($block);

        $this->resultRaw->expects($this->once())
            ->method('setContents')
            ->with('block_html_content')
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->resultRaw, $result);
    }

    /**
     * @return void
     */
    public function testExecuteWithLoginSelfReg()
    {
        $this->ssoConfiguration->method('isFclCustomer')->willReturn(false);
        $this->ssoConfiguration->method('getIsRequestFromSdeStoreFclLogin')->willReturn(false);
        $this->ssoConfiguration->method('isSelfRegCustomerWithFclEnabled')->willReturn(true);

        $this->resultRawFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRaw);

        $this->layoutFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->layout);

        $block = $this->createMock(Template::class);
        $block->expects($this->once())
            ->method('setTemplate')
            ->with('Fedex_SSO::customer/shipping_info.phtml')
            ->willReturnSelf();
        $block->expects($this->once())
            ->method('toHtml')
            ->willReturn('block_html_content');

        $this->layout->expects($this->once())
            ->method('createBlock')
            ->with(\Fedex\SSO\Block\LoginInfo::class)
            ->willReturn($block);

        $this->resultRaw->expects($this->once())
            ->method('setContents')
            ->with('block_html_content')
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->resultRaw, $result);
    }
}
