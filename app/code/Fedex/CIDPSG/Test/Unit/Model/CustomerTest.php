<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CIDPSG\Test\Unit\Model;

use Fedex\CIDPSG\Model\Customer as Index;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;

/**
 * Model Customer test class
 */
class CustomerTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    /**
     * @var object
     */
    protected $PostFactory;
    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->PostFactory = $this->objectManagerHelper->getObject(Index::class);
    }

    /**
     * test constuct method
     *
     * @return void
     */
    public function testConstruct()
    {
        $this->assertTrue(true);
    }
}
