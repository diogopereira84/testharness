<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\Catalog\Test\Unit\Controller\Product;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;
use Fedex\Catalog\Helper\Breadcrumbs as BreadcrumbsHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Catalog\Controller\Product\Breadcrumb;
use Magento\Framework\DataObject;

class BreadcrumbTest extends TestCase
{

	protected $resultJsonFactoryMock;
 protected $jsonValidatorMock;
 protected $storeManagerMock;
 protected $productRepositoryMock;
 protected $helperMock;
 protected $isDataObjectMock;
 protected $requestMock;
 protected $contextMock;
 protected $breadcrumbController;
 protected function setUp(): void
    {

        $this->resultJsonFactoryMock = $this
            ->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData','create'])
            ->getMock();

        $this->jsonValidatorMock = $this
            ->getMockBuilder(JsonValidator::class)
            ->disableOriginalConstructor()
            ->setMethods(['isValid'])
            ->getMock();

        $this->storeManagerMock = $this
            ->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore','getBaseUrl'])
            ->getMockForAbstractClass();

        $this->productRepositoryMock = $this
            ->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->helperMock = $this
            ->getMockBuilder(BreadcrumbsHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getControlJson'])
            ->getMock();
        

        $this->isDataObjectMock = $this->getMockBuilder(DataObject::class)
            ->setMethods(['getSku'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getPost',
                    'setPostValue',
                    'getPostValue',
                ]
            )
            ->getMockForAbstractClass();

        $this->contextMock = $this->createMock(Context::class);

        $objectManagerHelper = new ObjectManager($this);

        $this->breadcrumbController = $objectManagerHelper->getObject(
            Breadcrumb::class,
            [
                'context' => $this->contextMock,
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'jsonValidator' => $this->jsonValidatorMock,
                'helper' => $this->helperMock,
                'storeManager' => $this->storeManagerMock,
                'productRepository' => $this->productRepositoryMock,
                'request' => $this->requestMock,
                '_request' => $this->requestMock
            ]
        );
    }

    /**
     * testExecute 
     * 
     */
    public function testExecute()
    {
        $control = [['label'=>'Nk Template Test','url'=>'nk-template-test','skus'=>'1614105200640-4,1593103993699-4,1594830761054-4,1534434635598-4,1534436209752-2,1592421958159-4']];

        $controlJson = json_encode($control);

        $baseUrl = 'https://staging3.office.fedex.com/';

        $refererCrumb = ['label'=>'Nk Template Test','title'=>'Nk Template Test','skus'=>'1614105200640-4,1593103993699-4','link'=>'nk-template-test'];

        $output = ['data'=>json_encode($refererCrumb),'success'=>true];

        $jsonOutput = json_encode($output);

        $this->helperMock->expects($this->any())
            ->method('getControlJson')
            ->willReturn($controlJson);

        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturnSelf();

        $this->storeManagerMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn($baseUrl);

        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->requestMock
            ->method('getPost')
            ->withConsecutive(['ref'],['pid'])
            ->willReturnOnConsecutiveCalls('nk-template-test',12);


        $this->jsonValidatorMock->expects($this->any())
            ->method('isValid')
            ->willReturn(true);
       
        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->with(12)
            ->willReturn($this->isDataObjectMock);

        $this->isDataObjectMock->expects($this->any())
            ->method('getSku')
            ->willReturn('1534434635598-4');

        $this->resultJsonFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->resultJsonFactoryMock->expects($this->any())
            ->method('setData')
            ->willReturn($jsonOutput);

        $this->assertEquals($jsonOutput,$this->breadcrumbController->execute());
    }

}