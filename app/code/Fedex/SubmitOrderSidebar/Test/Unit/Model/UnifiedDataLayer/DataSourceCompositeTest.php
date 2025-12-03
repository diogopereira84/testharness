<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model\UnifiedDataLayer;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\SubmitOrderSidebar\Api\Data\DataSourceInterface;
use Fedex\SubmitOrderSidebar\Model\Data\UnifiedDataLayerFactory;
use Fedex\SubmitOrderSidebar\Model\Data\UnifiedDataLayer;
use Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\DataSourceComposite;

class DataSourceCompositeTest extends TestCase
{
    protected $source1;
    protected $source2;
    protected $unifiedDataLayerMock;
    /**
     * @var UnifiedDataLayerFactory|MockObject
     */
    private $unifiedDataLayerFactoryMock;

    /**
     * @var DataSourceComposite
     */
    private $dataSourceComposite;

    protected function setUp(): void
    {
        $this->source1 = $this->createMock(DataSourceInterface::class);
        $this->source2 = $this->createMock(DataSourceInterface::class);
        $this->unifiedDataLayerMock = $this->createMock(UnifiedDataLayer::class);
        $this->unifiedDataLayerFactoryMock = $this->createMock(UnifiedDataLayerFactory::class);
        $this->dataSourceComposite = new DataSourceComposite($this->unifiedDataLayerFactoryMock, [
            $this->source1,
            $this->source2
        ]);
    }

    public function testCompose(): void
    {
        $this->unifiedDataLayerFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->unifiedDataLayerMock);

        $this->source1->expects($this->once())
            ->method('map')
            ->with($this->unifiedDataLayerMock, []);
        $this->source2->expects($this->once())
            ->method('map')
            ->with($this->unifiedDataLayerMock, []);

        $this->unifiedDataLayerMock->expects($this->once())
            ->method('toArray')
            ->willReturn(['some' => 'data']);

        $result = $this->dataSourceComposite->compose();

        $this->assertEquals(['some' => 'data'], $result);
    }
}
