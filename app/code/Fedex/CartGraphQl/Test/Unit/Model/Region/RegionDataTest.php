<?php
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Region;

use Fedex\CartGraphQl\Model\Region\RegionData;
use Magento\Directory\Model\ResourceModel\Region\Collection;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\DataObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RegionDataTest extends TestCase
{
    /** @var CollectionFactory|MockObject */
    private $regionCollectionFactoryMock;

    /** @var Collection|MockObject */
    private $regionCollectionMock;

    /** @var RegionData */
    private $regionData;

    protected function setUp(): void
    {
        $this->regionCollectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->regionCollectionMock = $this->createMock(Collection::class);

        $this->regionData = new RegionData(
            $this->regionCollectionFactoryMock
        );
    }

    public function testGetRegionByCodeReturnsNullIfStateCodeEmpty(): void
    {
        $result = $this->regionData->getRegionByCode('');
        $this->assertNull($result);
    }

    public function testGetRegionByCodeReturnsFirstItem(): void
    {
        $stateCode = 'CA';
        $expectedRegion = new DataObject(['id' => 1, 'code' => 'CA']);

        $this->regionCollectionMock->expects($this->once())
            ->method('addRegionCodeFilter')
            ->with($stateCode)
            ->willReturnSelf();

        $this->regionCollectionMock->expects($this->once())
            ->method('addCountryFilter')
            ->with('US')
            ->willReturnSelf();

        $this->regionCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($expectedRegion);

        $this->regionCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->regionCollectionMock);

        $result = $this->regionData->getRegionByCode($stateCode);
        $this->assertSame($expectedRegion, $result);
    }

    public function testGetRegionByIdReturnsRegionCode(): void
    {
        $regionId = 123;
        $expectedCode = null;
        $region = new DataObject(['region_id' => $regionId, 'code' => $expectedCode]);

        $this->regionCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('main_table.region_id', $regionId)
            ->willReturnSelf();

        $this->regionCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($region);

        $this->regionCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->regionCollectionMock);

        $result = $this->regionData->getRegionById($regionId);
        $this->assertEquals($expectedCode, $result);
    }

    public function testGetRegionByIdReturnsNullWhenRegionNotFound(): void
    {
        $regionId = 999;
        $region = new DataObject(['region_id' => null, 'code' => null]);

        $this->regionCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('main_table.region_id', $regionId)
            ->willReturnSelf();

        $this->regionCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($region);

        $this->regionCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->regionCollectionMock);

        $result = $this->regionData->getRegionById($regionId);
        $this->assertNull($result);
    }
}
