<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CategoryLayout\Test\Unit\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Fedex\CategoryLayout\Setup\InstallData;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Test class for InstallData
 */
class InstallDataTest extends TestCase
{
    /**
     * @var EavSetupFactory $eavSetupFactory
     */
    protected $eavSetupFactory;
    /**
     * @var ObjectManager $objectManager
     */
    private $objectManager;

    /**
     * @var InstallData $installData
     */
    private $installData;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->eavSetupFactory = $this->getMockBuilder(EavSetupFactory::class)
            ->setMethods(
                [
                    'create',
                    'addAttribute'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->installData = $this->objectManager->getObject(
            InstallData::class,
            [
                    'eavSetupFactory' => $this->eavSetupFactory,
                ]
        );
    }

    /**
     * Test install function
     *
     * @return void
     */
    public function testInstall()
    {
        $setup = $this->getMockBuilder(ModuleDataSetupInterface::class)
                      ->getMockForAbstractClass();
        $context = $this->getMockBuilder(ModuleContextInterface::class)
                        ->getMockForAbstractClass();
        $this->eavSetupFactory->expects($this->any())
        ->method('create')
        ->willReturnSelf();
        $this->eavSetupFactory->expects($this->any())
        ->method('addAttribute')
        ->willReturnSelf();

        $this->assertEquals(null, $this->installData->install($setup, $context));
    }
}
