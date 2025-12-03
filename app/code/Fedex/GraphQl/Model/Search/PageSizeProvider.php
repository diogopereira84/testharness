<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Model\Search;

class PageSizeProvider extends \Magento\Search\Model\Search\PageSizeProvider
{
    const MAX_PAGE_SIZE_ALS = 500;

    /**
     * Returns max_page_size for Adobe Live Search engine
     *
     * @return integer
     * @since 101.0.0
     */
    public function getMaxPageSize() : int
    {
        return self::MAX_PAGE_SIZE_ALS;
    }
}
