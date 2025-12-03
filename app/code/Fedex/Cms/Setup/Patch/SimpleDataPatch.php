<?php

declare(strict_types=1);

namespace Fedex\Cms\Setup\Patch;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Fedex\Cms\Api\Cms\SimpleBlock;
use Fedex\Cms\Api\Cms\SimplePage;
use Fedex\Cms\Api\Cms\SimpleContentReader;
use Magento\Framework\App\Config\Storage\WriterInterface as SimpleConfig;

abstract class SimpleDataPatch implements DataPatchInterface
{
    /** @var SimpleBlock */
    protected $block;

    /** @var SimpleConfig */
    protected $config;

    /** @var SimplePage */
    protected $page;

    /**
     * SimpleDataPatch constructor.
     *
     * @param SimpleBlock $simpleBlock
     * @param SimpleConfig $simpleConfig
     * @param SimplePage $simplePage
     * @param SimpleContentReader $contentReader
     */
    public function __construct(
        SimpleBlock $simpleBlock,
        SimpleConfig $simpleConfig,
        SimplePage $simplePage,
        protected SimpleContentReader $contentReader
    ) {
        $this->block = $simpleBlock;
        $this->config = $simpleConfig;
        $this->page = $simplePage;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Call your patch updates within this function.
     */
    abstract public function apply(): void;
}
