<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SDE\Test\Unit\Block;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\SDE\Block\LoginInfo;
use Fedex\SDE\ViewModel\SdeSsoConfiguration;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LoginInfoTest extends TestCase
{
    protected $sdeSsoConfiguration;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var LoginInfoData
     */
    protected $LoginInfoData;
    
    /**
     * Test setUp
     */
    protected function setUp(): void
    {

        $this->sdeSsoConfiguration = $this->getMockBuilder(SdeSsoConfiguration::class)
            ->setMethods(
                [
                    'getSdeCustomerName',
                    'isSdeCustomer'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
      
        $this->objectManager = new ObjectManager($this);
        $this->LoginInfoData = $this->objectManager->getObject(
            LoginInfo::class,
            [
                'sdeSsoConfiguration' => $this->sdeSsoConfiguration

            ]
        );
    }

    /**
     * Test getSdeCustomerName
     */
    public function testGetSdeCustomerName()
    {

        $name = 'Shivani';
        $this->sdeSsoConfiguration->expects($this->any())->method('getSdeCustomerName')->willReturn($name);
        $this->assertsame($name, $this->LoginInfoData->getSdeCustomerName());
    }

    /**
     * Test isSdeCustomer
     */
    public function testIsSdeCustomer()
    {
        $this->sdeSsoConfiguration->expects($this->any())->method('isSdeCustomer')->willReturn(1);
        $this->assertsame(1, $this->LoginInfoData->isSdeCustomer());
    }
}
