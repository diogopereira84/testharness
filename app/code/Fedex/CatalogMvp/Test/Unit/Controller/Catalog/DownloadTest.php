<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Test\Unit\Controller\Catalog;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\CatalogMvp\Helper\Download as DownloadHelper;
use Fedex\CatalogMvp\Controller\Catalog\Download;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;

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
     * @var CatalogDocumentRefranceApi|MockObject
     */
    protected $catalogDocumentMock;

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

        $this->catalogDocumentMock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProductObjectById'])
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
                'catalogDocument' => $this->catalogDocumentMock,
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
        $this->requestMock->expects($this->any())
            ->method('getPost')
            ->willReturnSelf();
        $product = ['name' => 'Test', 'external-prod' => 'Test'];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($product);
        $this->catalogDocumentMock->expects($this->any())
            ->method('getProductObjectById')
            ->willReturn($varienObject);
        $this->downloadHelperMock->expects($this->any())
            ->method('getDownloadFileUrl')
            ->willReturn("http://test.com");
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->resultFactoryMock->expects($this->once())
            ->method('setContents')
            ->willReturnSelf();

        $this->assertEquals($this->resultFactoryMock, $this->downloadMock->execute());
    }
}