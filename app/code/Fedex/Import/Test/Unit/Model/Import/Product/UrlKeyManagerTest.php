<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Test\Unit\Model\Import\Product;

use Fedex\Import\Api\UrlKeyManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\Import\Model\Import\Product\UrlKeyManager;

class UrlKeyManagerTest extends TestCase
{
    protected $Mock;
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->Mock = $objectManagerHelper->getObject(
            UrlKeyManager::class,
            [
                $importUrlKeys['ABC'] = "ABC"
            ]
        );
    }
    
    /**
     * Test method for addUrlKeys method
     *
     * @return void
     */
    public function testaddUrlKeys()
    {
        $this->Mock->addUrlKeys("ABC", "ABC");
    }

    /**
     * Test method for getUrlKeys method
     *
     * @return void
     */
    public function testgetUrlKeys()
    {
        $this->Mock->getUrlKeys();
    }

    /**
     * Test method for isUrlKeyExist method
     *
     * @return void
     */
    public function testIsUrlKeyExist()
    {
        $importUrlKeys['ABC'] = "ABC";
        $this->Mock->isUrlKeyExist('ABC', 'ABC');
    }

    /**
     * Test method for isUrlKeyExistNot method
     *
     * @return void
     */
    public function testIsUrlKeyExistNot()
    {
        $this->Mock->addUrlKeys("ABC", "ABC");
        $this->Mock->isUrlKeyExist('ABCD', 'ABC');
    }
}
