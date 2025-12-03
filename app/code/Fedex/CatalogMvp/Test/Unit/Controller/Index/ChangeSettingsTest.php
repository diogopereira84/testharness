<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Controller;
 
use Exception;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Action\Context;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Controller\Index\ChangeSettings;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;
use Fedex\CatalogMvp\ViewModel\MvpHelper;
 
/**
 * Class ChangeRequestTest
 *
 */
class ChangeSettingsTest extends TestCase
{
    /**
     * @var (\Fedex\CatalogMvp\ViewModel\MvpHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $mvpHelperMock;
    protected $pageFactoryMock;
    /**
     * @var (\Magento\Framework\View\Result\Page & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $pageMock;
    protected $resultJsonMock;
    protected $requestMock;
    protected $changeSetting;
    /**
     * @var Context|MockObject
     */
    protected $contextMock;

 
    /**
     * @var JsonFactory|MockObject
     */
    protected $resultJsonFactoryMock;
 
    protected function setUp(): void
    {
             
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mvpHelperMock = $this->getMockBuilder(MvpHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
 
        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setData'])
            ->getMock();
        
        $this->pageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'getLayout', 'createBlock', 'setTemplate', 'setData', 'toHtml'])
            ->getMock();

        $this->pageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData','toHtml', 'getLayout', 'createBlock', 'setTemplate'])
            ->getMock();
            

        $this->resultJsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);
 
        $objectManagerHelper = new ObjectManager($this);
        $this->changeSetting = $objectManagerHelper->getObject(
            ChangeSettings::class,
            [
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'mvpHelper' => $this->mvpHelperMock,
                'pageFactory' => $this->pageFactoryMock,
                'context' => $this->contextMock
            ]
        );
    }

    /**
     * @test Execute if case
     */
    public function testExecuteTryCaseWithException()
    {
        $this->requestMock->expects($this->exactly(1))->method('getParam')
            ->withConsecutive(['sku'])
            ->willReturnOnConsecutiveCalls('test');
        $exception = new \Exception();
        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturn($this->resultJsonMock);
        $this->pageFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->pageFactoryMock->expects($this->any())->method('getLayout')->willReturnSelf();
        $this->pageFactoryMock->expects($this->any())->method('createBlock')->willReturnSelf();
        $this->pageFactoryMock->expects($this->any())->method('setTemplate')->willThrowException($exception);
        $this->pageFactoryMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->pageFactoryMock->expects($this->any())->method('toHtml')->willReturn('<h1></h1>');
        $this->resultJsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertNotNull($this->changeSetting->execute());
    }
}
