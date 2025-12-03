<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\PageBuilderTemplates\Test\Unit\Setup;

use Fedex\PageBuilderTemplates\Setup\UpgradeData;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\PageBuilder\Model\TemplateFactory;
use PHPUnit\Framework\MockObject\MockObject;

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
     * @var TemplateFactory $templateFactory
     */
    protected $templateFactory;
    protected ModuleDataSetupInterface|MockObject $dataInterface;
    protected ModuleContextInterface|MockObject $contextInterface;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->templateFactory = $this->getMockBuilder(TemplateFactory::class)
            ->setMethods(
                [
                    'create', 'addData', 'save'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->upgradeData = $this->objectManager->getObject(
            UpgradeData::class,
            [
                'templateFactory' => $this->templateFactory
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

        $this->contextInterface->expects($this->any())->method('getVersion')->willReturn('1.0.6');

        $this->templateFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->templateFactory->expects($this->any())->method('addData')->willReturnSelf();
        $this->templateFactory->expects($this->any())->method('save')->willReturnSelf();

        $this->assertNull($this->upgradeData->upgrade($this->dataInterface, $this->contextInterface));
    }
}
