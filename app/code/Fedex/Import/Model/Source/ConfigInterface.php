<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Model\Source;

interface ConfigInterface
{
    /**
     * Get configuration of source type by name
     *
     * @param string $name
     * @return array
     */
    public function getType($name);
}
