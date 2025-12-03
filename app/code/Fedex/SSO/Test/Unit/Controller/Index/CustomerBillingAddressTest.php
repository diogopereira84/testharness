<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Test\Unit\Controller\Index;

use Fedex\SSO\Model\ToggleConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\SSO\Controller\Index\CustomerBillingAddress;
use Magento\Customer\Model\Session;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerBillingAddressTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var CustomerBillingAddress $customerBillingAddressData
     */
    protected $customerBillingAddressData;

    /**
     * @var SsoConfiguration $ssoConfiguration
     */
    protected $ssoConfiguration;

    /**
     * @var Session $customerSession
     */
    protected $customerSession;

    /**
     * @var JsonFactory $jsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Json $json
     */
    protected $json;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->ssoConfiguration = $this->getMockBuilder(SsoConfiguration::class)
            ->setMethods(
                [
                    'isFclCustomer',
                    'getDefaultBillingAddressById'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->setMethods(
                [
                    'getCustomerId'

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

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->customerBillingAddressData = $this->objectManager->getObject(
            CustomerBillingAddress::class,
            [
                'ssoConfiguration' => $this->ssoConfiguration,
                'customerSession' => $this->customerSession,
                'jsonFactory' => $this->jsonFactory,
                'json' => $this->json,
                'toggleConfig' => $this->toggleConfig
            ]
        );
    }

    /**
     * Test execute with default address function
     */
    public function testExecute()
    {
        $billingAddress = [
                            'company' => 'Infogain',
                            'street' => [0 => 'Legacy'],
                            'city' => 'Plano',
                            'region' => ['region_code' => 'TX'],
                            'postcode' => '75024',
                        ];
        $this->ssoConfiguration->expects($this->any())->method('isFclCustomer')->willReturn(1);
        $this->ssoConfiguration->expects($this->any())->method('getDefaultBillingAddressById')
                                ->willReturn($billingAddress);
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->json);
        $this->json->expects($this->any())->method('setData');

        $this->assertEquals($this->json, $this->customerBillingAddressData->execute());
    }

    /**
     * Test execute without default address function
     */
    public function testExecuteWithoutDefualtBillingAddress()
    {
        $billingAddress = 'Customer has not set default billing address';
        $this->ssoConfiguration->expects($this->any())->method('isFclCustomer')->willReturn(1);
        $this->ssoConfiguration->expects($this->any())->method('getDefaultBillingAddressById')
                                ->willReturn($billingAddress);

        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->json);
        $this->json->expects($this->any())->method('setData');

        $this->assertEquals($this->json, $this->customerBillingAddressData->execute());
    }

    /**
     * Test execute without customer login
     */
    public function testExecuteWithoutLogin()
    {
        $this->ssoConfiguration->expects($this->any())->method('isFclCustomer')->willReturn(0);

        $this->assertEquals(null, $this->customerBillingAddressData->execute());
    }
}
