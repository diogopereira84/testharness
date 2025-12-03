<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Test\Unit\Controller\Landing;

use Fedex\SelfReg\Controller\Landing\Index;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{

    /**
     * @var (\Magento\Framework\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $selfRegHelperMock;
    protected $redirectFactoryMock;
    protected $redirectMock;
    protected $toggleConfigMock;
    protected $storeManagerInterfaceMock;
    protected $storeInterfaceMock;
    protected $resultPageFactoryMock;
    protected $resultPageMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $laningObj;
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->selfRegHelperMock = $this->getMockBuilder(SelfReg::class)
            ->setMethods(['selfRegWlgnLogin','isSelfRegCompany','isSelfRegCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirectFactoryMock = $this->getMockBuilder(RedirectFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirectMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Redirect::class)
            ->setMethods(['setUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManagerInterfaceMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->setMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeInterfaceMock = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->setMethods(['getBaseUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->resultPageFactoryMock = $this->getMockBuilder(\Magento\Framework\View\Result\PageFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultPageMock = $this->getMockBuilder(\Magento\Framework\View\Result\Page::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->laningObj = $this->objectManager->getObject(
            Index::class,
            [
                'selfRegHelper' => $this->selfRegHelperMock,
                'resultRedirectFactory' => $this->redirectFactoryMock,
                'toggleConfig' => $this->toggleConfigMock,
                'storeManagerInterface' => $this->storeManagerInterfaceMock,
                'resultPageFactory' => $this->resultPageFactoryMock,
                '_redirect' => $this->redirectFactoryMock
            ]
        );
    }

    /**
     *
     * @test  testExecute
     */
    public function testExecute()
    {
        $baseUrl = "https://staging3.office.fedex.com";
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getBaseUrl')->willReturn($baseUrl);
        $this->redirectFactoryMock->expects($this->any())->method('create')->willReturn($this->redirectMock);
        $this->selfRegHelperMock->expects($this->any())->method('isSelfRegCompany')->willReturn(true);
        $this->selfRegHelperMock->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $result = $this->laningObj->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
    }

    /**
     *
     * @test  testExecute
     */
    public function testExecuteNotSelfCompany()
    {
        $baseUrl = "https://staging3.office.fedex.com";
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getBaseUrl')->willReturn($baseUrl);
        $this->redirectFactoryMock->expects($this->any())->method('create')->willReturn($this->redirectMock);
        $this->selfRegHelperMock->expects($this->any())->method('isSelfRegCompany')->willReturn(false);
        $this->selfRegHelperMock->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $result = $this->laningObj->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
    }

    /**
     *
     * @test  testExecute
     */
    public function testExecutePageRedirect()
    {
        $baseUrl = "https://staging3.office.fedex.com";
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getBaseUrl')->willReturn($baseUrl);
        $this->redirectFactoryMock->expects($this->any())->method('create')->willReturn($this->redirectMock);
        $this->selfRegHelperMock->expects($this->any())->method('isSelfRegCompany')->willReturn(true);
        $this->selfRegHelperMock->expects($this->any())->method('isSelfRegCustomer')->willReturn(false);
        $this->resultPageFactoryMock->expects($this->any())->method('create')->willReturn($this->resultPageMock);
        $result = $this->laningObj->execute();
        $this->assertInstanceOf(\Magento\Framework\View\Result\Page::class, $result);
    }
}
