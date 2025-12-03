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
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Fedex\CatalogMvp\Controller\Index\MoveProduct;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Fedex\SelfReg\Model\Config as SelfRegConfig;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Class MoveProductTest
 *
 */
class MoveProductTest extends TestCase
{
    /**
     * @var (\Magento\Catalog\Model\Category & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $categoryMock;
    protected $requestMock;
    protected $productMock;
    protected $MoveProduct;
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

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    protected $productRepositoryMock;

    /**
     * @var CategoryRepositoryInterface|MockObject
     */
    protected $categoryRepositoryMock;

    /**
     * @var SelfRegConfig|MockObject
     */
    protected $selfRegConfigMock;

    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfigMock;

    protected function setUp(): void
    {
        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvpHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpSharedCatalogEnable', 'isSharedCatalogPermissionEnabled', 'assignProductToCategory','getCategoryUrl'])
            ->getMock();

        $this->categoryRepositoryMock = $this->getMockBuilder(CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get','save','delete','deleteByIdentifier'])
            ->getMock();

        $this->categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
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

        $this->contextMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);

        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById','save','get','delete','getList','deleteById'])
            ->getMock();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSku'])
            ->getMock();

        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setData'])
            ->getMock();

        $this->selfRegConfigMock = $this->getMockBuilder(SelfRegConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMoveCatalogItemMessage'])
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->MoveProduct = $objectManagerHelper->getObject(
            MoveProduct::class,
            [
                'logger' => $this->loggerMock,
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'catalogMvpHelper' => $this->catalogMvpHelperMock,
                'productRepository' => $this->productRepositoryMock,
                'categoryRepository' => $this->categoryRepositoryMock,
                'product' => $this->productMock,
                'context' => $this->contextMock,
                'request' => $this->requestMock,
                'selfRegConfig' => $this->selfRegConfigMock,
                'toggleConfig' => $this->toggleConfigMock
            ]
        );
    }

    /**
     * @test Execute if case with toggle disabled
     */
    public function testExecuteTryCase()
    {
        $expectedData = ['status' => true, 'message' => 'Your item has been moved. The item will be available in the new folder shortly.', 'url' => 'test'];

        $this->requestMock->expects($this->exactly(2))->method('getParam')
        ->withConsecutive(['cat_id'], ['id'])
        ->willReturnOnConsecutiveCalls(12, 2467);

        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->catalogMvpHelperMock->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('assignProductToCategory')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('getCategoryUrl')->willReturn('test');
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($this->categoryMock);
        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->productMock);
        $this->productMock->expects($this->any())->method('getSku')->willReturn('ec33512c-c435-4606-9805-67654288af3c');
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->resultJsonFactoryMock->expects($this->any())->method('setData')->willReturn($expectedData);
        $this->assertNotNull($this->MoveProduct->execute());
    }

    /**
     * @test Execute catch case
     */
    public function testExecuteCatchCase()
    {
        $expectedData = ['status' => false, 'message' => 'Exception message'];
        $phrase = new Phrase(__('Exception message'));
        $e = new \Exception($phrase);
        $this->requestMock->expects($this->exactly(2))->method('getParam')
        ->withConsecutive(['cat_id'], ['id'])
        ->willReturnOnConsecutiveCalls(12, 2467);
        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->catalogMvpHelperMock->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->productMock);
        $this->catalogMvpHelperMock->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->productMock->expects($this->any())->method('getSku')->willReturn('ec33512c-c435-4606-9805-67654288af3c');
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($this->categoryMock);
        $this->catalogMvpHelperMock->expects($this->any())->method('assignProductToCategory')->willThrowException($e);
        $this->resultJsonFactoryMock->expects($this->any())->method('setData')->willReturn($expectedData);
        $this->assertNotNull($this->MoveProduct->execute());
    }

    /**
     * @test Execute with catalog disabled
     */
    public function testExecuteTryCaseWithElse()
    {
        $expectedData = [];

        $this->requestMock->expects($this->exactly(2))->method('getParam')
        ->withConsecutive(['cat_id'], ['id'])
        ->willReturnOnConsecutiveCalls(12, 2467);

        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->catalogMvpHelperMock->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(false);
        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->productMock);
        $this->catalogMvpHelperMock->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(false);
        $this->productMock->expects($this->any())->method('getSku')->willReturn('ec33512c-c435-4606-9805-67654288af3c');
        $this->resultJsonFactoryMock->expects($this->any())->method('setData')->willReturn($expectedData);
        $this->assertEquals([], $this->MoveProduct->execute());
    }

    /**
     * @test Execute with toggle enabled and custom message
     */
    public function testExecuteWithToggleEnabledAndCustomMessage()
    {
        $customMessage = 'Custom move message from configuration';
        $expectedData = ['status' => true, 'message' => $customMessage, 'url' => 'test'];

        $this->requestMock->expects($this->exactly(2))->method('getParam')
            ->withConsecutive(['cat_id'], ['id'])
            ->willReturnOnConsecutiveCalls(12, 2467);

        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->catalogMvpHelperMock->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('assignProductToCategory')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('getCategoryUrl')->willReturn('test');
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($this->categoryMock);
        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->productMock);
        $this->productMock->expects($this->any())->method('getSku')->willReturn('ec33512c-c435-4606-9805-67654288af3c');
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->selfRegConfigMock->expects($this->any())->method('getMoveCatalogItemMessage')->willReturn($customMessage);
        $this->resultJsonFactoryMock->expects($this->any())->method('setData')->willReturn($expectedData);
        $this->assertNotNull($this->MoveProduct->execute());
    }

    /**
     * @test Execute with toggle enabled but empty custom message - should use default
     */
    public function testExecuteWithToggleEnabledButEmptyCustomMessage()
    {
        $expectedData = ['status' => true, 'message' => 'Your item has been moved. The item will be available in the new folder shortly.', 'url' => 'test'];

        $this->requestMock->expects($this->exactly(2))->method('getParam')
            ->withConsecutive(['cat_id'], ['id'])
            ->willReturnOnConsecutiveCalls(12, 2467);

        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->catalogMvpHelperMock->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('assignProductToCategory')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('getCategoryUrl')->willReturn('test');
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($this->categoryMock);
        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->productMock);
        $this->productMock->expects($this->any())->method('getSku')->willReturn('ec33512c-c435-4606-9805-67654288af3c');
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->selfRegConfigMock->expects($this->any())->method('getMoveCatalogItemMessage')->willReturn('');
        $this->resultJsonFactoryMock->expects($this->any())->method('setData')->willReturn($expectedData);
        $this->assertNotNull($this->MoveProduct->execute());
    }

    /**
     * @test Execute with toggle enabled but whitespace-only custom message - should use default
     */
    public function testExecuteWithToggleEnabledButWhitespaceCustomMessage()
    {
        $expectedData = ['status' => true, 'message' => 'Your item has been moved. The item will be available in the new folder shortly.', 'url' => 'test'];

        $this->requestMock->expects($this->exactly(2))->method('getParam')
            ->withConsecutive(['cat_id'], ['id'])
            ->willReturnOnConsecutiveCalls(12, 2467);

        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->catalogMvpHelperMock->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('assignProductToCategory')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('getCategoryUrl')->willReturn('test');
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($this->categoryMock);
        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($this->productMock);
        $this->productMock->expects($this->any())->method('getSku')->willReturn('ec33512c-c435-4606-9805-67654288af3c');
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->selfRegConfigMock->expects($this->any())->method('getMoveCatalogItemMessage')->willReturn('   ');
        $this->resultJsonFactoryMock->expects($this->any())->method('setData')->willReturn($expectedData);
        $this->assertNotNull($this->MoveProduct->execute());
    }
}
