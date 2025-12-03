<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SSO\Model\Session;

use Magento\Customer\Model\Config\Share;
use Magento\Store\Model\StoreManagerInterface;

class Storage extends \Magento\Framework\Session\Storage
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param string $namespace
     * @param array $data
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        $namespace = 'fedex_sso',
        array $data = []
    ) {
        parent::__construct($namespace, $data);
    }
}
