<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\ProductCustomAtrribute\Test\Unit\Setup;

use Fedex\ProductCustomAtrribute\Setup\UpgradeData;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Phrase;
use \Magento\Eav\Setup\EavSetupFactory;

/**
 * Test class for UpgradeData
 */
class UpgradeDataTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $upgradeData;
    /**
     * @var EavSetupFactory $eavSetupFactoryMock
     */
    protected $eavSetupFactoryMock;
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $dataInterfaceMock;
    /**
     * @var ModuleContextInterface
     */
    private ModuleContextInterface $contextInterfaceMock;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->eavSetupFactoryMock = $this->getMockBuilder(EavSetupFactory::class)
            ->setMethods(
                [
                    'create', 'addAttribute', 'updateAttribute'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->upgradeData = $this->objectManager->getObject(
            UpgradeData::class,
            [
                'eavSetupFactory' => $this->eavSetupFactoryMock
            ]
        );
    }

    /**
     * Test upgrade function
     *
     * @return void
     */
    public function testUpgrade()
    {
        $this->contextInterfaceMock = $this->getMockBuilder(ModuleContextInterface::class)
                  ->getMockForAbstractClass();

        $this->dataInterfaceMock = $this->getMockBuilder(ModuleDataSetupInterface::class)
                  ->getMockForAbstractClass();

        $this->eavSetupFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())->method('addAttribute')->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())->method('updateAttribute')->willReturnSelf();

        $this->assertSame(null, $this->upgradeData->upgrade($this->dataInterfaceMock, $this->contextInterfaceMock));
    }
}
