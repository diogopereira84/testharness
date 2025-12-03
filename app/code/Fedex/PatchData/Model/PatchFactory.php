<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PatchData\Model;

use Magento\Framework\ObjectManagerInterface;

class PatchFactory
{
    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        private ObjectManagerInterface $objectManager
    ) {
    }

    /**
     * Create the class dynamically by CLI.
     *
     * @param string $className
     * @return mixed|string
     */
    public function create(string $className): mixed
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Patch class '$className' not found.");
        }

        return $this->objectManager->create($className);
    }
}
