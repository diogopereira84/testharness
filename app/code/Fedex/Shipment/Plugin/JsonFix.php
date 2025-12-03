<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Shipment\Plugin;

use Magento\Framework\Serialize\Serializer\Json;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Model\Product;

class JsonFix
{
    /**
     * @param Json $serializer
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        /**
         * Serializer interface instance.
         *
         * @since 102.0.0
         */
        protected Json $serializer,
        protected ToggleConfig $toggleConfig
    )
    {
    }

    /**
     *
     * @param Magento\Catalog\Model\Product\Type\AbstractType $subject
     * @param callable $proceed
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function aroundGetOrderOptions($subject, callable $proceed, Product $product)
    {
        $optionArr = [];
        $info = $product->getCustomOption('info_buyRequest');

        if ($info) {
            if ($info->getValue() !== null) {
                return $proceed($product);
            }
        }
        if ($info) {
            if ($info->getValue() == null){
                $blankData = $this->serializer->serialize('');
                $optionArr['info_buyRequest'] = $this->serializer->unserialize($blankData);
            }
        }

        $optionIds = $product->getCustomOption('option_ids');

        //@codeCoverageIgnoreStart
        if ($optionIds) {
            foreach (explode(',', $optionIds->getValue() ?? '') as $optionId) {
                $option = $product->getOptionById($optionId);
                if ($option) {
                    $confItemOption = $product->getCustomOption(self::OPTION_PREFIX . $option->getId());

                    $group = $option->groupFactory($option->getType())
                        ->setOption($option)
                        ->setProduct($product)
                        ->setConfigurationItemOption($confItemOption);

                    $optionArr['options'][] = [
                        'label' => $option->getTitle(),
                        'value' => $group->getFormattedOptionValue($confItemOption->getValue()),
                        'print_value' => $group->getPrintableOptionValue($confItemOption->getValue()),
                        'option_id' => $option->getId(),
                        'option_type' => $option->getType(),
                        'option_value' => $confItemOption->getValue(),
                        'custom_view' => $group->isCustomizedView(),
                    ];
                }
            }
        }
        //@codeCoverageIgnoreEnd
        
        $productTypeConfig = $product->getCustomOption('product_type');
        if ($productTypeConfig) {
            $optionArr['super_product_config'] = [
                'product_code' => $productTypeConfig->getCode(),
                'product_type' => $productTypeConfig->getValue(),
                'product_id' => $productTypeConfig->getProductId(),
            ];
        }

        return $optionArr;
    }
}
