<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceWebhook\Test\Unit\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Fedex\MarketplaceWebhook\Setup\Patch\Data\UpgradeData;

class UpgradeDataTest extends TestCase
{
    protected $upgradeData;
    private const MIRAKL_ITEMS_INVOICING_CONFIG= 'mirakl_connector/order_workflow/lock_mirakl_items_invoicing';

    private const MIRAKL_ITEMS_SHIPPING_CONFIG= 'mirakl_connector/order_workflow/lock_mirakl_items_shipping';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetupMock;

    /**
     * @var WriterInterface
     */
    private $configWriterMock;

    /**
     * @var AdapterInterface
     */
    private $adapterMock;

    /**
     * Setup.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->moduleDataSetupMock = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->setMethods(['getConnection'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->configWriterMock = $this->getMockBuilder(WriterInterface::class)
            ->setMethods(['save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->adapterMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['startSetup','endSetup'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);

        $this->upgradeData = $objectManagerHelper->getObject(
            UpgradeData::class,
            [
                'moduleDataSetup' => $this->moduleDataSetupMock,
                'configWriter' => $this->configWriterMock
            ]
        );
    }

    /**
     * Test apply method.
     *
     * @return void
     */
    public function testApply()
    {
        $this->adapterMock->expects($this->any())->method('startSetup')->willReturnSelf();
        $this->moduleDataSetupMock->expects($this->any())->method('getConnection')->willReturn($this->adapterMock);
        $this->configWriterMock->expects($this->exactly(2))->method('save')->withConsecutive(
            [self::MIRAKL_ITEMS_INVOICING_CONFIG, '0'],
            [self::MIRAKL_ITEMS_SHIPPING_CONFIG, '0']
        );
        $this->adapterMock->expects($this->any())->method('endSetup')->willReturnSelf();
        $this->assertEquals(null, $this->upgradeData->apply());
    }

    /**
     * Test apply method.
     *
     * @return void
     */
    public function testRevert()
    {
        $this->adapterMock->expects($this->any())->method('startSetup')->willReturnSelf();
        $this->moduleDataSetupMock->expects($this->any())->method('getConnection')->willReturn($this->adapterMock);
        $this->configWriterMock->expects($this->exactly(2))->method('save')->withConsecutive(
            [self::MIRAKL_ITEMS_INVOICING_CONFIG, '1'],
            [self::MIRAKL_ITEMS_SHIPPING_CONFIG, '1']
        );
        $this->adapterMock->expects($this->any())->method('endSetup')->willReturnSelf();
        $this->assertEquals(null, $this->upgradeData->revert());
    }

    /**
     * Test getDependencies method.
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertEquals([], $this->upgradeData->getDependencies());
    }

    /**
     * Test getAliases method.
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertEquals([], $this->upgradeData->getAliases());
    }
}
