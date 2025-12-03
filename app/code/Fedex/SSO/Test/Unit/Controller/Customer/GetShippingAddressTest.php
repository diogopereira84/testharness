<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Test\Unit\Controller\Index;

use Fedex\SSO\Controller\Customer\GetShippingAddress;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Test class for GetShippingAddressTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class GetShippingAddressTest extends TestCase
{
    protected $getShippingAddress;
    /**
     * @var SsoConfiguration $ssoConfiguration
     */
    protected $ssoConfiguration;

    /**
     * @var JsonFactory $jsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Json $json
     */
    protected $json;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * Function setUp
     */
    protected function setUp(): void
    {
        $this->ssoConfiguration = $this->getMockBuilder(SsoConfiguration::class)
            ->setMethods(
                [
                    'isFclCustomer',
                    'getDefaultShippingAddressById',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(
                [
                    'create',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->getShippingAddress = $this->objectManager->getObject(
            GetShippingAddress::class,
            [
                'ssoConfiguration' => $this->ssoConfiguration,
                'jsonFactory' => $this->jsonFactory,
                'json' => $this->json,

            ]
        );
    }

    /**
     * Function testExecuteWithLogin
     *
     * @return void
     */
    public function testExecuteWithLogin()
    {
        $shippingAddress = [
            'firstname' => "Sde",
            'lastname' => "walmart",
            'custom_attributes' => ['email_id' => 'test@fedex.com', "ext" => "1"],
            'company' => 'Infogain',
            'street' => [0 => '7900', 1 => "Legacy"],
            'city' => 'Plano',
            'region' => ['region_code' => 'TX'],
            'postcode' => '75024',
            'telephone' => "9999999999",

        ];
        $this->ssoConfiguration->expects($this->any())->method('isFclCustomer')->willReturn(1);
        $this->ssoConfiguration->expects($this->any())
            ->method('getDefaultShippingAddressById')
            ->willReturn($shippingAddress);
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->json);
        $this->json->expects($this->any())->method('setData');
        $this->assertEquals($this->json, $this->getShippingAddress->execute());
    }

    /**
     * Test execute without default address function
     *
     * @return void
     */
    public function testExecuteWithoutDefualtShippingAddress()
    {
        $shippingAddress = 'Customer has not set shipping address';
        $this->ssoConfiguration->expects($this->any())->method('isFclCustomer')->willReturn(1);
        $this->ssoConfiguration->expects($this->any())
            ->method('getDefaultShippingAddressById')
            ->willReturn($shippingAddress);
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->json);
        $this->json->expects($this->any())->method('setData');
        $this->assertEquals($this->json, $this->getShippingAddress->execute());
    }

    /**
     * Test execute without customer login
     *
     * @return void
     */
    public function testExecuteWithoutLogin()
    {
        $this->ssoConfiguration->expects($this->any())->method('isFclCustomer')->willReturn(0);
        $this->assertEquals(null, $this->getShippingAddress->execute());
    }
}
