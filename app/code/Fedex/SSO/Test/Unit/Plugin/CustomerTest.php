<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\SSO\Test\Unit\Plugin;

use Fedex\SSO\Plugin\Customer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Test class for customer plugin
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class CustomerTest extends TestCase
{
    protected $customerDataMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $customerData;
    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->customerDataMock = $this->getMockBuilder(\Magento\Customer\Model\Data\Customer::class)
            ->setMethods(['getCustomAttribute', 'getValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->customerData = $this->objectManager->getObject(
            Customer::class,
            [
                'customerDataMock' => $this->customerDataMock

            ]
        );
    }

    /**
     * Test afterGetEmail function
     */
    public function testAfterGetEmail()
    {
        $this->customerDataMock
            ->expects($this->any())
            ->method('getCustomAttribute')
            ->willReturnSelf();
        $this->customerDataMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn('j.doe@example.com');

        $arrCustomer = 'j.doe@example.com';
        $this->assertEquals(
            $arrCustomer,
            $this->customerData->afterGetEmail($this->customerDataMock, 'abc@email.com')
        );
    }
}
