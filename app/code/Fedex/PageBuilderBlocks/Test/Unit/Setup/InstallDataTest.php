<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\PageBuilderBlocks\Test\Unit\Setup;

use Fedex\PageBuilderBlocks\Setup\InstallData;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Phrase;
use Magento\Cms\Model\Block;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\Page;
use Magento\PageBuilder\Model\Template;
use Magento\PageBuilder\Model\TemplateFactory;

/**
 * Test class for InstallData
 */
class InstallDataTest extends TestCase
{
    protected $block;
    protected $page;
    protected $template;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $installData;
    /**
     * @var BlockFactory $blockFactory
     */
    protected $blockFactory;

    /**
     * @var PageFactory $pageFactory
     */
    protected $pageFactory;

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
        $this->blockFactory = $this->getMockBuilder(BlockFactory::class)
            ->setMethods(['create', 'addAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->block = $this->getMockBuilder(Block::class)
            ->setMethods(['load', 'getId', 'delete', 'addData', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageFactory = $this->getMockBuilder(PageFactory::class)
            ->setMethods(['create', 'addAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->page = $this->getMockBuilder(Page::class)
            ->setMethods(['load', 'getId', 'delete', 'addData', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->templateFactory = $this->getMockBuilder(TemplateFactory::class)
            ->setMethods(['create', 'addAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->template = $this->getMockBuilder(Template::class)
            ->setMethods(['load', 'getId', 'delete', 'addData', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->installData = $this->objectManager->getObject(
            InstallData::class,
            [
                'blockFactory' => $this->blockFactory,
                'templateFactory' => $this->templateFactory,
                'pageFactory' => $this->pageFactory
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

        $this->blockFactory->expects($this->any())->method('create')->willReturn($this->block);
        $this->block->expects($this->any())->method('load')->willReturnSelf();
        $this->block->expects($this->any())->method('getId')->willReturn(2);
        $this->block->expects($this->any())->method('delete')->willReturnSelf();
        $this->block->expects($this->any())->method('addData')->willReturnSelf();
        $this->block->expects($this->any())->method('save')->willReturnSelf();
        $this->pageFactory->expects($this->any())->method('create')->willReturn($this->page);
        $this->page->expects($this->any())->method('load')->willReturnSelf();
        $this->page->expects($this->any())->method('getId')->willReturn(2);
        $this->page->expects($this->any())->method('delete')->willReturnSelf();
        $this->page->expects($this->any())->method('addData')->willReturnSelf();
        $this->page->expects($this->any())->method('save')->willReturnSelf();
        $this->templateFactory->expects($this->any())->method('create')->willReturn($this->template);
        $this->template->expects($this->any())->method('addData')->willReturnSelf();
        $this->template->expects($this->any())->method('save')->willReturnSelf();

        $this->assertSame(null, $this->installData->install($this->dataInterface, $this->contextInterface));
    }
}
