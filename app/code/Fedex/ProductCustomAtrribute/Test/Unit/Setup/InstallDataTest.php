<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\ProductCustomAtrribute\Setup\Test\Unit;

use Fedex\ProductCustomAtrribute\Setup\InstallData;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Phrase;
use \Magento\Eav\Setup\EavSetupFactory;

/**
 * Test class for InstallData
 */
class InstallDataTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $installData;
    /**
     * @var EavSetupFactory $eavSetupFactory
     */
    protected $eavSetupFactory;
    private ModuleDataSetupInterface|MockObject $dataInterface;
    private ModuleContextInterface|MockObject $contextInterface;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->eavSetupFactory = $this->getMockBuilder(EavSetupFactory::class)
            ->setMethods(
                [
                    'create', 'addAttribute'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->installData = $this->objectManager->getObject(
            InstallData::class,
            [
                'eavSetupFactory' => $this->eavSetupFactory
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
        $this->contextInterface = $this->getMockBuilder(ModuleContextInterface::class)
                  ->getMockForAbstractClass();

        $this->dataInterface = $this->getMockBuilder(ModuleDataSetupInterface::class)
                  ->getMockForAbstractClass();

        $this->eavSetupFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->eavSetupFactory->expects($this->any())->method('addAttribute')->willReturnSelf();
        $this->assertSame(null, $this->installData->install($this->dataInterface, $this->contextInterface));
    }
}
