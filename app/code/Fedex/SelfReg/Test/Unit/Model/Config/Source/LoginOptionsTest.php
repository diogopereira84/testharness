<?php

namespace Fedex\SelfReg\Test\Unit\Model\Config\Source;

use Fedex\SelfReg\Model\Config\Source\LoginOptions;
use PHPUnit\Framework\TestCase;

class LoginOptionsTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $model;
    protected function setUp(): void
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $this->objectManager->getObject(LoginOptions::class);
    }

    /**
     * Test for toOptionArray method.
     *
     * @return array
     */
    public function testToOptionArray()
    {
        $response = [
            ['value' => 'registered_user', 'label' => __('Auto Approve')],
            ['value' => 'domain_registration', 'label' => __('Domain Approve')],
            ['value' => 'admin_approval', 'label' => __('Admin Approve')]
        ];

        $result = $this->model->toOptionArray();
        $expectedResult = $response;
        $this->assertEquals($expectedResult, $result);
    }
}
