<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PatchData\Model;

use Fedex\PatchData\Model\ResourceModel\Patch as PatchResource;
use Magento\Framework\Filesystem\Driver\File;

class PatchValidator
{
    /**
     * @param PatchFactory $patchFactory
     * @param PatchResource $patchResource
     * @param File $fileDriver
     */
    public function __construct(
        private PatchFactory $patchFactory,
        private PatchResource $patchResource,
        private File $fileDriver,
    ) {
    }

    /**
     * @param string $patchPath
     * @return bool
     */
    public function IsPatchExistsAndExecuted(string $patchPath)
    {
        $patchEntry = $this->patchFactory->create(Patch::class);
        $this->patchResource->load($patchEntry, $patchPath, 'patch_name');

        if ($patchEntry->getId() && (int)$patchEntry->getData('patch_status') == 1) {
            return true;
        }

        return false;
    }

    /**
     * @param $patchPath
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function isPatchPathExists($patchPath)
    {
        if (!$this->fileDriver->isExists($patchPath)) {
            return false;
        }
        return true;
    }

    /**
     * @param string $path
     * @return string
     */
    public function convertPathToClass(string $path): string
    {
        return str_replace(['app/code/', '.php', '/'], ['', '', '\\'], $path);
    }

    /**
     * @param string $path
     * @return string
     */
    public function getPatchNameSpace(string $path): string
    {
        return str_replace(
            '\\',
            '/',
            str_replace(
                'app/code/',
                '',
                str_replace('.php', '', $path)
            )
        );
    }
}
