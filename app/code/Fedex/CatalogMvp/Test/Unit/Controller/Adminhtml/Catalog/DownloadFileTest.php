<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Test\Unit\Controller\Adminhtml\Catalog;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\CatalogMvp\Controller\Adminhtml\Catalog\DownloadFile;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;

class DownloadFileTest extends TestCase
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

        $this->catalogDocumentMock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['readZipFileContent'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->downloadMock = $this->objectManager->getObject(
            DownloadFile::class,
            [
                'resultFactory' => $this->resultFactoryMock,
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

        $this->requestMock
            ->method('getParam')
            ->withConsecutive(
                ['fileurl'],
                ['filename']
            )
            ->willReturnOnConsecutiveCalls(
                base64_encode('fileurl'),
                'NewProductDoc'
            );
       
        $this->catalogDocumentMock->expects($this->any())
            ->method('readZipFileContent')
            ->willReturn("");

        $this->downloadMock->execute();
    }
}