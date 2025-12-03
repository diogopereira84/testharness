<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomizedMegamenu\Plugin\Block\Cart;

use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class Plugin SerializeConfig
 */
class SerializeConfig
{
    /**
     * Initializing Constructor
     *
     * @param Json $jsonSerializer
     */
    public function __construct(
        protected Json $jsonSerializer
    ) {
    }

    /**
     * Get serialized config using after plugin
     *
     * @param Object $subject
     * @param Object $result
     * @return bool|string
     */
    public function afterGetSerializedConfig($subject, $result)
    {
        return $this->jsonSerializer->serialize($subject->getConfig());
    }
}
