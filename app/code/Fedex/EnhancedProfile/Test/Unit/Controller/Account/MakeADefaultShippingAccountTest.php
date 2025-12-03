<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\Account;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;
use Fedex\EnhancedProfile\Controller\Account\MakeADefaultShippingAccount;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test class for Fedex\EnhancedProfile\Controller\Account\MakeADefaultShippingAccount
 */
class MakeADefaultShippingAccountTest extends TestCase
{
    /**
     * @var JsonFactory|MockObject
     */
    protected $jsonFactory;

    /**
     * @var EnhancedProfile|MockObject
     */
    protected $enhancedProfile;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManagerHelper;

    /**
     * @var ObjectManager|MockObject
     */
    protected $makeADefaultShippingAccount;
    private MockObject|Json $jsonMock;

    /**
     * Test setUp
     */
    public function setUp(): void
    {

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->enhancedProfile = $this->getMockBuilder(EnhancedProfile::class)
            ->setMethods(['getConfigValue', 'apiCall', 'setProfileSession'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPost'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->makeADefaultShippingAccount = $this->objectManagerHelper->getObject(
            MakeADefaultShippingAccount::class,
            [
                'jsonFactory' => $this->jsonFactory,
                'enhancedProfile' => $this->enhancedProfile,
                'request' => $this->requestMock
            ]
        );
    }

    /**
     * Test execute method
     *
     * @return void
     */
    public function testExecute()
    {
        $this->requestMock->expects($this->any())->method('getPost')->willReturn('ACCOUNT');
        $this->jsonFactory->expects($this->any())->method('create')->willReturnSelf();

        $this->enhancedProfile->expects($this->any())->method('getConfigValue')->willReturn(
            'https://fxo-retailprofile-service-development.app.clwdev1.paas.fedex.com'
        );

        $apiOutPut = '{
            "transactionId": "3767d727-c0b0-494f-8e16-d52e4fe82255",
            "output": {
              "profile": {
                "delivery": {
                  "preferredDeliveryMethod": "DELIVERY",
                  "preferredStore": "1966"
                },
                "payment": {
                  "preferredPaymentMethod": "CREDIT_CARD"
                },
                "primaryShippingAccount": "610977570"
              }
            }
        }';

        $this->enhancedProfile->expects($this->any())->method('apiCall')->willReturn($apiOutPut);
        $this->enhancedProfile->expects($this->any())->method('setProfileSession')->willReturnSelf();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->jsonFactory->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertNotEquals(null, $this->makeADefaultShippingAccount->execute());
    }

    /**
     * Test execute method with exception
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->requestMock->expects($this->any())->method('getPost')->willReturn('ACCOUNT');
        $this->jsonFactory->expects($this->any())->method('create')->willReturnSelf();

        $this->enhancedProfile->expects($this->any())->method('getConfigValue')->willReturn(
            'https://fxo-retailprofile-service-development.app.clwdev1.paas.fedex.com/'
        );

        $this->enhancedProfile->expects($this->any())->method('apiCall')->willThrowException($exception);

        $this->assertNotEquals(null, $this->makeADefaultShippingAccount->execute());
    }
}
