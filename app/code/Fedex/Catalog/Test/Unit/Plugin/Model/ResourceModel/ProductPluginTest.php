<?php

namespace Fedex\Catalog\Test\Unit\Model\ResourceModel;

use Fedex\Catalog\Plugin\Model\ResourceModel\ProductPlugin;
use Fedex\EnvironmentManager\Model\Config\SharedCatalogProductShowingInPrintProducts;
use Fedex\Ondemand\Api\Data\ConfigInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryLinkRepository;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Session\SessionManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product\AttributeSet;
use Magento\Catalog\Api\Data\CategoryInterface;

class ProductPluginTest extends TestCase
{
    protected $attributeSetMock;
    protected $productResource;
    protected $abstractModel;
    private MockObject|CategoryRepositoryInterface $categoryRepository;
    private MockObject|AttributeSetRepositoryInterface $attributeSetRepository;
    private MockObject|CategoryLinkRepository $categoryLinkRepository;
    private MockObject|SessionManagerInterface $sessionManager;
    private MockObject|ConfigInterface $ondemandConfig;
    private MockObject|SharedCatalogProductShowingInPrintProducts $catalogItemShowingInPrintProducts;
    private MockObject|LoggerInterface $logger;
    private ProductPlugin $productPlugin;
    private MockObject|ToggleConfig $toggleConfig;

    protected function setUp(): void
    {
        $this->categoryRepository = $this->getMockBuilder(CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeSetRepository = $this->getMockBuilder(AttributeSetRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryLinkRepository = $this->getMockBuilder(CategoryLinkRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionManager = $this->getMockBuilder(SessionManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasPrintProductsCatIds', 'getPrintProductsCatIds', 'setPrintProductsCatIds'])
            ->getMockForAbstractClass();

        $this->ondemandConfig = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogItemShowingInPrintProducts = $this->getMockBuilder(SharedCatalogProductShowingInPrintProducts::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeSetMock = $this->getMockBuilder(AttributeSet::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetName'])
            ->getMock();

        $this->productResource = $this->getMockBuilder(ProductResource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->abstractModel = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId', 'getCategoryIds', 'getSku'])
            ->getMock();

        $this->productPlugin = new ProductPlugin(
            $this->categoryRepository,
            $this->attributeSetRepository,
            $this->categoryLinkRepository,
            $this->sessionManager,
            $this->ondemandConfig,
            $this->catalogItemShowingInPrintProducts,
            $this->logger
        );
    }

    public function testAfterSaveIfNotPrintOnDemand()
    {
        $attributeSetId = 1;
        $attributeSetName = "PrintOnDemands";

        $this->catalogItemShowingInPrintProducts->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        $this->abstractModel->method('getAttributeSetId')->willReturn($attributeSetId);

        $this->attributeSetMock->expects($this->once())
            ->method('getAttributeSetName')
            ->willReturn($attributeSetName);

        $this->attributeSetRepository->expects($this->once())
            ->method('get')
            ->with($attributeSetId)
            ->willReturn($this->attributeSetMock);

        // Execute the method under test
        $result = $this->productPlugin->afterSave(
            $this->productResource,
            'result',
            $this->abstractModel
        );

        $this->assertEquals('result', $result);
    }

    public function testAfterSaveExceptionNoSuchEntity()
    {
        $attributeSetId = 1;

        $this->catalogItemShowingInPrintProducts->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        $this->abstractModel->method('getAttributeSetId')->willReturn($attributeSetId);

        $this->attributeSetRepository->expects($this->once())
            ->method('get')
            ->with($attributeSetId)
            ->willThrowException(new NoSuchEntityException(__('Attribute set id not found')));

        // Execute the method under test
        $result = $this->productPlugin->afterSave(
            $this->productResource,
            'result',
            $this->abstractModel
        );

        $this->assertEquals('result', $result);
    }

    public function testAfterSaveException()
    {
        $attributeSetId = 1;

        $this->catalogItemShowingInPrintProducts->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        $this->abstractModel->method('getAttributeSetId')->willReturn($attributeSetId);

        $this->attributeSetRepository->expects($this->once())
            ->method('get')
            ->with($attributeSetId)
            ->willThrowException(new \Exception(__('not found data')));

        // Execute the method under test
        $result = $this->productPlugin->afterSave(
            $this->productResource,
            'result',
            $this->abstractModel
        );

        $this->assertEquals('result', $result);
    }

    public function testAfterSaveDisableToggle()
    {
        $this->catalogItemShowingInPrintProducts->expects($this->once())
            ->method('isActive')
            ->willReturn(false);

        $result = $this->productPlugin->afterSave(
            $this->productResource,
            'result',
            $this->abstractModel
        );

        $this->assertEquals('result', $result);
    }

    public function testAfterSaveFetchCategoryIdsAndStoreInSession(): void
    {
        $attributeSetId = 1;
        $attributeSetName = "PrintOnDemand";
        $categoryIds = [2, 4];
        $printProductsCatIds = [2, 3];

        $this->catalogItemShowingInPrintProducts->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        // Configure the mocks
        $this->abstractModel->method('getAttributeSetId')->willReturn($attributeSetId);
        $this->abstractModel->method('getCategoryIds')->willReturn($categoryIds);
        $this->abstractModel->method('getSku')->willReturn('test-sku');

        $this->attributeSetMock->expects($this->once())
            ->method('getAttributeSetName')
            ->willReturn($attributeSetName);

        $this->attributeSetRepository->expects($this->once())
            ->method('get')
            ->with($attributeSetId)
            ->willReturn($this->attributeSetMock);

        $this->sessionManager
            ->method('hasPrintProductsCatIds')
            ->willReturn(false);

        $this->ondemandConfig
            ->method('getB2bPrintProductsCategory')
            ->willReturn(1);

        $categoryMock1 = $this->createMockCategoryInterface();
        $categoryMock1->method('getId')->willReturn(1);
        $categoryMock1->method('getChildrenCategories')->willReturn([$this->createMockChildCategory(2)]);

        $categoryMock2 = $this->createMockCategoryInterface();
        $categoryMock2->method('getId')->willReturn(2);
        $categoryMock2->method('getChildrenCategories')->willReturn([$this->createMockChildCategory(4)]);

        $categoryMock3 = $this->createMockCategoryInterface();
        $categoryMock3->method('getId')->willReturn(4);
        $categoryMock3->method('getChildrenCategories')->willReturn([]);

        // Expectations
        $this->categoryRepository->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive([1], [2], [4])
            ->willReturnOnConsecutiveCalls($categoryMock1, $categoryMock2, $categoryMock3);

        $this->sessionManager->expects($this->any())
            ->method('setPrintProductsCatIds')
            ->with($this->equalTo($printProductsCatIds));

        $this->categoryLinkRepository->expects($this->any())
            ->method('deleteByIds');

        // Execute the method under test
        $result = $this->productPlugin->afterSave(
            $this->productResource,
            'result',
            $this->abstractModel
        );

        $this->assertEquals('result', $result);
    }

    public function testAfterSaveFetchCategoryIdsFromStoreSession(): void
    {
        $attributeSetId = 1;
        $attributeSetName = "PrintOnDemand";
        $categoryIds = [2, 4];
        $printProductsCatIds = [2, 3];

        $this->catalogItemShowingInPrintProducts->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        // Configure the mocks
        $this->abstractModel->method('getAttributeSetId')->willReturn($attributeSetId);
        $this->abstractModel->method('getCategoryIds')->willReturn($categoryIds);
        $this->abstractModel->method('getSku')->willReturn('test-sku');

        $this->attributeSetMock->expects($this->once())
            ->method('getAttributeSetName')
            ->willReturn($attributeSetName);

        $this->attributeSetRepository->expects($this->once())
            ->method('get')
            ->with($attributeSetId)
            ->willReturn($this->attributeSetMock);

        $this->sessionManager
            ->method('hasPrintProductsCatIds')
            ->willReturn(true);

        $this->sessionManager->expects($this->once())
            ->method('getPrintProductsCatIds')
            ->willReturn([1, 2, 4]);

        $this->categoryLinkRepository->expects($this->any())
            ->method('deleteByIds');

        // Execute the method under test
        $result = $this->productPlugin->afterSave(
            $this->productResource,
            'result',
            $this->abstractModel
        );

        $this->assertEquals('result', $result);
    }

    protected function createMockChildCategory($id): object
    {
        $childCategoryMock = $this->getMockBuilder(CategoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getChildrenCategories', 'getId'])
            ->getMockForAbstractClass();
        $childCategoryMock->method('getId')->willReturn($id);
        
        return $childCategoryMock;
    }

    protected function createMockCategoryInterface(): object
    {
        return $this->getMockBuilder(CategoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getChildrenCategories', 'getId'])
            ->getMockForAbstractClass();
    }
}
