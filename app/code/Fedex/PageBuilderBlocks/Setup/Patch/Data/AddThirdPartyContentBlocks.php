<?php
declare(strict_types=1);

namespace Fedex\PageBuilderBlocks\Setup\Patch\Data;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Cms\Model\BlockFactory;

/**
 * Class AddThirdPartyContentBlocks
 * Fedex\PageBuilderBlocks\Setup\Patch\Data
 */
class AddThirdPartyContentBlocks implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * AddThirdPartyContentBlocks constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param SerializerInterface $serializer
     * @param File $file
     * @param Reader $reader
     * @param BlockFactory $blockFactory
     * @param BlockRepositoryInterface $blockRepository
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private SerializerInterface $serializer,
        private File $file,
        private Reader $reader,
        private BlockFactory $blockFactory,
        private BlockRepositoryInterface $blockRepository
    )
    {
    }
    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $blocksListAsJSON = "";
        $file = $this->reader->getModuleDir(false, 'Fedex_PageBuilderBlocks') .
            DIRECTORY_SEPARATOR . 'Setup' .
            DIRECTORY_SEPARATOR . 'data' .
            DIRECTORY_SEPARATOR . 'thirdPartyBlocks.json';
        $blocksListAsJSON = $this->file->read($file);
        $blocks = $this->serializer->unserialize(trim($blocksListAsJSON));
        foreach ($blocks as $block) {
            $blockModel = $this->blockFactory->create([ 'data' => $block]);
            $this->blockRepository->save($blockModel);
        }
        $this->moduleDataSetup->endSetup();
    }
    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }
    /**
     * Revert method
     */
    public function revert()
    {
        $this->moduleDataSetup->startSetup();
        $blocksListAsJSON = "";
        $file = $this->reader->getModuleDir(false, 'Fedex_PageBuilderBlocks') .
            DIRECTORY_SEPARATOR . 'Setup' .
            DIRECTORY_SEPARATOR . 'data' .
            DIRECTORY_SEPARATOR . 'thirdPartyBlocks.json';
        $blocksListAsJSON = $this->file->read($file);
        $blocks = $this->serializer->unserialize(trim($blocksListAsJSON));
        foreach ($blocks as $block) {
            $blockModel = $this->blockFactory->create()->load($block['identifier']);
            $this->blockRepository->delete($blockModel);
        }
        $this->moduleDataSetup->endSetup();
    }
    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
