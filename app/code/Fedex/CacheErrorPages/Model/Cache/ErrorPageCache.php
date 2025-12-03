<?php
/**
 * @category Fedex
 * @package  Fedex_CacheErrorPages
 * @author   Iago Lima <iago.lima.osv@fedex.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License
 * @copyright Copyright (c) 2025 Fedex.
 **/
declare(strict_types=1);
namespace Fedex\CacheErrorPages\Model\Cache;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

class ErrorPageCache extends TagScope
{
    const TYPE_IDENTIFIER = 'error_page_cache';
    const CACHE_TAG = 'ERROR_PAGE_CACHE';

    public function __construct(FrontendPool $cacheFrontendPool)
    {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}
