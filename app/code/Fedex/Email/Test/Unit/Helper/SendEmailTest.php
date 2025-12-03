<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Email\Test\Unit\Helper;

use Fedex\Email\Helper\SendEmail;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Phrase;

class SendEmailTest extends \PHPUnit\Framework\TestCase
{
    public const CUST_EMAIL = 'vivek2.singh@infogain.com';
    public const GTN_NO = '2001045';
    public const FEDEX_OFFICE = 'Fedex Office';
    public const CHANNEL = 'Print On Demand';
    public const PROD_COST_AMT = '$4.00';
    public const CUST_RELATIONS_PHONE = '1.800.GoFedEx 1.800.463.3339';
    public const TAZ_EMAIL_URL = 'https://apitest.fedex.com/email/fedexoffice/v2/email';
    public const TAJ_EMAIL_API_URL = 'fedex/taz/taz_email_api_url';
    public const TRANS_EMAIL = 'trans_email/ident_general/email';

    /**
     * @var ScopeInterface|MockObject
     */
    private $configInterface;

    /**
     * @var EncryptorInterface|MockObject
     */
    private $encryptorInterface;

    /**
     * @var Fedex\Email\Helper\SendEmail
     */
    private $email;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->configInterface = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->encryptorInterface = $this->getMockBuilder(EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->email = $objectManagerHelper->getObject(
            SendEmail::class,
            [
                'configInterface' => $this->configInterface,
                'encryptorInterface' => $this->encryptorInterface
            ]
        );
    }

    public function testSendMail()
    {
        $customerData = ['name' => 'vivek', 'email' => self::CUST_EMAIL];
        $templateId = 'ePro_quote_confirmation';
        $payLoad = "abc";
        $templateD = [
            'order' => [
                'primaryContact' => [
                    'firstLastName' => 'vivek',
                ],
                'gtn' => self::GTN_NO, // order no  for quote.
                'productionCostAmount' => self::PROD_COST_AMT, //quote sub total price excluding shipping & taxes.
            ],
            'producingCompany' => [
                'name' => self::FEDEX_OFFICE,
                'customerRelationsPhone' => self::CUST_RELATIONS_PHONE,
            ],
            'user' => [
                'emailaddr' => self::CUST_EMAIL,
            ],
            'channel' => self::CHANNEL,
        ];
        $templateData = json_encode($templateD);
        $tokenData = [
            'access_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY
            2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2MTQxODYxNTIsImF1dGhvcml0aWVzIj
            pbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6IjM
            zNTI0NmY4LWRmNjEtNDMxZS04ZDY1LWE1MDI0YjQ5MTBjZCIsImNsaWVudF9pZCI6IjM1MzcxMzFf
            TUFHRU5UT19QT0RfU0VSVklDRSJ9.JwBON1oZAALzGnHmbVfS7W2ke2UFuxH0nzBREbyHv1ZQMUIY
            2tq9q9GSMp2enK2yWEYqn_mq_nWIkV_78bdUYtJR-3kEU3HoALKTBAxAGCjadVZENjPayvIFAxCxe_
            K5fTXFDxQo2sv6VFMPRObOlEgfQWh_lX_R8O2xi3E5KLVlkyxfuXqIf4CQeyz6jUgCGbVKO4sjA9E
            HRUrBI9LzMtWIHIhefqXCtCkLya5ywo239EQSA2z2KRSYYeRLlBVf2Ie-wLLttvmN22wyq_Lp4ZDc
            jcKbQol7oOP89wDQryMkrUWlUCFFhuuOcqx8F8bfUTwuhTYq2TEhhCKFl1k24CWQIKL8S1gtuddct
            ia7ValuRNYjhj5UiVm6uWCJtvLDcDiSY08i2020gX5K4gaXm-aM4Xq-CLac-nS2OMbTM5LEzRSds7
            M7MAe2f1V6LLUvsXR9LAPhDpM2QL_P2TQKgxTcMKmdloLJB_u0cC-XdipF_w5BmNRgNFBcyF1E9UO
            BeK6KGvpOvkg0FOw9SgOEYeX1ANOMyqkx-Kz3GfzIVw3OSzxmrMv1z8bMyPRbNX3EcEslQxB__Gxx
            xsnp5Y48mzj6EQzLtnnBSN-8S_21GP5XZvoZ5y_cvNcDENryWCp6-XJ983Mz9H7OFRJR44jJ2k1D5icOfd2HlyE98uzHdwk',
            'token_type' => 'Bearer',
            'auth_token' => 'l7xx1cb26690fadd4b2789e0888a96b80ee2'
        ];

        $tazEmailUrl = self::TAZ_EMAIL_URL;
        $from = 'no-reply.ecommerce@fedex.com';
        $this->configInterface->expects($this->any())->method('getValue')
            ->withConsecutive([self::TAJ_EMAIL_API_URL], [self::TRANS_EMAIL])
            ->willReturnOnConsecutiveCalls($tazEmailUrl, $from);

        $this->assertEquals(
            true,
            !empty($this->email->sendMail($customerData, $templateId, $templateData, $tokenData, $payLoad))
        );
    }

    public function testSendMail1()
    {
        $customerData = [
            'name' => 'vivek',
            'email' => ''
        ];
        $templateId = 'ePro_quote_confirmation';
        $templateD = [
            'order' => [
                'primaryContact' => [
                    'firstLastName' => '',
                ],
                'gtn' => self::GTN_NO, // order no  for quote.
                'productionCostAmount' => self::PROD_COST_AMT, //quote sub total price excluding shipping & taxes.
            ],
            'producingCompany' => [
                'name' => self::FEDEX_OFFICE,
                'customerRelationsPhone' => self::CUST_RELATIONS_PHONE,
            ],
            'user' => [
                'emailaddr' => '',
            ],
            'channel' => self::CHANNEL,
        ];
        $templateData = json_encode($templateD);
        $tokenData = [
            'access_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xpZW50czp3
            cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2MTQxODYxNTIsImF1dGhvcml0aWVzIjpbIm1hZ2VudG8ub3JkZXIi
            LCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6IjMzNTI0NmY4LWRmNjEtNDMxZS04ZDY1LWE1
            MDI0YjQ5MTBjZCIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.JwBON1oZAALzGnH
            mbVfS7W2ke2UFuxH0nzBREbyHv1ZQMUIY2tq9q9GSMp2enK2yWEYqn_mq_nWIkV_78bdUYtJR-3kEU3HoALKTBAx
            AGCjadVZENjPayvIFAxCxe_K5fTXFDxQo2sv6VFMPRObOlEgfQWh_lX_R8O2xi3E5KLVlkyxfuXqIf4CQeyz6jUg
            CGbVKO4sjA9EHRUrBI9LzMtWIHIhefqXCtCkLya5ywo239EQSA2z2KRSYYeRLlBVf2Ie-wLLttvmN22wyq_Lp4ZD
            cjcKbQol7oOP89wDQryMkrUWlUCFFhuuOcqx8F8bfUTwuhTYq2TEhhCKFl1k24CWQIKL8S1gtuddctia7ValuRNY
            jhj5UiVm6uWCJtvLDcDiSY08i2020gX5K4gaXm-aM4Xq-CLac-nS2OMbTM5LEzRSds7M7MAe2f1V6LLUvsXR9LAP
            hDpM2QL_P2TQKgxTcMKmdloLJB_u0cC-XdipF_w5BmNRgNFBcyF1E9UOBeK6KGvpOvkg0FOw9SgOEYeX1ANOMyqk
            x-Kz3GfzIVw3OSzxmrMv1z8bMyPRbNX3EcEslQxB__Gxxxsnp5Y48mzj6EQzLtnnBSN-8S_21GP5XZvoZ5y_cvNc
            DENryWCp6-XJ983Mz9H7OFRJR44jJ2k1D5icOfd2HlyE98uzHdwk',
            'token_type' => 'Bearer',
            'auth_token' => 'l7xx1cb26690fadd4b2789e0888a96b80ee2'
        ];

        $from = 'no-reply.ecommerce@fedex.com';
        $this->configInterface->expects($this->any())->method('getValue')
            ->withConsecutive([self::TAJ_EMAIL_API_URL], [self::TRANS_EMAIL])
            ->willReturnOnConsecutiveCalls(false, $from);
        $this->email->sendMail(
            $customerData,
            $templateId,
            $templateData,
            $tokenData
        );
    }

    public function testGetTazEmailUrl()
    {
        $tazEmailUrl = self::TAZ_EMAIL_URL;
        $this->configInterface->expects($this->any())->method('getValue')->with(self::TAJ_EMAIL_API_URL)
            ->willReturn($tazEmailUrl);

        $this->assertEquals($tazEmailUrl, $this->email->getTazEmailUrl());
    }

    public function testGetTazEmailUrl1()
    {
        $this->configInterface->expects($this->any())->method('getValue')->with(self::TAJ_EMAIL_API_URL)
            ->willReturn(false);

        $this->assertEquals(false, $this->email->getTazEmailUrl());
    }

    public function testSendMailWithException()
    {
        $customerData = ['name' => 'vivek', 'email' => self::CUST_EMAIL];
        $templateId = 'ePro_quote_confirmation';
        $templateD = [
            'order' =>
            [
                'primaryContact' =>
                [
                    'firstLastName' => 'vivek',
                ],
                'gtn' => self::GTN_NO, // order no for quote.
                'productionCostAmount' => self::PROD_COST_AMT, //quote sub total price excluding shipping & taxes.
            ],
            'producingCompany' =>
            [
                'name' => self::FEDEX_OFFICE,
                'customerRelationsPhone' => self::CUST_RELATIONS_PHONE,
            ],
            'user' =>
            [
                'emailaddr' => self::CUST_EMAIL,
            ],
            'channel' => self::CHANNEL,
        ];
        $templateData = json_encode($templateD);
        $tokenData = [
            'access_token' => 'eyJhbGciOiJSUz',
            'token_type' => 'Bearer',
            'auth_token' => 'l7xx1cb26690fadd4b2789e0888a96b80ee2'
        ];
        $phrase = new Phrase(__('Something went wrong. Please try again later.'));
        $exception = new \Exception($phrase);
        $this->configInterface->expects($this->any())
            ->method('getValue')
            ->withConsecutive([self::TAJ_EMAIL_API_URL], [self::TRANS_EMAIL])
            ->willThrowException($exception);

        $this->assertEquals('', !empty($this->email->sendMail($customerData, $templateId, $templateData, $tokenData)));
    }

    public function testSendMailWithSubject()
    {
        $customerData = ['name' => 'vivek', 'email' => self::CUST_EMAIL];
        $templateId = 'ePro_quote_confirmation';
        $payLoad = null;
        $subject = "Test Subject Line";
        $templateD = [
            'order' => [
                'primaryContact' => [
                    'firstLastName' => 'vivek',
                ],
                'gtn' => self::GTN_NO,
                'productionCostAmount' => self::PROD_COST_AMT,
            ],
            'producingCompany' => [
                'name' => self::FEDEX_OFFICE,
                'customerRelationsPhone' => self::CUST_RELATIONS_PHONE,
            ],
            'user' => [
                'emailaddr' => self::CUST_EMAIL,
            ],
            'channel' => self::CHANNEL,
        ];
        $templateData = json_encode($templateD);
        $tokenData = [
            'access_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.test_token',
            'token_type' => 'Bearer',
            'auth_token' => 'l7xx1cb26690fadd4b2789e0888a96b80ee2'
        ];

        $tazEmailUrl = self::TAZ_EMAIL_URL;
        $from = 'no-reply.ecommerce@fedex.com';
        $this->configInterface->expects($this->any())->method('getValue')
            ->withConsecutive([self::TAJ_EMAIL_API_URL], [self::TRANS_EMAIL])
            ->willReturnOnConsecutiveCalls($tazEmailUrl, $from);
        // Mock the sendMail method to simulate sending an email
        try {
            $this->email->sendMail($customerData, $templateId, $templateData, $tokenData, $payLoad, $subject);
            $this->assertTrue(true); // If we reach here, the test passes
        } catch (\Exception $e) {
            $this->fail("Exception thrown when subject is provided: " . $e->getMessage());
        }
    }

    public function testSendMailWithEmptyAccessToken()
    {
        $customerData = ['name' => 'vivek', 'email' => self::CUST_EMAIL];
        $templateId = 'ePro_quote_confirmation';
        $templateD = [
            'order' => [
                'primaryContact' => [
                    'firstLastName' => 'vivek',
                ],
                'gtn' => self::GTN_NO,
                'productionCostAmount' => self::PROD_COST_AMT,
            ],
            'producingCompany' => [
                'name' => self::FEDEX_OFFICE,
                'customerRelationsPhone' => self::CUST_RELATIONS_PHONE,
            ],
            'user' => [
                'emailaddr' => self::CUST_EMAIL,
            ],
            'channel' => self::CHANNEL,
        ];
        $templateData = json_encode($templateD);
        $tokenData = [
            'access_token' => '', // Empty access token
            'token_type' => 'Bearer',
            'auth_token' => 'l7xx1cb26690fadd4b2789e0888a96b80ee2'
        ];

        $tazEmailUrl = self::TAZ_EMAIL_URL;
        $from = 'no-reply.ecommerce@fedex.com';
        $this->configInterface->expects($this->any())->method('getValue')
            ->withConsecutive([self::TAJ_EMAIL_API_URL], [self::TRANS_EMAIL])
            ->willReturnOnConsecutiveCalls($tazEmailUrl, $from);

        // Test that method completes without error when access token is empty
        $result = $this->email->sendMail($customerData, $templateId, $templateData, $tokenData);
        $this->assertNull($result); // The method should return null since it will hit the logger and exit
    }
}
