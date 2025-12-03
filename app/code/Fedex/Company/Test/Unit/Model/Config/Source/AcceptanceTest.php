<?php

namespace Fedex\Company\Test\Unit\Model\Config\Source;

use PHPUnit\Framework\TestCase;
use Fedex\Company\Model\Config\Source\Acceptance;

class AcceptanceTest extends TestCase
{

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $model;
    protected function setUp() : void
    {

        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->model = $this->objectManager->getObject(Acceptance::class);
    }

    /**
     * Test for toOptionArray method.
     *
     * @return array
     */
    public function testToOptionArray()
    {
        $response=[['value' => '', 'label' => __('Select Rule Type')],['value' => 'extrinsic', 'label' => __('Extrinsic')], ['value' => 'contact', 'label' => __('Contact')], ['value' => 'both', 'label' => __('Extrinsic & Contact')] ];

        $result = $this->model->toOptionArray();
        $expectedResult = $response;
        $this->assertEquals($expectedResult, $result);
    }
}
