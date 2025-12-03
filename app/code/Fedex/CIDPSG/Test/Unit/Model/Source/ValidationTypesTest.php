<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Model\Source;

use Fedex\CIDPSG\Model\Source\ValidationTypes;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Class for validation typle list
 */
class ValidationTypesTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $validationTypesObj;
    /**
     * Set up method
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->validationTypesObj = $this->objectManager->getObject(ValidationTypes::class);
    }

    /**
     * Test toOptionArray
     *
     * @return void
     */
    public function testToOptionArray()
    {
        $response = [
            ['value' => 'text', 'label' => __('Text')],
            ['value' => 'email', 'label' => __('Email')],
            ['value' => 'telephone', 'label' => __('Telephone')],
            ['value' => 'fax', 'label' => __('Fax')],
            ['value' => 'fedex_account', 'label' => __('FedEx Account')],
            ['value' => 'zipcode', 'label' => __('Zipcode')],
            ['value' => 'fedex_shipping_account', 'label' => __('FedEx Shipping Account')]
        ];

        $this->assertEquals($response, $this->validationTypesObj->toOptionArray());
    }
}
