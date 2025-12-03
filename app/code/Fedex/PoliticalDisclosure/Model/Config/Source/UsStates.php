<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PoliticalDisclosure\Model\Config\Source;

use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class UsStates implements OptionSourceInterface
{
    /**
     * @param CollectionFactory $regionCollectionFactory
     */
    public function __construct(
        private CollectionFactory $regionCollectionFactory
    ) {}

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $collection = $this->regionCollectionFactory->create()
            ->addCountryFilter('US')->load();

        $out = [];
        foreach ($collection as $region) {
            $out[] = ['value' => $region->getCode(), 'label' => $region->getName()];
        }
        return $out;
    }
}
