<?php
/**
 * Copyright © Fedex. All rights reserved.
 * See COPYING.txt for license details.
 * Nitin Pawar <npawar.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Catalog\Plugin\Admin;

use Magento\ConfigurableProduct\Ui\DataProvider\Product\Form\Modifier\Composite;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Class SortConfigurableAssociatedProductPlugin
 * Sorts the configurable-matrix array by price for configurable products.
 */
class SortConfigurableAssociatedProductPlugin
{

    const ENABLE_SORT_CONFIGURABLE_ASSOCIATED_PRODUCT = 'tiger_d240007_default_sku_logic_for_configurable_products';

    /**
     * Constructor
     *
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private readonly ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * After plugin for modifyData method.
     *
     * @param Composite $subject
     * @param array $result
     * @return array
     */
    public function afterModifyData(Composite $subject, array $result): array
    {
        if (empty($result) || !$this->toggleConfig->getToggleConfigValue(self::ENABLE_SORT_CONFIGURABLE_ASSOCIATED_PRODUCT)) {
            return $result;
        }
        $productId = array_key_first($result);
        if (isset($result[$productId]['configurable-matrix'])) {
            usort(
                $result[$productId]['configurable-matrix'],
                function ($a, $b) {
                    return ($a['price'] ?? 0) <=> ($b['price'] ?? 0);
                }
            );
        }
        return $result;
    }
}
