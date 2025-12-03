<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\Account;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;
use Fedex\EnhancedProfile\Controller\Account\UpdateAccount;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Controller\Result\Json;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test class for Fedex\EnhancedProfile\Controller\Account\UpdateAccount
 */
class UpdateAccountTest extends TestCase
{
    protected $updateAccount;
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
    protected MockObject|Json $jsonMock;

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
            ->setMethods(['getConfigValue', 'apiCall', 'setProfileSession', 'getLoggedInProfileInfo'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPost'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->updateAccount = $this->objectManagerHelper->getObject(
            UpdateAccount::class,
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
                        "output": {
                            "accounts": [
                                {
                                    "profileAccountId": "0df81688-1034-483e-b0d1-e28ef75a335c",
                                    "accountNumber": "788848923",
                                    "maskedAccountNumber": "*8923",
                                    "accountLabel": "Ankit",
                                    "accountType": "SHIPPING",
                                    "billingReference": "9910",
                                    "primary": false
                                }
                            ]
                        }
                    }';

        $this->enhancedProfile->expects($this->any())->method('apiCall')->willReturn($apiOutPut);
        $this->enhancedProfile->expects($this->any())->method('setProfileSession')->willReturnSelf();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->jsonFactory->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertNotEquals(null, $this->updateAccount->execute());
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

        $this->assertNotEquals(null, $this->updateAccount->execute());
    }

    /**
     * Test Nick name unique validation
     *
     * @return void
     */
    public function testgetNickNameStatus()
    {
        $dummyJsonData = '{
            "transactionId": "106f4966-f8aa-4fd2-a34a-a6a5b74e473f",
            "output": {
                "profile": {
                    "userProfileId": "3933d6a8-fd00-4519-ad15-fbc17fe606ff",
                    "accounts": [
                        {
                           "profileAccountId": "",
                            "accountNumber": "7888489334679",
                            "accountLabel": "FedEx Account 4697",
                            "billingReference": "9220",
                            "accountType": "PRINTING"
                        }
                    ]
                }
            }
        }';

        $this->requestMock->expects($this->any())->method('getPost')->willReturn('true');
        $this->enhancedProfile->expects($this->any())->method('getLoggedInProfileInfo')
                            ->willReturn(json_decode($dummyJsonData));

        $this->assertEquals(true, $this->updateAccount->getNickNameStatus(true, 'FedEx Account 4697', '630493022'));
    }
}
