<?php

namespace Fedex\Company\Test\Unit\Model\Config\Source;

use PHPUnit\Framework\TestCase;
use Fedex\Company\Model\Config\Source\IconographyOptions;

class IconographyOptionsTest extends TestCase
{

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $model;
    protected function setUp() : void
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $this->objectManager->getObject(IconographyOptions::class);
    }

    /**
     * Test for toOptionArray method.
     *
     * @return array
     */
    public function testToOptionArray()
    {
        $response = [
            [
                'value' => '',
                'label' => __('Please select banner icon...')
            ],
            [
                'value' => 'warning',
                'label' => __('Warning')
            ],
            [
                'value' => 'information',
                'label' => __('Information')
            ]
        ];

        $result = $this->model->toOptionArray();

        $this->assertEquals($response, $result);
    }
}
