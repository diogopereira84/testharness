<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Rutvee Sojitra <rsojitra@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\LiveSearchFacets\Model\Cache;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;
/**
 * @codeCoverageIgnore
 */
class Type extends TagScope
{
    /**
     * Type Code for Cache. It should be unique
     */
    public const TYPE_IDENTIFIER = 'livesearchfacetscache';
    /**
     * Tag of Cache
     */
    public const CACHE_TAG = 'LIVESEARCHFACETSCACHE';

    /**
     * @param FrontendPool $cacheFrontendPool
     */
    public function __construct(
        FrontendPool $cacheFrontendPool
    ) {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}
