<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Fedex\SharedCatalogCustomization\Test\Unit\Plugin\Catalog\Model\Product;

use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Copier;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\SharedCatalog\Api\ProductItemManagementInterface;
use Magento\SharedCatalog\Model\ProductSharedCatalogsLoader;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Fedex\SharedCatalogCustomization\Plugin\Catalog\Model\Product\AssignSharedCatalogOnDuplicateProductPlugin;
use Magento\Framework\Phrase;

/**
 * AssignSharedCatalogOnDuplicateProductPluginTest Repository Test
 */
class AssignSharedCatalogOnDuplicateProductPluginTest extends TestCase
{
    protected $sharedCatalogInterfaceMock;
    protected $assignSharedCatalogOnDuplicateProductPlugin;
    /**
     * test variables
     */
    private const SKU = 'test23234';
    private const TYPE= 1;
    /**
     * @var ProductSharedCatalogsLoader
     */
    private $productSharedCatalogsLoaderMock;
    /**
     * @var ProductItemManagementInterface
     */
    private $sharedCatalogProductItemManagementMock;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $loggerMock;
    /**
     * @var ObjectManager
     */
    private $objectManagerMock;
    /**
     * @var Subject
     */
    private $subjectMock;
    /**
     * @var Result
     */
    private $resultMock;
    /**
     * @var Product
     */
    private $productMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->objectManagerMock = new ObjectManager($this);
        $this->productSharedCatalogsLoaderMock = $this->getMockBuilder(ProductSharedCatalogsLoader::class)
            ->setMethods(['getAssignedSharedCatalogs','getType','getCustomerGroupId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogProductItemManagementMock = $this->getMockBuilder(ProductItemManagementInterface::class)
            ->setMethods(['addItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->setMethods(['critical'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->subjectMock = $this->getMockBuilder(Copier::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultMock = $this->getMockBuilder(Product::class)
            ->setMethods(['getSku','getType'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productMock = $this->getMockBuilder(Product::class)
            ->setMethods(['getSku','getType'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogInterfaceMock = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockforAbstractClass();
        $this->assignSharedCatalogOnDuplicateProductPlugin = $this->objectManagerMock->getObject(
            AssignSharedCatalogOnDuplicateProductPlugin::class,
            [
                'productSharedCatalogsLoader' => $this->productSharedCatalogsLoaderMock,
                'productItemManagement' => $this->sharedCatalogProductItemManagementMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * @test aftercopy method
     */
    public function testAfterCopy()
    {
        $this->productMock->expects($this->any())->method('getSku')->willReturn(self::SKU);
        $this->productSharedCatalogsLoaderMock->expects($this->any())
        ->method('getAssignedSharedCatalogs')->willReturn([$this->sharedCatalogInterfaceMock]);
        $this->productSharedCatalogsLoaderMock->expects($this->any())
        ->method('getType')->willReturnSelf();
        $this->sharedCatalogProductItemManagementMock->expects($this->any())
        ->method('addItems')->willReturnSelf();
        $this->resultMock->expects($this->any())->method('getSku')->willReturn(self::SKU);
        $this->assertIsObject($this->assignSharedCatalogOnDuplicateProductPlugin->afterCopy(
            $this->subjectMock,
            $this->resultMock,
            $this->productMock
        ));
    }

    /**
     * @test aftercopy with if statement method
     */
    public function testAfterCopyWithType()
    {
        $this->productMock->expects($this->any())->method('getSku')->willReturn(self::SKU);
        $this->productSharedCatalogsLoaderMock->expects($this->any())
        ->method('getAssignedSharedCatalogs')->willReturn([$this->sharedCatalogInterfaceMock]);
        $this->sharedCatalogInterfaceMock->expects($this->any())
        ->method('getType')->willReturn(self::TYPE);
        $this->sharedCatalogProductItemManagementMock->expects($this->any())
        ->method('addItems')->willReturnSelf();
        $this->resultMock->expects($this->any())->method('getSku')->willReturn(self::SKU);
        $this->assertIsObject($this->assignSharedCatalogOnDuplicateProductPlugin->afterCopy(
            $this->subjectMock,
            $this->resultMock,
            $this->productMock
        ));
    }
    /**
     * @test aftercopy with Exception
     */
    public function testafterCopyTestWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->productMock->expects($this->any())->method('getSku')->willReturn(self::SKU);
        $this->productSharedCatalogsLoaderMock->expects($this->any())
        ->method('getAssignedSharedCatalogs')->willReturn([$this->sharedCatalogInterfaceMock]);
        $this->sharedCatalogInterfaceMock->expects($this->any())
        ->method('getType')->willThrowException($exception);
        $this->assertIsObject($this->assignSharedCatalogOnDuplicateProductPlugin->afterCopy(
            $this->subjectMock,
            $this->resultMock,
            $this->productMock
        ));
    }
}
