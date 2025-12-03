<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Test\Unit\Plugin;

use Fedex\MarketplaceAdmin\Model\Config;
use Fedex\MarketplaceAdmin\Plugin\AdminOrderGridShippingAddressPlugin;
use Fedex\MarketplaceAdmin\Service\Address\MiraklShippingAddressGridFormatter;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Sales\Ui\Component\Listing\Column\Address as AddressColumn;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceAdmin\Service\Address\RegionNameResolver;

class AdminOrderGridShippingAddressPluginTest extends TestCase
{
    /** @var ResourceConnection&MockObject */
    private ResourceConnection $resource;

    /** @var AdapterInterface&MockObject */
    private AdapterInterface $db;

    /** @var Config&MockObject */
    private Config $config;

    /** @var MiraklShippingAddressGridFormatter&MockObject */
    private MiraklShippingAddressGridFormatter $formatter;

    private AdminOrderGridShippingAddressPlugin $plugin;

    private RegionNameResolver $regionNameResolver;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->resource  = $this->createMock(ResourceConnection::class);
        $this->db        = $this->createMock(AdapterInterface::class);
        $this->config    = $this->createMock(Config::class);
        $this->formatter = $this->createMock(MiraklShippingAddressGridFormatter::class);
        $this->regionNameResolver = $this->createMock(RegionNameResolver::class);

        $this->resource->method('getConnection')->willReturn($this->db);
        $this->resource->method('getTableName')->willReturnCallback(
            fn (string $name) => $name
        );

        $selectStub = new class {
            public function from(...$args) { return $this; }
            public function where(...$args) { return $this; }
        };
        $this->db->method('select')->willReturn($selectStub);

        $this->plugin = new AdminOrderGridShippingAddressPlugin(
            $this->resource,
            $this->config,
            $this->formatter,
            $this->regionNameResolver
        );
    }

    /**
     * @return void
     */
    public function testAfterPrepareDataSourceAppliesMiraklAddressForPickup(): void
    {
        $subject = $this->mockAddressColumn('sales_order_grid', 'shipping_address');

        $this->config->method('isD226848Enabled')->willReturn(true);

        $result = [
            'data' => [
                'items' => [
                    ['entity_id' => 111, 'shipping_address' => 'ORIG111'],
                    ['entity_id' => 222, 'shipping_address' => 'ORIG222'],
                ],
            ],
        ];

        $this->db->method('fetchPairs')->willReturn([
            111 => 'fedexshipping_PICKUP',
            222 => 'flatrate_flatrate',
        ]);

        $miraklJson = json_encode([
            'mirakl_shipping_data' => ['address' => [
                'street'    => ['7900 Legacy Dr'],
                'city'      => 'Plano',
                'regionCode'=> 'TX',
                'postcode'  => '75024',
            ]],
        ]);
        $this->db->method('fetchAll')->willReturn([
            ['order_id' => 111, 'additional_data' => $miraklJson],
            ['order_id' => 222, 'additional_data' => null],
        ]);

        $this->formatter->method('formatInline')
            ->with([
                'street'     => ['7900 Legacy Dr'],
                'city'       => 'Plano',
                'regionCode' => 'TX',
                'postcode'   => '75024',
            ])
            ->willReturn('7900 Legacy Dr,Plano,TX,75024');

        $updated = $this->plugin->afterPrepareDataSource($subject, $result);

        $this->assertSame('7900 Legacy Dr,Plano,TX,75024', $updated['data']['items'][0]['shipping_address']);
        $this->assertSame('ORIG222', $updated['data']['items'][1]['shipping_address']);
    }

    /**
     * Skips when not the Orders grid namespace or not the target column.
     *
     * @return void
     */
    public function testAfterPrepareDataSourceSkipsWhenNotOrdersGridOrColumn(): void
    {
        $subjectWrongNs   = $this->mockAddressColumn('sales_order_invoice_grid', 'shipping_address');
        $subjectWrongCol  = $this->mockAddressColumn('sales_order_grid', 'billing_address');
        $this->config->method('isD226848Enabled')->willReturn(true);

        $result = ['data' => ['items' => [['entity_id' => 1, 'shipping_address' => 'ORIG']]]];

        $this->assertSame($result, $this->plugin->afterPrepareDataSource($subjectWrongNs, $result));
        $this->assertSame($result, $this->plugin->afterPrepareDataSource($subjectWrongCol, $result));
    }

    /**
     * Skips when toggle is disabled or there are no items.
     *
     * @return void
     */
    public function testAfterPrepareDataSourceSkipsWhenToggleOffOrNoItems(): void
    {
        $subject = $this->mockAddressColumn('sales_order_grid', 'shipping_address');

        $this->config->method('isD226848Enabled')->willReturn(false);
        $result = ['data' => ['items' => [['entity_id' => 1, 'shipping_address' => 'ORIG']]]];
        $this->assertSame($result, $this->plugin->afterPrepareDataSource($subject, $result));

        $this->config->method('isD226848Enabled')->willReturn(true);
        $this->assertSame(['data' => ['items' => []]], $this->plugin->afterPrepareDataSource($subject, ['data' => ['items' => []]]));
    }

    /**
     * @param string $namespace
     * @param string $columnName
     * @return AddressColumn
     */
    private function mockAddressColumn(string $namespace, string $columnName): AddressColumn
    {
        /** @var ContextInterface&MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $context->method('getNamespace')->willReturn($namespace);

        /** @var AddressColumn&MockObject $subject */
        $subject = $this->getMockBuilder(AddressColumn::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getContext', 'getName'])
            ->getMock();

        $subject->method('getContext')->willReturn($context);
        $subject->method('getName')->willReturn($columnName);

        return $subject;
    }
}
