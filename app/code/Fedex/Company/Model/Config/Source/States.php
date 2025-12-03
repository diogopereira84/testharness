<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Model\Config\Source;

use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\ResourceModel\Region\Collection;
use Magento\Framework\Data\OptionSourceInterface;

class States implements OptionSourceInterface
{
    /**
     * States constructor
     *
     * @param RegionFactory $regionFactory
     * @return void
     */
    public function __construct(
        protected RegionFactory $regionFactory
    )
    {
    }

    /**
     * Get list of states
     *
     * @return array
     */
    public function toOptionArray()
    {
        $states = $this->getStatesCollection();
        $statesList = [['value' => '', 'label' => __('Please select a region, state or province.')]];
        foreach ($states as $state) {
            $statesList[] = [
                'label' => $state["name"],
                'value' => $state["code"],
            ];
        }
        return $statesList;
    }

    /**
     * Get list of states in United States
     *
     * @return Collection
     */
    private function getStatesCollection()
    {
        return $this->regionFactory->create()
            ->getCollection()
            ->addFieldToFilter('country_id', 'US');
    }
}
