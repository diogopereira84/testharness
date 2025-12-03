<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Setup\Patch\Data;

use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\PatchInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Cms\Api\BlockRepositoryInterface;

/**
 * @codeCoverageIgnore
 */
class CanvaHeaderCmsBlock implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * canva block identifier
     */
    public const BLOCK_IDENTIFIER = 'canva-page-header';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param BlockRepositoryInterface $blockRepository
     * @param BlockFactory $blockFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private BlockRepositoryInterface $blockRepository,
        private BlockFactory $blockFactory
    )
    {
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $blockData = [
            'title' => 'Canva page header',
            'identifier' => self::BLOCK_IDENTIFIER,
            'content' => '<div data-content-type="row" data-appearance="contained" data-element="main"><div data-enable-parallax="0" data-parallax-speed="0.5" data-background-images="{}" data-background-type="image" data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true" data-video-fallback-src="" data-element="inner" style="justify-content: flex-start; display: flex; flex-direction: column; background-position: left top; background-size: cover; background-repeat: no-repeat; background-attachment: scroll; border-style: none; border-width: 1px; border-radius: 0px; margin: 0px 0px 10px; padding: 10px;"><div data-content-type="text" data-appearance="default" data-element="main" style="border-style: none; border-width: 1px; border-radius: 0px; margin: 0px; padding: 0px;"><p><strong>TAKE $20 OFF YOUR $100 PRINT ORDER</strong> Use code <strong>NOW422</strong>. <a tabindex="0" href="#">See Terms</a></p></div></div></div>',// phpcs:ignore
            'stores' => [0],
            'is_active' => 1,
        ];
        $block = $this->blockFactory
            ->create()
            ->load($blockData['identifier'], 'identifier');

        /**
         * Create the block if it does not exist, otherwise update the content
         */
        if (!$block->getId()) {
            $block->setData($blockData);
        } else {
            $block->setContent($blockData['content']);
        }
        $this->blockRepository->save($block);
    }

    /**
     * @inheritDoc
     */
    public function revert()
    {
        $block = $this->blockFactory
            ->create()
            ->load(self::BLOCK_IDENTIFIER, 'identifier');

        if ($block->getId()) {
            $this->blockRepository->deleteById($block->getId());
        }
    }
}
