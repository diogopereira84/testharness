<?php
/**
 * @category Fedex
 * @package  Fedex_DisableCacheButton
 * @copyright   Copyright (c) 2024 FedEx
 */
namespace Fedex\DisableCacheButton\Block;

use Magento\Backend\Block\Cache as MagentoCache;

/**
 * Overridden OOTB class Cache to hide flush magento cache button
 */
class Cache extends MagentoCache
{
    /**
     * Remove magento cache buttons to have more control about it
     * Cache managements would require requests to be made from the command line
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->buttonList->remove('flush_magento');
        $this->buttonList->remove('flush_system');
    }
}
