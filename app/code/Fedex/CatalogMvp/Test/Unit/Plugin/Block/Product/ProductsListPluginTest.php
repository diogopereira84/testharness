<?php

namespace Fedex\CatalogMvp\Test\Unit\Plugin\Block\Product;

use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CatalogMvp\Plugin\Block\Product\ProductsListPlugin;
use PHPUnit\Framework\TestCase;
use Magento\CatalogWidget\Block\Product\ProductsList;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Widget\Helper\Conditions;
use Magento\CatalogWidget\Model\Rule;
use Magento\CatalogWidget\Model\Rule\Condition\Combine as CombineRule;
use Magento\Rule\Model\Condition\Sql\Builder as SqlBuilder;

class ProductsListPluginTest extends TestCase
{
    protected $ruleMock;
    private object $plugin;
    private ProductsList|MockObject $subjectMock;
    private \Closure $proceedStub;
    private MockObject|CatalogMvp $catalogMvpHelperMock;
    private CollectionFactory|MockObject $productCollectionFactoryMock;
    private MockObject|ProductCollection $productCollectionMock;
    private MockObject|Conditions $conditionsHelperMock;
    private MockObject|SqlBuilder $sqlBuilderMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->subjectMock = $this->getMockBuilder(ProductsList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productCollectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productCollectionMock = $this->getMockBuilder(ProductCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->conditionsHelperMock = $this->getMockBuilder(Conditions::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ruleMock = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->plugin = $objectManager->getObject(
            ProductsListPlugin::class,
            [
                'productCollectionFactory' => $this->productCollectionFactoryMock,
                'conditionsHelper' => $this->conditionsHelperMock,
                'rule' => $this->ruleMock,
                'catalogMvpHelper' => $this->catalogMvpHelperMock
            ]
        );
        $this->proceedStub = function () {
            return $this->productCollectionMock;
        };
    }

    public function testAroundCreateCollectionWithPerformanceEnabled()
    {
        $this->productCollectionMock->method('addAttributeToSelect')
            ->willReturnSelf();
        $this->productCollectionMock->method('addUrlRewrite')
            ->willReturnSelf();
        $this->productCollectionMock->method('addStoreFilter')
            ->willReturnSelf();
        $this->productCollectionMock->method('addAttributeToSort')
            ->willReturnSelf();
        $this->productCollectionMock->method('setPageSize')
            ->willReturnSelf();
        $this->productCollectionMock->method('setCurPage')
            ->willReturnSelf();
        $this->productCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->productCollectionMock);
        $this->subjectMock->method('getData')
            ->with('conditions_encoded')
            ->willReturn('^[`1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,`aggregator`:`all`,`value`:`1`,`new_child`:``^]^]');
        $this->conditionsHelperMock->method('decode')->willReturn(
            [
                "1" => [
                    "type" => "Magento\CatalogWidget\Model\Rule\Condition\Combine",
                    "aggregator" => "all",
                    "value" => "1",
                    "new_child" => ""
                ]
            ]
        );
        $combineRuleMock = $this->getMockBuilder(CombineRule::class)
            ->disableOriginalConstructor()
            ->getMock();
        $combineRuleMock->method('collectValidatedAttributes')->willReturnSelf();
        $this->ruleMock->method('getConditions')->willReturn($combineRuleMock);
        $this->assertInstanceOf(
            ProductCollection::class,
            $this->plugin->aroundCreateCollection($this->subjectMock, $this->proceedStub)
        );
    }

}
