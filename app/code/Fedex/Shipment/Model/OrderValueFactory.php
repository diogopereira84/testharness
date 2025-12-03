<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Model;

class OrderValueFactory
{
    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        protected \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
    }

    /**
     * Create new country model
     *
     * @param array $arguments
     * @return \Magento\Directory\Model\Country
     */
    public function create(array $arguments = [])
    {
        return $this->objectManager->create(\Fedex\Shipment\Model\OrderValue::class, $arguments, false);
    }
}
