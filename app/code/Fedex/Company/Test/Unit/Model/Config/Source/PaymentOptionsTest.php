<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Company\Test\Unit\Model\Config\Source;

use Fedex\Company\Model\Config\Source\PaymentOptions;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class PaymentOptionsTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $model;
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->model = $this->objectManager->getObject(PaymentOptions::class);
    }

    /**
     * @test testToOptionArray
     */
    public function testToOptionArray()
    {
        $options = [
            ['value' => PaymentOptions::FEDEX_ACCOUNT_NUMBER, 'label' => __('Fedex Account Number')],
            ['value' => PaymentOptions::CREDIT_CARD, 'label' => __('Credit Card')],
        ];

        $this->assertEquals($options, $this->model->toOptionArray());
    }
}
