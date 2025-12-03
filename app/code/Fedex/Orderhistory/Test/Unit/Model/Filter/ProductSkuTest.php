<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\Orderhistory\Test\Unit\Model\Filter;

use Magento\Framework\Api\Filter;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Sales\Model\Order\ItemRepository;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\Orderhistory\Model\Filter\ProductSku;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\ResourceModel\Order\item\Collection as ItemCollection;
use Magento\Framework\DB\Select;
use Magento\Framework\App\ResourceConnection;

/**
 * Class ProductSkuTest.
 *
 * Unit test for Product SKU filter.
 */
class ProductSkuTest extends TestCase
{
    /**
     * @var (\Magento\Sales\Model\ResourceModel\Order\item\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $ItemCollection;
    protected $resourceConnectionMock;
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var ItemRepository|MockObject
     */
    private $itemRepositoryMock;

    /**
     * @var ProductSku
     */
    private $productSkuModel;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {

        $this->itemRepositoryMock = $this
            ->getMockBuilder(ItemRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getList'])
            ->getMock();
        $this->ItemCollection = $this->getMockBuilder(ItemCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter','getIterator'])
            ->getMock();
       

        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->setMethods(['getTableName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->productSkuModel = $this->objectManagerHelper->getObject(
            ProductSku::class,
            [
                'itemRepository' => $this->itemRepositoryMock,
                'resourceConnection' => $this->resourceConnectionMock
            ]
        );
    }

    /**
     * Test applyFilter() method.
     *
     * B-1281434 - Order history search by product name
     * @return void
     */
    public function testApplyFilter()
    {
		$selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['joinLeft'])
            ->getMock();
            
        $this->resourceConnectionMock->expects($this->any())
            ->method('getTableName')->with('sales_order_item')->willReturn('sales_order_item');
            
        $collectionMock = $this
            ->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getSelect', 'group'])
            ->getMock();
        
        $collectionMock->expects($this->once())->method('getSelect')->willReturn($selectMock);
        $selectMock->expects($this->once())->method('joinLeft')->willReturn($collectionMock);
        $collectionMock->expects($this->once())->method('group')->willReturnSelf();
        $collectionMock->expects($this->once())->method('addFieldToFilter')->willReturnSelf();
            
        $value = 'shirt';
        $this->assertSame(
            $collectionMock,
            $this->productSkuModel->applyFilter($collectionMock, $value)
        );
    }
}