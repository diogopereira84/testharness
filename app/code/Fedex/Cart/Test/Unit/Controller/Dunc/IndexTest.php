<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Test\Unit\Controller\Dunc;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Cart\Controller\Dunc\Index;
use Magento\Framework\Phrase;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

class IndexTest extends \PHPUnit\Framework\TestCase
{
    protected $jsonFactoryMock;
    protected $toggleConfigMock;
    protected $curlMock;
    protected $customerSessionMock;
    protected $configInterfaceMock;
    protected $jsonMock;
    protected $index;
    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var Sidebar|MockObject
     */
    protected $sidebar;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerInterface;

    /**
     * @var Data|MockObject
     */
    protected $data;

    /**
     * @var Item|MockObject
     */
    protected $item;

    /**
     * @var StockRegistryInterface|MockObject
     */
    protected $stockRegistryInterface;

    /**
     * @var RequestInterface|MockObject
     */
    protected $request;

    /**
     * @var ResponseInterface|MockObject
     */
    protected $responseInterface;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var UpdateItemQty|MockObject
     */
    protected $updateItemQty;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
             ->setMethods(['getRequest','getPostValue'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
        ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->curlMock = $this->getMockBuilder(Curl::class)
            ->setMethods(['getBody', 'setOptions', 'get'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->setMethods(['getDuncResponse','setDuncResponse'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->configInterfaceMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
            
            $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPostValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

            $this->context->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);

            $this->jsonMock      = $this->getMockBuilder(Json::class)
            ->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $objectManager = new ObjectManager($this);
        $this->index = $objectManager->getObject(
            Index::class,
            [
                'context' => $this->context,
                'resultJsonFactory' => $this->jsonFactoryMock ,
                'toggleConfig' => $this->toggleConfigMock,
                'curl' => $this->curlMock,
                'customerSession' => $this->customerSessionMock,
                'configInterface' => $this->configInterfaceMock,
                'logger' => $this->loggerInterface,
            ]
        );
    }

    /**
     * Test for Execute
     */
    public function testExecute()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->customerSessionMock->expects($this->any())->method('getDuncResponse')
        ->willReturn(['imagedataer'=> "kajdfkjakdsfjakdjfkajdfkadf"]);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->context->expects($this->any())->method('getRequest')->willReturnSelf();
        $this->testcallDuncApi();
        $this->request->expects($this->any())->method('getPostValue')->willReturn('imagedata');
        $this->customerSessionMock->expects($this->any())->method('setDuncResponse')->willReturnSelf();
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        

        $this->assertNotNull($this->index->execute());
    }

    public function testcallDuncApi()
    {
        $response = json_encode([
            'sucessful' => true,
            'output'=>[
                'imageByteStream' =>'adKAJSDKJASKDjAKSJDLKASFlaksdjflkajdflkjakdfjakfdjalkjfklajdf'
            ]
            ]);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->configInterfaceMock->expects($this->any())->method('getValue')->willReturn('file:///var/www/html/staging3.office.fedex.com/coverage/app/code/Fedex/Cart/Controller/Dunc/Index.php.html#68');
        $this->curlMock->expects($this->any())->method('getBody')->willreturn($response);
        $this->assertNotNull($this->index->callDuncApi('298283484kjdfkjsadf'));

    }

        /**
     * testExecuteWithExecption
     */
    public function testExecuteWithExecption()
    {

        $phrase = new Phrase(__('Exception message'));
        $exception = new \Exception();
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->customerSessionMock->expects($this->any())->method('getDuncResponse')
        ->willReturnSelf(['imagedataer'=> "kajdfkjakdsfjakdjfkajdfkadf"]);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->context->expects($this->any())->method('getRequest')->willReturnSelf();
        $this->testcallDuncApi();
        $this->request->expects($this->any())->method('getPostValue')->willThrowException($exception);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertNotNull($this->index->execute());
    }

   
}
