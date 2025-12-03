<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\Quote\Product;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ContentAssociationsResolver
{
    protected const CONTENT_REFERENCE = 'contentReference';
    protected const INSTORE_NFW_TOGGLE_ENABLED_CONFIG = 'instore_nfw';

    /**
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private ToggleConfig $toggleConfig
    ) {
    }

    /**
     * @param array|null $productContentAssociations
     * @return string|null
     */
    public function getContentReference(?array $productContentAssociations): ?string
    {
        if (!is_array($productContentAssociations)) {
            return null;
        }
        return $this->toggleConfig->getToggleConfigValue(self::INSTORE_NFW_TOGGLE_ENABLED_CONFIG) ?
            $productContentAssociations[0][self::CONTENT_REFERENCE] ?? null :
            $productContentAssociations[0][self::CONTENT_REFERENCE];
    }
}
