<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\Account;

use Fedex\EnhancedProfile\Controller\Account\GetDefaultAddress;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Response\Http as ResponseHttp;

/**
 * Test class for Fedex\EnhancedProfile\Controller\Account\Preferences
 */
class GetDefaultAddressTest extends TestCase
{
    protected $defaultAddress;
    /**
     * @var PageFactory|MockObject
     */
    protected $resultPageFactory;

    /**
     * @var ResponseHttp|MockObject
     */
    protected $response;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManagerHelper;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * Test setUp
     */
    public function setUp(): void
    {
        $this->resultPageFactory = $this->getMockBuilder(PageFactory::class)
            ->setMethods(['create', 'getLayout', 'createBlock', 'setTemplate', 'toHtml', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPost'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->response = $this->createMock(ResponseHttp::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->defaultAddress = $this->objectManagerHelper->getObject(
            GetDefaultAddress::class,
            [
                'resultPageFactory' => $this->resultPageFactory,
                'request' => $this->requestMock,
                'response' => $this->response
            ]
        );
    }

    /**
     * Test testExecute method
     *
     * @return void
     */
    public function testExecute()
    {
        $this->requestMock->expects($this->any())->method('getPost')->willReturn('1');
        $this->resultPageFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('getLayout')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('createBlock')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('setTemplate')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('setData')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('toHtml')->willReturnSelf();
        $this->assertEquals(null, $this->defaultAddress->execute());
    }
}