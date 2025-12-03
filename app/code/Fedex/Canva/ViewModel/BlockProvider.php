<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\ViewModel;

use Magento\Cms\Block\Block;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\LayoutInterface;

class BlockProvider implements ArgumentInterface
{
    public const CMS_BLOCK_ID_CANVA_PDP = 'canva-page-header';
    public const CMS_BLOCK_ID_CANVA_HOME = 'header_promo_block';

    /**
     * @param LayoutInterface $layout
     */
    public function __construct(
        private LayoutInterface $layout
    )
    {
    }

    /**
     * Return the CMS static block to show
     *
     * @return Block
     */
    public function getPromotionBlockPdp(): Block
    {
        return $this->layout->createBlock(Block::class)->setBlockId(self::CMS_BLOCK_ID_CANVA_PDP);
    }

    /**
     * Return the CMS static block to show
     *
     * @return Block
     */
    public function getPromotionBlockHome(): Block
    {
        return $this->layout->createBlock(Block::class)->setBlockId(self::CMS_BLOCK_ID_CANVA_HOME);
    }
}
