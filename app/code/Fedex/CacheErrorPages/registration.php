<?php
/**
 * @category Fedex
 * @package  Fedex_CacheErrorPages
 * @author   Iago Lima <iago.lima.osv@fedex.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License
 * @copyright Copyright (c) 2025 Fedex.
 **/
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Fedex_CacheErrorPages',
    __DIR__
);
