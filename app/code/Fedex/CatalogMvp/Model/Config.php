<?php
declare(strict_types=1);
namespace Fedex\CatalogMvp\Model;

use Fedex\CatalogMvp\Api\ConfigInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Config implements ConfigInterface
{
    public const XML_PATH_D206810_TOGGLE = 'tiger_d206810';
    public const XML_PATH_B2371268_TOGGLE = 'etag_b2371268';

    /**
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private readonly ToggleConfig $toggleConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isD206810ToggleEnabled(): bool|int
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::XML_PATH_D206810_TOGGLE);
    }

    /**
     * @inheritDoc
     */
    public function isB2371268ToggleEnabled(): bool|int
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::XML_PATH_B2371268_TOGGLE);
    }
}
