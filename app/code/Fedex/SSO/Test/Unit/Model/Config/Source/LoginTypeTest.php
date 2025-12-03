<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Test\Unit\Model\Config\Source;

use Fedex\SSO\Model\Config\Source\LoginType;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LoginTypeTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $loginType;
    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->loginType = $this->objectManager->getObject(
            LoginType::class
        );
    }

    /**
     * Test testToOptionArray function
     */
    public function testToOptionArray()
    {
        $expected = [
            ['value' => '0', 'label' => __('Select')],
            ['value' => '1', 'label' => __('WLGN')],
            ['value' => '2', 'label' => __('Customer SSO')]
        ];
        $result = $this->loginType->toOptionArray();
        $this->assertEquals($expected, $result);
    }
}
