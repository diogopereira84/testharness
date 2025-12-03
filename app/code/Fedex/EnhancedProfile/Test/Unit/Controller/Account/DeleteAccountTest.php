<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\Account;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\EnhancedProfile\Controller\Account\DeleteAccount;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Fedex\Base\Helper\Auth;

/**
 * Test class for Fedex\EnhancedProfile\Controller\Account\DeleteAccount
 */
class DeleteAccountTest extends TestCase
{
    protected $deleteAccount;
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

    protected Auth|MockObject $baseAuthMock;
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
            ->setMethods(['getConfigValue', 'apiCall', 'setProfileSession',
                'isLoggedIn'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPost'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->deleteAccount = $this->objectManagerHelper->getObject(
            DeleteAccount::class,
            [
                'jsonFactory' => $this->jsonFactory,
                'enhancedProfile' => $this->enhancedProfile,
                'request' => $this->requestMock,
                'authHelper' => $this->baseAuthMock
            ]
        );
    }

    /**
     * Test execute method with Toggle On For Logged In User
     *
     * @return void
     */
    public function testExecuteWithToggleOnWithLoggedIn()
    {
        $this->baseAuthMock->expects($this->once())
            ->method('isLoggedIn')->willReturn(true);
        $this->apiCall();
        $this->assertNotNull($this->deleteAccount->execute());
    }

    /**
     * Test execute method with Toggle On For non Logged In User
     *
     * @return void
     */
    public function testExecuteWithToggleOnWithNonLoggedIn()
    {

        $this->apiCall();
        $this->assertNotNull($this->deleteAccount->execute());
    }

    /**
     * Test execute method
     *
     * @return void
     */
    public function testExecute()
    {
        $this->apiCall();

        $this->assertNotNull($this->deleteAccount->execute());
    }

    /**
     * Common test logic for api Call
     */
    public function apiCall()
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

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->jsonFactory->expects($this->any())->method('setData')->willReturnSelf();
        $this->enhancedProfile->expects($this->any())->method('setProfileSession')->willReturnSelf();
    }

    /**
     * Test execute method with exception WithToggleOn
     *
     * @return void
     */
    public function testExecuteWithExceptionWithToggleOn()
    {
        $this->baseAuthMock->expects($this->once())
            ->method('isLoggedIn')->willReturn(true);
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->requestMock->expects($this->any())->method('getPost')->willReturn('ACCOUNT');
        $this->jsonFactory->expects($this->any())->method('create')->willReturnSelf();

        $this->enhancedProfile->expects($this->any())->method('getConfigValue')->willReturn(
            'https://fxo-retailprofile-service-development.app.clwdev1.paas.fedex.com/'
        );

        $this->enhancedProfile->expects($this->any())->method('apiCall')->willThrowException($exception);

        $this->assertNotNull($this->deleteAccount->execute());
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

        $this->assertNotNull($this->deleteAccount->execute());
    }
}
