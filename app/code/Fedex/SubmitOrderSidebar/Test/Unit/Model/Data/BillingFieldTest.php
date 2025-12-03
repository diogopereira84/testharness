<?php
/**
 * @category    Fedex
 * @package     Fedex_SubmitOrderSidebar
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model\Data;

use Exception;
use Fedex\SubmitOrderSidebar\Model\Data\BillingField;
use PHPUnit\Framework\TestCase;

/** @covers \Fedex\SubmitOrderSidebar\Model\Data\BillingField */
class BillingFieldTest extends TestCase
{
    /** @var BillingField  */
    private BillingField $billingField;

    /**
     * Set Up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->billingField = new BillingField();
    }

    /**
     * Test getFieldName method
     *
     * @return void
     * @throws Exception
     */
    public function testGetFieldName()
    {
        $this->billingField->setFieldName('testFieldName');
        $this->assertEquals('testFieldName', $this->billingField->getFieldName());
    }

    /**
     * Test getValue method
     *
     * @return void
     * @throws Exception
     */
    public function testGetValue()
    {
        $this->billingField->setValue('testFieldName');
        $this->assertEquals('testFieldName', $this->billingField->getValue());
    }

}
