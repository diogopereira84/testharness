<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\SSO\Test\Unit\Setup;

use Fedex\SSO\Setup\UpgradeData;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Phrase;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * Test class for UpgradeData class
 */
class UpgradeDataTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $upgradeData;
    /**
     * @var EavSetupFactory $eavSetupFactory
     */
    protected $eavSetupFactory;
    protected ModuleDataSetupInterface|MockObject $dataInterface;
    protected ModuleContextInterface|MockObject $contextInterface;

    /**
     * Main set up method
     */
    public function setUp(): void
    {
        $this->eavSetupFactory = $this->getMockBuilder(EavSetupFactory::class)
            ->setMethods(['create', 'removeAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->upgradeData = $this->objectManager->getObject(
            UpgradeData::class,
            [
                'eavSetupFactory' => $this->eavSetupFactory
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
        $this->contextInterface = $this->getMockBuilder(ModuleContextInterface::class)
                  ->getMockForAbstractClass();

        $this->dataInterface = $this->getMockBuilder(ModuleDataSetupInterface::class)
                  ->getMockForAbstractClass();

        $this->eavSetupFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->eavSetupFactory->expects($this->any())->method('removeAttribute')->willReturnSelf();
        $this->assertEquals(null, $this->upgradeData->upgrade($this->dataInterface, $this->contextInterface));
    }
}
