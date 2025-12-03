<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model\UnifiedDataLayer\Source\Delivery;

use Fedex\SubmitOrderSidebar\Api\Data\LineItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Sales\Model\Order\Item;
use Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\Source\Delivery\ProducerTypeResolver;

class ProducerTypeResolverTest extends TestCase
{
    private const PRODUCER_TYPE_FEDEX = 'FedEx Office';
    private const PRODUCER_TYPE_SELLER = 'Mirakl Shop Name';

    /**
     * @var ProducerTypeResolver
     */
    private $producerTypeResolver;

    /**
     * @var Item|MockObject
     */
    private $itemMock;

    protected function setUp(): void
    {
        $this->producerTypeResolver = new ProducerTypeResolver();
        $this->itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()->setMethods([
                'getMiraklOfferId',
                'getMiraklShopName',
            ])
            ->getMockForAbstractClass();
    }

    /**
     * test resolve method Mirakl product
     */
    public function testGetProducerTypeMiraklProduct(): void
    {
        $this->itemMock->expects($this->once())
            ->method('getMiraklOfferId')
            ->willReturn(123);
        $this->itemMock->expects($this->once())
            ->method('getMiraklShopName')
            ->willReturn(self::PRODUCER_TYPE_SELLER);
        $this->assertEquals(self::PRODUCER_TYPE_SELLER, $this->producerTypeResolver->resolve($this->itemMock));
    }

    /**
     * test resolve method FedEx product
     */
    public function testGetProducerTypeFedExProduct(): void
    {
        $this->itemMock->expects($this->once())
            ->method('getMiraklOfferId')
            ->willReturn(null);
        $this->assertEquals(self::PRODUCER_TYPE_FEDEX, $this->producerTypeResolver->resolve($this->itemMock));
    }
}
