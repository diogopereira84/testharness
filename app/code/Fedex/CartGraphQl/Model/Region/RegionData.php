<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Athira Indrakumar <athiraindrakumar.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Region;

use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;

class RegionData
{
    private const COUNTRY_CODE = 'US';

    /**
     * @param RegionCollectionFactory $regionCollectionFactory
     */
    public function __construct(
        private readonly RegionCollectionFactory $regionCollectionFactory
    ) {
    }

    /**
     * @param string $stateCode
     * @return \Magento\Framework\DataObject|null
     */
    public function getRegionByCode(string $stateCode)
    {
        if (!$stateCode) {
            return null;
        }

        $collection = $this->regionCollectionFactory->create()
            ->addRegionCodeFilter($stateCode)
            ->addCountryFilter(self::COUNTRY_CODE);

        return $collection->getFirstItem();
    }

    /**
     * @param $regionId
     * @return null|string
     */
    public function getRegionById($regionId)
    {
        $collection = $this->regionCollectionFactory->create();
        $region = $collection->addFieldToFilter('main_table.region_id', $regionId)
            ->getFirstItem();
        return $region->getId() ? $region->getCode() : null;
    }
}
