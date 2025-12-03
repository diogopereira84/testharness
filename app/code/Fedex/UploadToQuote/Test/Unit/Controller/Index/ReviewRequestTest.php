<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Controller\Index;

use Magento\Customer\Model\Session as CustomerSession;
use PHPUnit\Framework\TestCase;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Fedex\UploadToQuote\Controller\Index\ReviewRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\Controller\ResultFactory;
use Fedex\UploadToQuote\Block\QuoteDetails;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class ReviewRequestTest extends TestCase
{
    protected $requestMock;
    /**
     * @var (\Magento\Framework\App\Response\Http & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $response;
    protected $reviewRequest;
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var PageFactory|MockObject
     */
    protected $resultPageFactory;

    /**
     * @var CustomerSession|MockObject
     */
    protected $customerSession;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactoryMock;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resultPageFactory = $this->getMockBuilder(PageFactory::class)
            ->setMethods(['create', 'getLayout', 'createBlock', 'setTemplate', 'toHtml', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPostValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['setSiItems'])
            ->getMock();
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setData'])
            ->getMock();
        $this->response = $this->createMock(ResponseHttp::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->reviewRequest = $this->objectManagerHelper->getObject(
            ReviewRequest::class,
            [
                'resultPageFactory' => $this->resultPageFactory,
                'customerSession' => $this->customerSession,
                '_request' => $this->requestMock,
                'response' => $this->response,
                'resultFactory' => $this->resultFactoryMock,
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
        $data = [
            'siItems' => [
                [
                    'productId' => '1234',
                    'productName' => 'test',
                    'si' => 'test',
                    'productImgUrl' => 'test-url',
                ],
                [
                    'productId' => '1234',
                    'productName' => 'test',
                    'si' => 'test',
                    'productImgUrl' => 'test-url',
                ],
            ]

        ];
        $this->customerSession->expects($this->once())->method('setSiItems')->willReturnSelf();
        $this->requestMock->expects($this->any())->method('getPostValue')->willReturn($data);
        $this->resultPageFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('getLayout')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('createBlock')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('setTemplate')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('setData')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('toHtml')->willReturnSelf();
        $this->resultFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->resultFactoryMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertNotEquals(null, $this->reviewRequest->execute());
    }
}
