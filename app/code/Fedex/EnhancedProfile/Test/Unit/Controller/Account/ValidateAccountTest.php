<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\Account;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\EnhancedProfile\Controller\Account\ValidateAccount;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Fedex\Base\Helper\Auth;

/**
 * Test class for Fedex\EnhancedProfile\Controller\Account\ValidateAccount
 */
class ValidateAccountTest extends TestCase
{
    protected $ValidateAccount;
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
            ->setMethods(['getAccountSummary', 'getLoggedInProfileInfo', 'isLoggedIn'])
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
        $this->ValidateAccount = $this->objectManagerHelper->getObject(
            ValidateAccount::class,
            [
                'jsonFactory' => $this->jsonFactory,
                'enhancedProfile' => $this->enhancedProfile,
                'request' => $this->requestMock,
                'authHelper' => $this->baseAuthMock
            ]
        );
    }

    /**
     * Test execute method WithToggleOnForLoggedIn
     *
     * @return void
     */
    public function testExecuteWithToggleOnForLoggedIn()
    {
        $this->baseAuthMock->expects($this->once())
            ->method('isLoggedIn')->willReturn(true);
        $this->apiCall();

        $this->assertNotNull($this->ValidateAccount->execute());
    }

    /**
     * Test execute method WithToggleOnForNonLoggedIn
     *
     * @return void
     */
    public function testExecuteWithToggleOnForNonLoggedIn()
    {

        $this->apiCall();

        $this->assertNotNull($this->ValidateAccount->execute());
    }

    /**
     * Test execute method
     *
     * @return void
     */
    public function testExecute()
    {
        $this->apiCall();

        $this->assertNotNull($this->ValidateAccount->execute());
    }

    /**
     * Common test logic for api Call
     */
    public function apiCall()
    {
        $dummyJsonData = '{
            "transactionId": "106f4966-f8aa-4fd2-a34a-a6a5b74e473f",
            "output": {
              "profile": {
                "accounts": [
                  {
                    "accountNumber": "610977553",
                    "maskedAccountNumber": "*7553",
                    "accountLabel": "My Account-553",
                    "accountType": "PRINTING",
                    "primary": true,
                    "accountValid": false
                  }
                ]
              }
            }
        }';

        $this->requestMock->expects($this->any())->method('getPost')->willReturn('accountNumber');
        $this->jsonFactory->expects($this->any())->method('create')->willReturnSelf();
        $getAccountSummary = ['account_status' => 'active'];
        $this->enhancedProfile->expects($this->any())->method('getAccountSummary')->willReturn($getAccountSummary);
        $this->enhancedProfile->expects($this->any())->method('getLoggedInProfileInfo')
                            ->willReturn(json_decode($dummyJsonData));
        $this->jsonFactory->expects($this->any())->method('setData')->willReturnSelf();
    }
}
