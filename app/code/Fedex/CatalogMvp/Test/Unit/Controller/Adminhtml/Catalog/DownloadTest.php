<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Test\Unit\Controller\Adminhtml\Catalog;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\CatalogMvp\Helper\Download as DownloadHelper;
use Fedex\CatalogMvp\Controller\Adminhtml\Catalog\Download;

class DownloadTest extends TestCase
{
    protected $downloadMock;
    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestInterfaceMock;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var DownloadHelper|MockObject
     */
    protected $downloadHelperMock;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setContents'])
            ->getMock();

        $this->downloadHelperMock = $this->getMockBuilder(DownloadHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDownloadFileUrl'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPost'])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->downloadMock = $this->objectManager->getObject(
            Download::class,
            [
                'resultFactory' => $this->resultFactoryMock,
                'downloadHelper' => $this->downloadHelperMock,
                'request' => $this->requestMock
            ]
        );
    }

    /**
     * Test execute
     *
     * @return void
     */
    public function testExecute()
    {
        $this->requestMock
            ->method('getPost')
            ->withConsecutive(
                ['productName'],
                ['externalProd']
            )
            ->willReturnOnConsecutiveCalls(
                'Test Product',
                '{}'
            );
        $this->downloadHelperMock->expects($this->once())
            ->method('getDownloadFileUrl')
            ->willReturn("https://test.com");
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->resultFactoryMock->expects($this->once())
            ->method('setContents')
            ->willReturnSelf();

        $this->assertEquals($this->resultFactoryMock, $this->downloadMock->execute());
    }
}