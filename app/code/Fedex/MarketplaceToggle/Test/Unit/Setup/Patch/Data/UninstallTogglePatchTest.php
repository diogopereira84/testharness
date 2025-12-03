<?php

namespace Fedex\MarketplaceToggle\Test\Unit\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceToggle\Setup\Patch\Data\UninstallTogglePatch;

class UninstallTogglePatchTest  extends TestCase
{
    /**
     * @var UninstallPatch
     */
    private $uninstallPatch;

    /**
     * Xpath marketplace checkout flag.
     */
    private const XPATH_ENABLE_MKT_CHECKOUT =
        'environment_toggle_configuration/environment_toggle/enable_marketplace_checkout';

    /**
     * Xpath transactional emails toggle flag
     */
    private const XPATH_TRANSACTIONAL_EMAILS_TOGGLE =
        'environment_toggle_configuration/environment_toggle/enable_transactional_emails';

    /**
     * Xpath strip tags emails toggle flag
     */
    private const STRIP_TAGS_TRANSACTIONAL_EMAILS_TOGGLE = 'strip_tags_transactional_emails';

    /**
     * Xpath enable rounding of navitor prices for mirakl
     */
    private const XPATH_PRICE_ROUNDING_ENABLED =
        'environment_toggle_configuration/environment_toggle/enable_price_rounding';

    /**
     * @var ModuleDataSetupInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $moduleDataSetupMock;

    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->moduleDataSetupMock = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->uninstallPatch = $objectManager->getObject(UninstallTogglePatch::class, [
            'moduleDataSetup' => $this->moduleDataSetupMock,
        ]);
    }

    /**
     * Test apply method.
     *
     * @return void
     */
    public function testApply()
    {
        $configPathsToRemove = [
            self::XPATH_ENABLE_MKT_CHECKOUT,
            self::XPATH_TRANSACTIONAL_EMAILS_TOGGLE,
            self::STRIP_TAGS_TRANSACTIONAL_EMAILS_TOGGLE,
            self::XPATH_PRICE_ROUNDING_ENABLED
        ];

        $connectionMock = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->moduleDataSetupMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($connectionMock);

        foreach ($configPathsToRemove as $configPath) {
            $connectionMock->expects($this->any())
                ->method('delete')
                ->with(
                    $this->moduleDataSetupMock->getTable('core_config_data'),
                    ['path = ?' => $configPath]
                );
        }
    }

    /**
     * Test getDependencies method.
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertEmpty($this->uninstallPatch->getDependencies());
    }

    /**
     * Test getAliases method.
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertEmpty($this->uninstallPatch->getAliases());
    }
}
