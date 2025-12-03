<?php
declare(strict_types=1);

namespace Fedex\PageBuilderBlocks\Test\Unit\Setup\Patch\Data;

use PHPUnit\Framework\TestCase;
use Magento\Store\Model\Store;
use Magento\Cms\Model\Block;
use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Fedex\PageBuilderBlocks\Setup\Patch\Data\MarketingContentVideoTabbedBlock;

class MarketingContentVideoTabbedBlockTest extends TestCase
{
    private $moduleDataSetup;
    private $blockFactory;
    private $block;

    protected function setUp(): void
    {
        $this->moduleDataSetup = $this->createMock(ModuleDataSetupInterface::class);
        $this->blockFactory = $this->createMock(BlockFactory::class);
        $this->block = $this->getMockBuilder(Block::class)
            ->disableOriginalConstructor()
            ->addMethods(['setStores'])
            ->onlyMethods(
                [
                    'setTitle',
                    'setIdentifier',
                    'setIsActive',
                    'setContent',
                    'save',
                    'load',
                    'getId',
                    'delete'
                ]
            )->getMock();

        $this->blockFactory->method('create')->willReturn($this->block);
    }

    public function testApplyCreatesBlock(): void
    {
        $this->moduleDataSetup->expects($this->once())->method('startSetup');
        $this->moduleDataSetup->expects($this->once())->method('endSetup');
        $this->block->expects($this->once())->method('setTitle')
            ->with('Marketing Content Video Tabbed Block')
            ->willReturnSelf();
        $this->block->expects($this->once())->method('setIdentifier')->willReturnSelf();
        $this->block->expects($this->once())->method('setIsActive')->with(true)->willReturnSelf();
        $this->block->expects($this->once())->method('setContent')->willReturnSelf();
        $this->block->expects($this->once())->method('setStores')
            ->with([Store::DEFAULT_STORE_ID])
            ->willReturnSelf();
        $this->block->expects($this->once())->method('save');
        $this->block->method('load')->willReturn($this->block);
        $this->block->method('getId')->willReturn(1);
        $patch = new MarketingContentVideoTabbedBlock(
            $this->moduleDataSetup,
            $this->blockFactory
        );
        $patch->apply();
    }

    public function testRevertDeletesBlock(): void
    {
        $this->block->method('load')->willReturn($this->block);
        $this->block->method('getId')->willReturn(1);
        $this->block->expects($this->once())->method('delete');

        $patch = new MarketingContentVideoTabbedBlock(
            $this->moduleDataSetup,
            $this->blockFactory
        );
        $patch->revert();
    }

    public function testRevertDoesNotDeleteBlockWhenNotFound(): void
    {
        $this->block->method('load')->willReturn($this->block);
        $this->block->method('getId')->willReturn(null);
        $this->block->expects($this->never())->method('delete');

        $patch = new MarketingContentVideoTabbedBlock(
            $this->moduleDataSetup,
            $this->blockFactory
        );
        $patch->revert();
    }

    public function testGetDependenciesReturnsEmptyArray(): void
    {
        $patch = new MarketingContentVideoTabbedBlock($this->moduleDataSetup, $this->blockFactory);
        $this->assertEquals([], $patch->getDependencies());
    }

    public function testGetAliasesReturnsEmptyArray(): void
    {
        $patch = new MarketingContentVideoTabbedBlock($this->moduleDataSetup, $this->blockFactory);
        $this->assertEquals([], $patch->getAliases());
    }
}
