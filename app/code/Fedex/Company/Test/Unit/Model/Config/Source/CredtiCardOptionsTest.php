<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Company\Test\Unit\Model\Config\Source;

use Fedex\Company\Model\Config\Source\CredtiCardOptions;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class CredtiCardOptionsTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $model;
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->model = $this->objectManager->getObject(CredtiCardOptions::class);
    }

    /**
     * @test testToOptionArray
     */
    public function testToOptionArray()
    {
        $options = [
            [
                'label' => 'Legacy Site Provided Credit Card',
                'value' => CredtiCardOptions::LEGACY_SITE_CREDIT_CARD,
            ],
            [
                'label' => 'New Credit Card',
                'value' => CredtiCardOptions::NEW_CREDIT_CARD,
            ],
        ];

        $this->assertEquals($options, $this->model->toOptionArray());
    }
}
