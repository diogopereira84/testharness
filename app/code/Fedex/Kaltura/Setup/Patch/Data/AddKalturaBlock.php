<?php
declare(strict_types=1);

namespace Fedex\Kaltura\Setup\Patch\Data;

use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * Class AddKalturaBlock
 * @package Fedex\Kaltura\Setup\Patch\Data
 */
class AddKalturaBlock implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param BlockFactory $blockFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private BlockFactory $blockFactory
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $this->deleteBlockByIdentifier('kaltura-video-player');
        $heroBanner = $this->blockFactory->create();
        $heroBanner->addData($this->getBlockInfo())->save();

        $this->moduleDataSetup->endSetup();

    }

    private function deleteBlockByIdentifier($blockIdentifier) {
        $block = $this->blockFactory->create()->load($blockIdentifier);
        if($block && $block->getId()) {
            $block->delete();
        }
    }

    private function getBlockInfo(){
        return [
            "title" => "Kaltura Video Player",
            "identifier" => "kaltura-video-player",
            "content" => '<div data-content-type="row" data-appearance="contained" data-element="main"><div data-enable-parallax="0" data-parallax-speed="0.5" data-background-images="{}" data-background-type="image" data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true" data-video-fallback-src="" data-element="inner" style="justify-content: flex-start; display: flex; flex-direction: column; background-position: left top; background-size: cover; background-repeat: no-repeat; background-attachment: scroll; border-style: none; border-width: 1px; border-radius: 0px; margin: 0px 0px 10px; padding: 10px;"><div data-content-type="html" data-appearance="default" data-element="main" style="border-style: none; border-width: 1px; border-radius: 0px; margin: 0px; padding: 0px;"><div class="bg-cl-black kaltura-player-modal">
    <div class="modal-body-content">
        <div id="cms-kvideo-player" class="player-frame"></div>
    </div>
</div>
<script type="text/x-magento-init">
    {
        ".kaltura-player-modal": {
            "Magento_PageBuilder/js/components/kaltura-player-modal": {}
        }
    }
</script></div></div></div>',
            "is_active" => 1,
            "stores" => [0]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function revert()
    {
        $this->moduleDataSetup->startSetup();
        $this->deleteBlockByIdentifier('kaltura-video-player');
        $this->moduleDataSetup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
