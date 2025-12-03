<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Ui\Component\Listing\Column\Status;

use Magento\Framework\Data\OptionSourceInterface;
use Fedex\Shipment\Model\ResourceModel\Shipment\CollectionFactory;

/**
 * Class Options for Listing Column Status
 */
class Options implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * Constructor
     *
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        protected CollectionFactory $collectionFactory
    )
    {
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $collection = $this->collectionFactory->create();
        $statusdata = [];
        foreach ($collection as $shipmentvalue) {
            $statusdata[] = ["label" => $shipmentvalue->getData("label"), "value" => $shipmentvalue->getData("value")];
        }

        return $statusdata;
    }
}
