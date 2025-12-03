<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Plugin;

use Fedex\Catalog\Model\Config;
use Fedex\MarketplaceProduct\Plugin\Mirakl\Mcm as McmPlugin;
use Magento\Framework\App\ResourceConnection;
use Mirakl\Mcm\Model\Product\Import\Adapter\Mcm as McmClass;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceToggle\Helper\Config as StoreConfig;
use Magento\SharedCatalog\Model\ProductManagement;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use PHPUnit\Framework\MockObject\MockObject;

class McmTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var |MockObject
     */
    protected $toggleConfig;

    /**
     * @var McmPlugin
     */
    protected $mcmPlugin;

    /**
     * @var McmClass
     */
    protected $mcmClass;

    /**
     * Product attributes
     * @var array
     */
    private array $productAttributes;

    /**
     * @var |MockObject
     */
    protected $productRepositoryMock;

    /**
     * @var |MockObject
     */
    protected $sharedCatalogMock;

    /**
     * @var |MockObject
     */
    protected $searchCriteriaBuilderMock;

    /**
     * @var |MockObject
     */
    protected $sharedCatalogRepositoryMock;


    /**
     * @var ResourceConnection|MockObject
     */
    protected $resourceConnectionMock;


    /**
     * @var Config|MockObject
     */
    protected $catalogConfigMock;

    /**
     * Is called before running a test
     */
    protected function setUp(): void
    {
        $this->mcmClass = $this->getMockBuilder(McmClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepositoryMock = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogMock = $this->getMockBuilder(ProductManagement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogRepositoryMock = $this->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->getMock();

        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mcmPlugin = new McmPlugin(
            $this->toggleConfig,
            $this->productRepositoryMock,
            $this->sharedCatalogMock,
            $this->searchCriteriaBuilderMock,
            $this->sharedCatalogRepositoryMock,
            $this->resourceConnectionMock,
            $this->catalogConfigMock
        );
    }

    /**
     *  Test the afterImport() method
     *
     * @return void
     */
    public function testAfterImport()
    {
        $expectedSharedCatalogId = 'sharedCatalogId';

        $searchCriteriaMock = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->getMock();

        $searchResultsMock = $this->getMockBuilder(SearchResultsInterface::class)
            ->getMock();

        $this->sharedCatalogRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($this->equalTo($searchCriteriaMock))
            ->willReturn($searchResultsMock);

        $searchResultsMock->expects($this->once())
            ->method('getTotalCount')
            ->willReturn(1);

        $itemMock = $this->getMockBuilder(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface::class)
            ->addMethods(['getEntityId'])
            ->getMockForAbstractClass();

        $itemMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn($expectedSharedCatalogId);

        $searchResultsMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$itemMock]);

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        $result = 'result';

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfig')
            ->willReturn(true);

        $this->sharedCatalogMock->expects($this->any())
            ->method('assignProducts')
            ->willReturn(null);

        $output = $this->mcmPlugin->afterImport($this->mcmClass, $result);

        $this->assertEquals($result, $output);
    }
}

