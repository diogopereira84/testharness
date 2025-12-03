<?php
declare(strict_types=1);

namespace Fedex\InBranch\Model\Config;

use Fedex\EnvironmentManager\Model\Config\ToggleBase;
use Fedex\EnvironmentManager\Model\Config\ToggleInterface;

class E366082DocumentLevelRouting extends ToggleBase implements ToggleInterface
{
    /**
     * Toggle system configuration path
     */
    private const PATH = 'tigers_document_level_touting';

    /**
     * @inheritDoc
     */
    protected function getPath(): string
    {
        return self::PATH;
    }
}
