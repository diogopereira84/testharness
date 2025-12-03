<?php
/**
 * Copyright © FedEx. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Test\Unit\Model\Config\Source;

use Fedex\Company\Model\Config\Source\PaymentAcceptance;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class PaymentAcceptanceTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $model;
    protected function setUp(): void
    {

        $this->objectManager = new ObjectManager($this);

        $this->model = $this->objectManager->getObject(PaymentAcceptance::class);
    }

    /**
     * B-1013340 | Anuj | RT-ECVS-Resolve PHPUnit Console Errors for module 'Company'
     * Test for toOptionArray method.
     *
     * @return array
     */
    public function testToOptionArray()
    {
        $response = [
            ['value' => '', 'label' => __('Select payment Type')],
            ['value' => 'legacyaccountnumber', 'label' => __('Legacy Site Provided FedEx Account Number')],
            ['value' => 'sitecreditcard', 'label' => __('Legacy Site Provided Credit Card')],
            ['value' => 'purchaseorder', 'label' => __('Purchase Order')],
            ['value' => 'accountnumbers', 'label' => __('Account Numbers')],
        ];

        $this->assertEquals($response, $this->model->toOptionArray());
    }
}
