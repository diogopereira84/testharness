<?php
/**
 * @category    Fedex
 * @package     Fedex_EnvironmentManager
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Model\Config;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

abstract class ToggleBase implements ToggleInterface
{
    /**
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * Return the XML path for toggle status retrieval
     *
     * @return string
     */
    abstract protected function getPath(): string;

    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue($this->getPath());
    }
}
