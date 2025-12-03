<?php
declare(strict_types=1);

namespace Fedex\LiveSearch\Model;

use Fedex\LiveSearch\Api\Data\ChildProductOfferInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\LocalizedException;
use Fedex\MarketplaceProduct\Helper\Data;

class ChildProductOffer implements ChildProductOfferInterface
{
    /**
     * Constructor
     *
     * @param ProductRepository $productRepository
     * @param Data $marketPlaceProductHelper
     * @param Configurable $configurable
     */
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly Data $marketPlaceProductHelper,
        private readonly Configurable $configurable
    ) {}

    /**
     * Get the Child Product Offer ID
     *
     * @param Product $parentProduct
     * @param array $selectedOptions
     * @return int|string
     * @throws LocalizedException
     */
    public function getChildProductOfferId($parentProduct,$selectedOptions)
    {
        if ($parentProduct->getTypeId() !== Configurable::TYPE_CODE) {
            throw new LocalizedException(__('The provided product is not a configurable product.'));
        }

        $childProduct = $this->configurable->getProductByAttributes($selectedOptions, $parentProduct);
        $bestOffer = $this->marketPlaceProductHelper->getBestOffer($childProduct);

        return $bestOffer->getId();
    }
}
