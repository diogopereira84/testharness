<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpressCheckout\Test\Unit\Controller\Customer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\ExpressCheckout\Controller\Customer\GetProfileSession;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetProfileSessionTest extends TestCase
{

    /**
     * @var JsonFactory|MockObject
     */
    protected $jsonFactory;

    /**
     * @var Session|MockObject
     */
    protected $customerSession;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var GetProfileSession|MockObject
     */
    protected $getProfileSession;

    /**
     * Function setUp
     */
    protected function setUp(): void
    {
        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(
                [
                    'create',
                    'setData'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->customerSession = $this->getMockBuilder(Session::class)
            ->setMethods(
                [
                    'getProfileSession'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->getProfileSession = $this->objectManager->getObject(
            GetProfileSession::class,
            [
                'jsonFactory' => $this->jsonFactory,
                'customerSession' => $this->customerSession
            ]
        );
    }

    /**
     * Test execute
     *
     * @return void
     */
    public function testExecute()
    {
        $returnValue = $this->getProfileSessionData();
        $this->jsonFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->jsonFactory->expects($this->once())->method('setData')->willReturnSelf();
        $this->customerSession->expects($this->once())->method('getProfileSession')->willReturn($returnValue);
        $this->assertEquals($this->jsonFactory, $this->getProfileSession->execute());
    }

    /**
     * Get profile session data
     *
     * @return JSON
     */
    public function getProfileSessionData()
    {
        return '{
            "transactionId":"450c55ed-d04c-49ed-8f69-ada0783a6912",
            "output":{
               "profile":{
                  "userProfileId":"d8381b64-04e9-496c-b06e-bf3e0378ded2",
                  "uuId":"wX85bDekJw",
                  "customerId":"wX85bDekJw",
                  "contact":{
                     "personName":{
                        "firstName":"Siddhi",
                        "lastName":"Soni"
                     },
                     "company":{
                        
                     },
                     "emailDetail":{
                        "emailAddress":"chandra.verma@infogain.com"
                     },
                     "phoneNumberDetails":[
                        {
                           "phoneNumber":{
                              "number":"9889014141"
                           },
                           "primary":false
                        },
                        {
                           "phoneNumber":{
                              
                           },
                           "primary":false
                        },
                        {
                           "phoneNumber":{
                              
                           },
                           "primary":false
                        }
                     ],
                     "address":{
                        "streetLines":[
                           "5601 West Parker Road",
                           ""
                        ],
                        "city":"Plano",
                        "stateOrProvinceCode":"TX",
                        "postalCode":"75093",
                        "countryCode":"US"
                     }
                  },
                  "canvaId":"9f4bb005-8420-45f6-9e80-0f4b3e88d7c8",
                  "delivery":{
                     "preferredDeliveryMethod":"PICKUP",
                     "preferredStore":"3114",
                     "postalCode":"75093"
                  },
                  "payment":{
                     "preferredPaymentMethod":"CREDIT_CARD"
                  },
                  "creditCards":[
                     {
                        "profileCreditCardId":"75ae9fd1-507e-405f-9507-e2a9da5fec5e",
                        "creditCardLabel":"VISA_11111",
                        "creditCardType":"VISA",
                        "maskedCreditCardNumber":"11111",
                        "cardHolderName":"Siddhi",
                        "expirationMonth":"10",
                        "tokenExpirationDate":"2026-01-17T00:00:00Z",
                        "expirationYear":"2030",
                        "billingAddress":{
                           "company":{
                              
                           },
                           "streetLines":[
                              "Legacy Honey",
                              ""
                           ],
                           "city":"Plano",
                           "stateOrProvinceCode":"NY",
                           "postalCode":"10008",
                           "countryCode":"US"
                        },
                        "primary":true,
                        "tokenExpired":false
                     }
                  ]
               }
            }
         }';
    }
}
