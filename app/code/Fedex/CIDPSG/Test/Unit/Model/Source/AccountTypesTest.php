<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Model\Source;

use Fedex\CIDPSG\Model\Source\AccountTypes;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Test Class for megamenu categoryLevel class
 */
class AccountTypesTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $model;
    /**
     * Set up method
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->model = $this->objectManager->getObject(AccountTypes::class);
    }

    /**
     * Test for toOptionArray method.
     *
     * @return void
     */
    public function testToOptionArray()
    {
        $response = [
            ['value' => ' ', 'label' => __('Please select account type')],
            ['value' => '0', 'label' => __('Discount Account')],
            ['value' => '1', 'label' => __('Invoice Account')],
            ['value' => '2', 'label' => __('Both')],
        ];

        $this->assertNotEquals($response, $this->model->toOptionArray());
    }
}
