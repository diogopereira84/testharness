<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Company\Test\Unit\Model\Config\Source;

use Fedex\Company\Model\Config\Source\FedExAccountOptions;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class FedExAccountOptionsTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $model;
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->model = $this->objectManager->getObject(FedExAccountOptions::class);
    }

    /**
     * @test testToOptionArray
     */
    public function testToOptionArray()
    {
        $options = [
            [
                'label' => 'Legacy Site Provided Account',
                'value' => FedExAccountOptions::LEGACY_FEDEX_ACCOUNT,
            ],
            [
                'label' => 'Custom Account',
                'value' => FedExAccountOptions::CUSTOM_FEDEX_ACCOUNT,
            ],
        ];

        $this->assertEquals($options, $this->model->toOptionArray());
    }
}
