<?php

declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\ViewModel;

use Fedex\Canva\ViewModel\BlockProvider;
use Magento\Cms\Block\Block;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class BlockProviderTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    public const CMS_BLOCK_ID_CANVA_PDP = 'canva-page-header';
    public const CMS_BLOCK_ID_CANVA_HOME = 'header_promo_block';

    private BlockProvider $blockProviderMock;
    private LayoutInterface|MockObject $layoutMock;
    private Block|MockObject $blockMock;

    protected function setUp(): void
    {
        $this->layoutMock = $this->getMockBuilder(LayoutInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->blockMock = $this->getMockBuilder(Block::class)
            ->addMethods(['setBlockId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->blockProviderMock = $this->objectManager->getObject(
            BlockProvider::class,
            [
                'layout' => $this->layoutMock
            ]
        );
    }

    public function testGetPromotionBlockPdp()
    {
        $this->blockMock->expects($this->once())->method('setBlockId')
            ->with(self::CMS_BLOCK_ID_CANVA_PDP)->willReturnSelf();
        $this->layoutMock->expects($this->once())->method('createBlock')
            ->with(Block::class)->willReturn($this->blockMock);

        $this->assertInstanceOf(Block::class, $this->blockProviderMock->getPromotionBlockPdp());
    }

    public function testGetPromotionBlockHome()
    {
        $this->blockMock->expects($this->once())->method('setBlockId')
            ->with(self::CMS_BLOCK_ID_CANVA_HOME)->willReturnSelf();
        $this->layoutMock->expects($this->once())->method('createBlock')
            ->with(Block::class)->willReturn($this->blockMock);

        $this->assertInstanceOf(Block::class, $this->blockProviderMock->getPromotionBlockHome());
    }
}
