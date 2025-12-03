<?php
/**
 * @category     Fedex
 * @package      Fedex_ProductGraphQl
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Brajmohan Rajput <brajmohan.rajput.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\ProductGraphQl\Model\Resolver\Product;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Model\Product as ProductModel;

class GetProductPresetId implements ResolverInterface
{
    /**
     * @param ProductModel $productModel
     */
    public function __construct(
        protected ProductModel $productModel
    )
    {
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $product = $value['model'];
        $productObj = $this->productModel->load($product->getId());

        return $productObj->getPresetId() ?? '';
    }
}
