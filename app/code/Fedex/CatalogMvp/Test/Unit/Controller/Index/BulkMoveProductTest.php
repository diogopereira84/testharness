<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Controller\Index\BulkMoveProduct;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Product;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;
use Fedex\CatalogMvp\Helper\CatalogMvp;

/**
 * Class BulkMoveProduct
 * Handle the BulkMoveProduct test cases of the CatalogMvp controller
 */
class BulkMoveProductTest extends TestCase
{

    protected $productRepositoryMock;
    protected $productMock;
    protected $requestMock;
    protected $loggerMock;
    protected $contextMock;
    protected $jsonFactoryMock;
    protected $helperMock;
    protected $catalogMvp;
    const ID = ['1947,1'];
    const CATID = 14;

    protected $registry;
    protected $product;
    protected $context;
    protected $logger;
    protected $helper;

    protected function setUp(): void
    {
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById','save','get','delete','getList','deleteById'])
            ->getMock();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSku'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','setData'])
            ->getMock();

        $this->helperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpSharedCatalogEnable', 'isSharedCatalogPermissionEnabled', 'assignProductToCategory','getCategoryUrl'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->catalogMvp = $objectManagerHelper->getObject(
            BulkMoveProduct::class,
            [
                'product' => $this->productMock,
                'productRepository' => $this->productRepositoryMock,
                'resultJsonFactory' => $this->jsonFactoryMock,
                'logger' => $this->loggerMock,
                'context' => $this->contextMock,
                'catalogMvpHelper' => $this->helperMock,
                'request' => $this->requestMock
            ]
        );
    }

    /**
     * @test Execute try case
     */
    public function testExecuteTryCase()
    {
        $phrase = new Phrase(__('Your items have been moved. The items will be available in the new folder shortly.'));
        $expectedData = ['status' => true,'message' => $phrase, 'url' => 'test'];

        $this->helperMock->expects($this->any())
        ->method('isMvpSharedCatalogEnable')->willReturn(true);

        $this->helperMock->expects($this->any())
        ->method('isSharedCatalogPermissionEnabled')->willReturn(true);

        $this->prepareRequestMock();

        $this->jsonFactoryMock->expects($this->any())
        ->method('create')->willReturnSelf();

        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->productMock);

        $this->productMock->expects($this->any())->method('getSku')->willReturn('ec33512c-c435-4606-9805-67654288af3c');

        $this->helperMock->expects($this->any())->method('assignProductToCategory')->willReturn(true);

        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturn($expectedData);

        $this->assertNotNull($this->catalogMvp->execute());
    }

    /**
     * @test Execute try case
     */
    public function testExecuteCatchCase()
    {
        $phrase = new Phrase(__('Exception message'));

        $expectedData = [];

        $e = new \Exception($phrase);

        $this->helperMock->expects($this->any())
        ->method('isMvpSharedCatalogEnable')->willReturn(true);

        $this->helperMock->expects($this->any())
        ->method('isSharedCatalogPermissionEnabled')->willReturn(true);

        $this->prepareRequestMock();

        $this->jsonFactoryMock->expects($this->any())
        ->method('create')->willReturnSelf();

        $this->productRepositoryMock->expects($this->any())->method('getById')->willThrowException($e);
        $this->jsonFactoryMock->expects($this->any())->method('setData')->willReturn($expectedData);

        $this->assertEquals([], $this->catalogMvp->execute());
    }

    /**
     * @test response test case when folder is moved
     */

    public function testResponsForProductMove()
    {
        $phrase = new Phrase(__('Your items have been moved. The items will be available in the new folder shortly.'));
        $this->assertEquals(['status' => true,'message' => $phrase, 'url' => null], $this->catalogMvp->response(1,12));
    }


    /**
     * Prepare Request Mock.
     *
     * @return void
     */
    private function prepareRequestMock()
    {
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn(SELF::ID);

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn(SELF::CATID);
    }

}
