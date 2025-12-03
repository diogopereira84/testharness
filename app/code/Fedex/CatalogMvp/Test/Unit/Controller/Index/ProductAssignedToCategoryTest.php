<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Controller;

use Exception;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Action\Context;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\CatalogMvp\Helper\CatalogMvp as CatalogMvpHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Controller\Index\ProductAssignedToCategory;
use Magento\Framework\App\RequestInterface;

/**
 * Class ProductAssignedToCategoryTest
 *
 */
class ProductAssignedToCategoryTest extends TestCase
{
    protected $requestMock;
    protected $productAssignedToCategory;
    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var JsonFactory|MockObject
     */
    protected $resultJsonFactoryMock;

    /**
     * @var CatalogMvpHelper|MockObject
     */
    protected $catalogMvpHelperMock;
    protected $requestInterMock;

    protected function setUp(): void
    {
        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvpHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpSharedCatalogEnable', 'isSharedCatalogPermissionEnabled', 'assignProductToCategory'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestInterMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);

        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setData'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->productAssignedToCategory = $objectManagerHelper->getObject(
            ProductAssignedToCategory::class,
            [
                'logger' => $this->loggerMock,
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'catalogMvpHelper' => $this->catalogMvpHelperMock,
                'context' => $this->contextMock,
                'request' => $this->requestInterMock
            ]
        );
    }

    /**
     * @test Execute if case
     */
    public function testExecuteTryCase()
    {
        $expectedData = ['status' => true, 'message' => 'Product moved successfully'];

        $this->requestInterMock->expects($this->exactly(2))->method('getParam')
        ->withConsecutive(['category_id'], ['product_sku'])
        ->willReturnOnConsecutiveCalls(12, 'test');

        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->catalogMvpHelperMock->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('assignProductToCategory')->willReturn(true);
        $this->resultJsonFactoryMock->expects($this->any())->method('setData')->willReturn($expectedData);
        $this->assertNotNull($this->productAssignedToCategory->execute());
    }

    /**
     * @test Execute if case
     */
    public function testExecuteCatchCase()
    {
        $expectedData = ['status' => false, 'message' => 'Exception message'];
        $phrase = new Phrase(__('Exception message'));
        $e = new \Exception($phrase);
        $this->requestInterMock->expects($this->exactly(2))->method('getParam')
        ->withConsecutive(['category_id'], ['product_sku'])
        ->willReturnOnConsecutiveCalls(12, 'test');
        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->catalogMvpHelperMock->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('assignProductToCategory')->willThrowException($e);
        $this->resultJsonFactoryMock->expects($this->any())->method('setData')->willReturn($expectedData);
        $this->assertNotNull($this->productAssignedToCategory->execute());
    }

    /**
     * @test Execute if case
     */
    public function testExecuteTryCaseWithElse()
    {
        $expectedData = [];

        $this->requestInterMock->expects($this->exactly(2))->method('getParam')
        ->withConsecutive(['category_id'], ['product_sku'])
        ->willReturnOnConsecutiveCalls(12, 'test');

        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->catalogMvpHelperMock->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(false);
        $this->catalogMvpHelperMock->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(false);
        $this->catalogMvpHelperMock->expects($this->any())->method('assignProductToCategory')->willReturn(true);
        $this->resultJsonFactoryMock->expects($this->any())->method('setData')->willReturn($expectedData);
        $this->assertEquals([], $this->productAssignedToCategory->execute());
    }
}
