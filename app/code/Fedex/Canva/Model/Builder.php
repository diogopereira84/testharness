<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Model;

use Exception;
use Fedex\Canva\Api\Data\SizeInterfaceFactory;
use Fedex\Canva\Api\Data\SizeCollectionInterface;
use Fedex\Canva\Api\Data\SizeCollectionInterfaceFactory;

/**
 * Class Builder
 * Size collection builder
 */
class Builder
{
    /**
     * @param SizeInterfaceFactory $sizeFactory
     * @param SizeCollectionInterfaceFactory $sizeCollectionFactory
     */
    public function __construct(
        private SizeInterfaceFactory $sizeFactory,
        private SizeCollectionInterfaceFactory $sizeCollectionFactory
    )
    {
    }

    /**
     * Build the size collection
     *
     * @param array $data
     * @return SizeCollectionInterface
     * @throws Exception
     */
    public function build(array $data): SizeCollectionInterface
    {
        $collection = $this->sizeCollectionFactory->create();
        foreach ($data as $item) {
            $collection->addItem(
                $this->sizeFactory->create(['data' => [
                    Size::RECORD_ID => $item[Size::RECORD_ID] ?? '',
                    Size::DEFAULT => $item[Size::DEFAULT] ?? Size::DEFAULT_VALUE_FALSE,
                    Size::PRODUCT_MAPPING_ID => $item[Size::PRODUCT_MAPPING_ID] ?? '',
                    Size::DISPLAY_WIDTH => $item[Size::DISPLAY_WIDTH] ?? '',
                    Size::DISPLAY_HEIGHT => $item[Size::DISPLAY_HEIGHT] ?? '',
                    Size::ORIENTATION => $item[Size::ORIENTATION] ?? '',
                    Size::POSITION => $item[Size::POSITION] ?? '',
                    Size::INITIALIZE => $item[Size::INITIALIZE] ?? '',
                ]])
            );
        }
        $collection->sort();
        return $collection;
    }
}
