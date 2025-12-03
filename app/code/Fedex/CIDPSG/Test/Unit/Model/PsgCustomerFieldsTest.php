<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CIDPSG\Test\Unit\Model;

use Fedex\CIDPSG\Model\PsgCustomerFields;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;

/**
 * Test classs for PsgCustomerFields
 */
class PsgCustomerFieldsTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    /**
     * @var object
     */
    protected $psgCustomerFieldsData;
    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->psgCustomerFieldsData = $this->objectManagerHelper->getObject(PsgCustomerFields::class);
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
