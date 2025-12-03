<?php
namespace Fedex\LateOrdersGraphQl\Test\Unit\Model;

use Fedex\LateOrdersGraphQl\Model\OrderDetailsAssembler;
use Fedex\Cart\Api\CartIntegrationNoteRepositoryInterface;
use Fedex\Cart\ViewModel\CheckoutConfig;
use Fedex\LateOrdersGraphQl\Model\Data\CustomerDTO;
use Fedex\LateOrdersGraphQl\Model\Data\StoreRefDTO;
use Fedex\LateOrdersGraphQl\Model\Data\OrderDetailsDTO;
use Fedex\Shipment\Api\ProducingAddressServiceInterface;
use Fedex\Shipment\Model\ProducingAddress;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use PHPUnit\Framework\TestCase;

class OrderDetailsAssemblerTest extends TestCase
{
    private $checkoutConfig;
    private $cartIntegrationNoteRepository;
    private $producingAddressService;
    private $assembler;

    protected function setUp(): void
    {
        $this->checkoutConfig = $this->createMock(CheckoutConfig::class);
        $this->cartIntegrationNoteRepository = $this->createMock(CartIntegrationNoteRepositoryInterface::class);
        $this->producingAddressService = $this->createMock(ProducingAddressServiceInterface::class);
        $this->assembler = new OrderDetailsAssembler(
            $this->checkoutConfig,
            $this->cartIntegrationNoteRepository,
            $this->producingAddressService
        );
    }

    public function testAssembleReturnsOrderDetails()
    {
        $order = $this->getMockBuilder(OrderInterface::class)
            ->onlyMethods([
                'getCustomerFirstname', 'getCustomerLastname',
                'getCustomerEmail',  'getIncrementId', 'getStatus',
                'getCreatedAt', 'getQuoteId', 'getItems'
            ])
            ->addMethods(['getId'])
            ->addMethods(['getStore', 'getShippingAddress', 'getShippingMethod', 'getData', 'getShipmentsCollection'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $order->method('getCustomerFirstname')->willReturn('John');
        $order->method('getCustomerLastname')->willReturn('Doe');
        $order->method('getCustomerEmail')->willReturn('john@example.com');
        $order->method('getId')->willReturn('123');
        $order->method('getIncrementId')->willReturn('1000001');
        $order->method('getStatus')->willReturn('processing');
        $order->method('getCreatedAt')->willReturn('2025-10-01 12:00:00');
        $order->method('getShippingMethod')->willReturn('fedexshipping_PICKUP');
        $order->method('getShipmentsCollection')->willReturn([]);
        $producingAddress = $this->getMockBuilder(ProducingAddress::class)
            ->addMethods(['getPhoneNumber', 'getEmailAddress', 'getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMock();
        $producingAddress->expects($this->once())
            ->method('getPhoneNumber')
            ->willReturn('703.734.3204');
        $producingAddress->expects($this->once())
            ->method('getEmailAddress')
            ->willReturn('usa1821@fedex.com');
        $producingAddress->expects($this->once())
            ->method('getAdditionalData')
            ->willReturn('{"estimated_time":null,"estimated_duration":null,"responsible_location_id":"WGOKO"}');
        $this->producingAddressService->expects($this->once())
            ->method('getByOrderId')
            ->with('123')
            ->willReturn($producingAddress);
        // Mock shipping address with all required methods
        $shippingAddress = $this->getMockBuilder('stdClass')
            ->addMethods([
                'getTelephone', 'getStreetLine', 'getCity', 'getRegion', 'getPostcode', 'getCountryId'
            ])->getMock();
        $shippingAddress->method('getTelephone')->willReturn('123456789');
        $shippingAddress->method('getStreetLine')->willReturnMap([
            [1, '123 Main St'],
            [2, 'Apt 4B']
        ]);
        $shippingAddress->method('getCity')->willReturn('New York');
        $shippingAddress->method('getRegion')->willReturn('NY');
        $shippingAddress->method('getPostcode')->willReturn('10001');
        $shippingAddress->method('getCountryId')->willReturn('US');
        $order->method('getShippingAddress')->willReturn($shippingAddress);
        $orderItem = $this->getMockBuilder(OrderItemInterface::class)
            ->addMethods(['getProductOptionByCode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderItem->method('getProductOptionByCode')->willReturn([
            'external_prod' => [[
                'id' => 'PID123',
                'instanceId' => 'INST1',
                'contentAssociations' => [
                    ['contentReference' => 'DOC1'],
                    ['contentReference' => 'DOC2']
                ],
                'features' => [
                    ['name' => 'color', 'choice' => ['name' => 'blue']]
                ],
                'properties' => [
                    ['name' => 'CUSTOMER_SI', 'value' => 'Special instructions']
                ]
            ]]
        ]);
        $order->method('getItems')->willReturn([$orderItem]);
        $this->checkoutConfig->method('getDocumentImagePreviewUrl')->willReturn('http://example.com/');
        $result = $this->assembler->assemble($order);
        $this->assertInstanceOf(OrderDetailsDTO::class, $result);
        $this->assertEquals('1000001', $result->getOrderId());
        $this->assertEquals('processing', $result->getStatus());
        $this->assertEquals('2025-10-01 12:00:00', $result->getCreatedAt());
        $this->assertInstanceOf(CustomerDTO::class, $result->getCustomer());
        $this->assertInstanceOf(StoreRefDTO::class, $result->getStore());
        // Check getItems returns correct structure
        $items = $result->getItems();
        $this->assertCount(1, $items);
        $item = $items[0];
        $this->assertEquals('PID123', $item->getProductId());
        $this->assertEquals(['DOC1', 'DOC2'], $item->getDocumentId());
        $this->assertCount(1, $item->getProductConfiguration());
        $this->assertEquals('color', $item->getProductConfiguration()[0]->getKey());
        $this->assertEquals('blue', $item->getProductConfiguration()[0]->getValue());
        $this->assertEquals(['CUSTOMER_SI' => 'Special instructions'], $item->getProductionInstructions());
        $this->assertCount(2, $item->getDownloadLinks());
        $this->assertStringContainsString('http://example.com/v2/documents/DOC1/previewpages/1', $item->getDownloadLinks()[0]->getHref());
        $this->assertStringContainsString('http://example.com/v2/documents/DOC2/previewpages/1', $item->getDownloadLinks()[1]->getHref());
    }
}
