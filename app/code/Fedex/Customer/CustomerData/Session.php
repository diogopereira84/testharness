<?php
/**
 * @category    Fedex
 * @package     Fedex_Customer
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;

class Session implements SectionSourceInterface
{

    /**
     * Cookie name
     */
    private const COOKIE_NAME = 'PHPSESSID';


    /**
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        private CookieManagerInterface $cookieManager
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getSectionData(): array
    {
        $data = [];

        $data['CUSTOMERSESSIONID'] = $this->cookieManager->getCookie(self::COOKIE_NAME) ?? '';

        return $data;
    }
}
