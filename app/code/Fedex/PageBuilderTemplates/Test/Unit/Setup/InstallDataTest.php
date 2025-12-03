<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\PageBuilderTemplates\Setup\Test\Unit;

use Fedex\PageBuilderTemplates\Setup\InstallData;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\PageBuilder\Model\TemplateFactory;
use PHPUnit\Framework\MockObject\MockObject;

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
     * @var TemplateFactory $templateFactory
     */
    protected $templateFactory;
    private ModuleDataSetupInterface|MockObject $dataInterface;
    private ModuleContextInterface|MockObject $contextInterface;

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
        $this->installData = $this->objectManager->getObject(
            InstallData::class,
            [
                'templateFactory' => $this->templateFactory
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

        $this->templateFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->templateFactory->expects($this->any())->method('addData')->willReturnSelf();
        $this->templateFactory->expects($this->any())->method('save')->willReturnSelf();

        $this->assertSame(null, $this->installData->install($this->dataInterface, $this->contextInterface));
    }
}
