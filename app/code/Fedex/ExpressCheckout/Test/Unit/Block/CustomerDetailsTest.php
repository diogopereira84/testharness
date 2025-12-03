<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpressCheckout\Test\Unit\Block;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\ExpressCheckout\Block\CustomerDetails;
use Fedex\ExpressCheckout\ViewModel\ExpressCheckout as ExpressCheckoutViewModel;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerDetailsTest extends TestCase
{
    /**
     * @var ExpressCheckoutViewModel|MockObject
     */
    protected $expressCheckoutViewModel;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var CustomerDetails|MockObject
     */
    protected $customerDetails;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {

        $this->expressCheckoutViewModel = $this->getMockBuilder(ExpressCheckoutViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerProfileSession', 'getCustomerProfileSessionWithExpiryToken'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->customerDetails = $this->objectManager->getObject(
            CustomerDetails::class,
            [
                'expressCheckout' => $this->expressCheckoutViewModel
            ]
        );
    }

    /**
     * Test getLoggedInProfileInfo
     */
    public function testGetLoggedInProfileInfo()
    {
        $returnValue = '{
                          "output": {
                            "profile": {
                              "userProfileId": "08e04cc8-21e3-4841-b9cb-dd3fdd1c9baf",
                              "uuId": "gC68Zgn6xH",
                              "contact": {
                                "personName": {
                                  "firstName": "Nidhi",
                                  "lastName": "Singh1"
                                },
                                "company": {
                                  "name": "Infogain"
                                },
                                "emailDetail": {
                                  "emailAddress": "Abhyuday.Gupta@infogain.com"
                                },
                                "phoneNumberDetails": [
                                  {
                                    "phoneNumber": {
                                      "number": "9013456789"
                                    }
                                  },
                                  {
                                    "phoneNumber": {}
                                  },
                                  {
                                    "phoneNumber": {}
                                  }
                                ],
                                "address": {
                                  "streetLines": [
                                    "32 meadow crest dr",
                                    ""
                                  ],
                                  "city": "Plano",
                                  "stateOrProvinceCode": "TX",
                                  "postalCode": "75024",
                                  "countryCode": "US"
                                }
                              },
                              "delivery": {
                                "preferredDeliveryMethod": "PICKUP",
                                "preferredStore": "4027"
                              },
                              "creditCards": [
                                        {
                                          "profileCreditCardId": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
                                          "cardHolderName": "Ravi Kant Kumar",
                                          "maskedCreditCardNumber": "4111111111111111",
                                          "creditCardLabel": "VISA",
                                          "creditCardType": "VISA",
                                          "expirationMonth": "10",
                                          "expirationYear": "2029",
                                          "billingAddress": {
                                            "company": "Infogain",
                                            "streetLines": [
                                              "6146 Honey Bluff Parkway Calder"
                                            ],
                                            "city": "Plano",
                                            "stateOrProvinceCode": "Texas",
                                            "postalCode": "75024",
                                            "countryCode": "United States",
                                            "addressClassification": "HOME"
                                          },
                                          "primary": true
                                        }
                                      ],
                                      "accounts": [
                                        {
                                          "profileAccountId": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
                                          "accountNumber": "stringstr",
                                          "maskedAccountNumber": "1234",
                                          "accountLabel": "John123",
                                          "accountType": "Shipping",
                                          "billingReference": "string",
                                          "primary": true
                                        },
                                        {
                                          "profileAccountId": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
                                          "accountNumber": "653243308",
                                          "maskedAccountNumber": "*3308",
                                          "accountLabel": "Prabhakar",
                                          "accountType": "printing",
                                          "billingReference": "string",
                                          "primary": true
                                        }
                                      ],
                              "emailSubscription": false,
                              "marketingEmails": false
                            }
                          }
                        }';
        $this->expressCheckoutViewModel
        ->expects($this->once())
        ->method('getCustomerProfileSession')
        ->willReturn($returnValue);

        $this->expressCheckoutViewModel
        ->expects($this->once())
        ->method('getCustomerProfileSessionWithExpiryToken')
        ->willReturn($returnValue);
        $this->assertEquals($returnValue, $this->customerDetails->getCustomerProfileSession());
    }
}
