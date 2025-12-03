<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Model\Source;

use Fedex\SharedCatalogCustomization\Model\Source\Status;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $status;
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->status = $this->objectManager->getObject(Status::class);
    }

    /**
     * Retrieve option array
     *
     * @return string[]
     */
    public function testGetOptionArray()
    {
        $response = [
            1 => __('Enabled'),
            0 => __('Disabled')
        ];
 
        $result = $this->status->getOptionArray();
        $expectedResult = $response;
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Retrieve option array with empty value
     *
     * @return string[]
     */
    public function testGetAllOptions()
    {
        $response = [
            [
                'value' => 1,
                'label' => __('Enabled')
            ],
            [
                'value' => 0,
                'label' => __('Disabled')
            ]
        ];
 
        $result = $this->status->getAllOptions();
        $expectedResult = $response;
        $this->assertEquals($expectedResult, $result);
    }
}
