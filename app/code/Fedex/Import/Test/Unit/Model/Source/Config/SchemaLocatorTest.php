<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Test\Unit\Model\Source\Config;

use Fedex\Import\Model\Source\Config\SchemaLocator;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class SchemaLocatorTest extends TestCase
{
    protected $Mock;
    /**
     * Set up method
     */
    public function setUp():void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->Mock = $objectManagerHelper->getObject(
            SchemaLocator::class,
            [
            ]
        );
    }

    /**
     * Test method for getSchema
     *
     * @return void
     */
    public function testgetSchema()
    {
        $this->Mock->getSchema();
    }
    
    /**
     * Test method for getPerFileSchema
     *
     * @return void
     */
    public function testgetPerFileSchema()
    {
        $this->Mock->getPerFileSchema();
    }
}
