<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Shipment\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\FedexAccountPay\Model\FedexAccount;

/**
 * Test model class for FedexAccount
 */
class FedexAccountTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var object
     */
    protected $fedexAccount;
    /**
     * @var ObjectManager|MockObject
    */
    protected $objectManagerHelper;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->fedexAccount = $this->objectManagerHelper->getObject(
            FedexAccount::class
        );
    }
    
    /**
     * Test testConstruct
     */
    public function testConstruct()
    {
        $this->assertTrue(true);
    }
}
