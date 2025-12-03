<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Api\Data;

/**
 * Interface ConfigInterface
 * Provide access to SelfReg Module database configuration.
 */
interface ConfigInterface
{
    /**
     * Get Add Catalog Item message from configuration
     *
     * @return string|null
     */
    public function getAddCatalogItemMessage(): ?string;

    /**
     * Get Move Catalog Item message from configuration
     *
     * @return string|null
     */
    public function getMoveCatalogItemMessage(): ?string;

    /**
     * Get Delete Catalog Item message from configuration
     *
     * @return string|null
     */
    public function getDeleteCatalogItemMessage(): ?string;
}
