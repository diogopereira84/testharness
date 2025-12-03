<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\Account;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;
use Fedex\EnhancedProfile\Controller\Account\PreferredPaymentMethod;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Framework\Controller\Result\Json;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test class for Fedex\EnhancedProfile\Controller\Account\PreferredPaymentMethod
 */
class PreferredPaymentMethodTest extends TestCase
{
    protected $preferredPaymentMethod;
    /**
     * @var Curl|MockObject
     */
    protected $curl;

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
     * @var PunchoutHelper|MockObject
     */
    protected $punchoutHelper;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManagerHelper;
    private MockObject|Json $jsonMock;

    /**
     * Test setUp
     */
    public function setUp(): void
    {
        $this->curl = $this->getMockBuilder(Curl::class)
            ->setMethods(['setOptions', 'post'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->enhancedProfile = $this->getMockBuilder(EnhancedProfile::class)
            ->setMethods(['getConfigValue', 'setProfileSession'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPost'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->punchoutHelper = $this->getMockBuilder(PunchoutHelper::class)
            ->setMethods(['getTazToken', 'getAuthGatewayToken'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->preferredPaymentMethod = $this->objectManagerHelper->getObject(
            PreferredPaymentMethod::class,
            [
                'curl' => $this->curl,
                'jsonFactory' => $this->jsonFactory,
                'enhancedProfile' => $this->enhancedProfile,
                'request' => $this->requestMock,
                'punchoutHelper' => $this->punchoutHelper
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
        $tokenArray = '{
                        "access_token": "test",
                        "token_type": "test"
                    }';

        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn($tokenArray);
        $curlOutPut = '{
                          "output": {
                            "profile": {
                              "delivery": {
                                "preferredDeliveryMethod": "PICKUP",
                                "preferredStore": "TX"
                              },
                              "payment": {
                                "preferredPaymentMethod": "ACCOUNT"
                              }
                            }
                          }
                        }';

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->curl->expects($this->any())->method('post')->willReturn($curlOutPut);

        $this->jsonFactory->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertNotEquals(null, $this->preferredPaymentMethod->execute());
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

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->curl->expects($this->any())->method('post')->willThrowException($exception);

        $this->assertNotEquals(null, $this->preferredPaymentMethod->execute());
    }
}
