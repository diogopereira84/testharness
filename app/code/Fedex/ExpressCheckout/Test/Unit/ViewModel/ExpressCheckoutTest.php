<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpressCheckout\Test\Unit\ViewModel;

use Fedex\ExpressCheckout\ViewModel\ExpressCheckout;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Customer\Model\Session;
use Fedex\ShipTo\Helper\Data as ShipToHelper;
use Psr\Log\LoggerInterface;

/**
 * Prepare test objects.
 */
class ExpressCheckoutTest extends TestCase
{
  /**
   * @var Session
   */
  protected $customerSession;
  /**
   * @var ExpressCheckout
   */
  protected $expressCheckoutMock;
  /**
   * @var EnhancedProfile
   */
  protected $enhancedProfileMock;

  /**
   * @var SsoConfiguration
   */
  protected $ssoConfigurationMock;

  /**
   * @var ToggleConfig
   */
  protected $toggleConfigMock;

  /**
   * @var ShipToHelper
   */
  protected $shipToHelperMock;

  /**
   * @var LoggerInterface
   */
  protected $loggerMock;

  /**
   * Prepare test objects.
   */
  protected function setUp(): void
  {
    $this->enhancedProfileMock = $this->getMockBuilder(EnhancedProfile::class)
      ->disableOriginalConstructor()
      ->setMethods(['getLoggedInProfileInfo', 'getTokenIsExpired', 'getAccountSummary'])
      ->getMock();

    $this->ssoConfigurationMock = $this->getMockBuilder(SsoConfiguration::class)
      ->disableOriginalConstructor()
      ->setMethods([
        'isRetail',
        'isFclCustomer',
        'getIsRequestFromSdeStoreFclLogin', // Add this method
        'isSelfRegCustomerWithFclEnabled'  // Add this method
      ])
      ->getMock();


    $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
      ->disableOriginalConstructor()
      ->setMethods(['getToggleConfigValue'])
      ->getMock();

    $this->customerSession = $this->getMockBuilder(Session::class)
      ->disableOriginalConstructor()
      ->setMethods(['getProfileSession'])
      ->getMock();

    $this->shipToHelperMock = $this->getMockBuilder(ShipToHelper::class)
      ->disableOriginalConstructor()
      ->setMethods(['getAddressByLocationId'])
      ->getMock();

    $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
      ->disableOriginalConstructor()
      ->setMethods(['error'])
      ->getMockForAbstractClass();

    $objectManagerHelper = new ObjectManager($this);
    $this->expressCheckoutMock = $objectManagerHelper->getObject(
      ExpressCheckout::class,
      [
        'enhancedProfile' => $this->enhancedProfileMock,
        'ssoConfiguration' => $this->ssoConfigurationMock,
        'toggleConfig' => $this->toggleConfigMock,
        'customerSession' => $this->customerSession,
        'shipToHelper' => $this->shipToHelperMock,
        'logger' => $this->loggerMock
      ]
    );
  }

  /**
   * @test getCustomerProfileSession
   *
   * @return void
   */
  public function testGetCustomerProfileSession()
  {
    $expectedResult = [];
    $this->enhancedProfileMock
      ->expects($this->once())
      ->method('getLoggedInProfileInfo')
      ->willReturn([]);

    $this->assertEquals($expectedResult, $this->expressCheckoutMock->getCustomerProfileSession());
  }

  /**
   * @test getIsRetail
   *
   * @return void
   */
  public function testGetIsRetail()
  {
    $expectedResult = true;
    $this->ssoConfigurationMock
      ->expects($this->once())
      ->method('isRetail')
      ->willReturn(true);

    $this->assertEquals($expectedResult, $this->expressCheckoutMock->getIsRetail());
  }

  /**
   * @test getIsFclCustomer
   *
   * @return void
   */
  public function testGetIsFclCustomer()
  {
    $expectedResult = true;
    $this->ssoConfigurationMock
      ->expects($this->once())
      ->method('isFclCustomer')
      ->willReturn(true);

    $this->assertEquals($expectedResult, $this->expressCheckoutMock->getIsFclCustomer());
  }

  /**
   * Get customer profile session data
   *
   * @return string
   */
  public function getCustomerProfileSessionData()
  {
    return '{
          "output": {
            "profile": {
              "creditCards": {
                "0": {
                  "profileCreditCardId": "80addce1-1d40-454e-ac98-8ec36196777a",
                  "creditCardLabel": "VISA_11111",
                  "creditCardType": "VISA",
                  "maskedCreditCardNumber": "11111",
                  "cardHolderName": "ravi",
                  "expirationMonth": "10",
                  "tokenExpirationDate": "2026-01-17T00:00:00Z",
                  "expirationYear": "2029",
                  "billingAddress": {
                    "company": {},
                    "streetLines": {
                      "0": "9400 WADE BLVD,1539"
                    },
                    "city": "FRISCO",
                    "stateOrProvinceCode": "TX",
                    "postalCode": "75035",
                    "countryCode": "US"
                  },
                  "primary": "",
                  "tokenExpired": "false"
                },
                "1": {
                  "profileCreditCardId": "54e65a89-a344-4c09-a25e-9a53d3789130",
                  "creditCardLabel": "MASTERCARD_54444",
                  "creditCardType": "MASTERCARD",
                  "maskedCreditCardNumber": "54444",
                  "cardHolderName": "5555555555554444",
                  "expirationMonth": "03",
                  "tokenExpirationDate": "2026-02-18T00:00:00Z",
                  "expirationYear": "2025",
                  "billingAddress": {
                    "company": {
                      "name": "Temple"
                    },
                    "streetLines": {
                      "0": "7900 Legacy"
                    },
                    "city": "Plano",
                    "stateOrProvinceCode": "TX",
                    "postalCode": "75024",
                    "countryCode": "US"
                  },
                  "primary": "",
                  "tokenExpired": "false"
                },
                "2": {
                  "profileCreditCardId": "036291e6-44fd-4551-b8ed-c7376759ada6",
                  "creditCardLabel": "DISCOVER_39424",
                  "creditCardType": "DISCOVER",
                  "maskedCreditCardNumber": "39424",
                  "cardHolderName": "6011000990139424",
                  "expirationMonth": "01",
                  "tokenExpirationDate": "2026-07-26T00:00:00Z",
                  "expirationYear": "2023",
                  "billingAddress": {
                    "company": {
                      "name": "Infogain"
                    },
                    "streetLines": {
                      "0": "234"
                    },
                    "city": "Plantation",
                    "stateOrProvinceCode": "FL",
                    "postalCode": "33324",
                    "countryCode": "US"
                  },
                  "primary": "",
                  "tokenExpired": "false"
                },
                "3": {
                  "profileCreditCardId": "aed40e56-9883-44ea-81c5-f9a1e45719c6",
                  "creditCardLabel": "AMEX_10005",
                  "creditCardType": "AMEX",
                  "maskedCreditCardNumber": "10005",
                  "cardHolderName": "378282246310005",
                  "expirationMonth": "02",
                  "tokenExpirationDate": "2026-07-26T00:00:00Z",
                  "expirationYear": "2025",
                  "billingAddress": {
                    "company": {
                      "name": "Infogain"
                    },
                    "streetLines": {
                      "0": "234"
                    },
                    "city": "Plantation",
                    "stateOrProvinceCode": "FL",
                    "postalCode": "33324",
                    "countryCode": "US"
                  },
                  "primary": "1",
                  "tokenExpired": "false"
                },
                "4": {
                  "profileCreditCardId": "18e68f66-923f-48f6-a09d-29d32a88b3b8",
                  "creditCardLabel": "VISA_22222",
                  "creditCardType": "VISA",
                  "maskedCreditCardNumber": "22222",
                  "cardHolderName": "Ravi1",
                  "expirationMonth": "11",
                  "tokenExpirationDate": "2021-10-17T11:16:35Z",
                  "expirationYear": "2022",
                  "billingAddress": {
                    "company": {
                      "name": "Infogain"
                    },
                    "streetLines": {
                      "0": "234"
                    },
                    "city": "Plantation",
                    "stateOrProvinceCode": "FL",
                    "postalCode": "33324",
                    "countryCode": "US"
                  },
                  "primary": "",
                  "tokenExpired": "true"
                }
              },
              "delivery": {
                "preferredDeliveryMethod": "CREDIT_CARD",
                "preferredStore": "0443"
              }
            }
          }
        }';
  }

  /**
   * Test get customer profile session with expiry token as true
   *
   * @return void
   */
  public function testGetCustomerProfileSessionWithExpiryTokenWithData()
  {
    $returnValue = $this->getCustomerProfileSessionData();
    $profileSession = json_decode($returnValue);

    $filteredMockData = [];
    foreach ($profileSession->output->profile->creditCards as $card) {
      if ($card->tokenExpired === "true") {
        $filteredMockData[] = $card;
      }
    }
    if (!isset($profileSession->output->profile->delivery)) {
      $profileSession->output->profile->delivery = new \stdClass();
      $profileSession->output->profile->delivery->preferredStore = "0443";
    }

    $profileSession->output->profile->creditCards = $filteredMockData;


    $this->customerSession->expects($this->any())
      ->method('getProfileSession')
      ->willReturn($profileSession);

    $this->enhancedProfileMock->expects($this->any())
      ->method('getTokenIsExpired')
      ->willReturn(1);

    $this->toggleConfigMock->expects($this->any())
      ->method('getToggleConfigValue')
      ->willReturn(true);

    $addressData = $this->getAddressData();
    $this->shipToHelperMock->expects($this->once())
      ->method('getAddressByLocationId')
      ->with('0443')
      ->willReturn($addressData);

    $result = $this->expressCheckoutMock->getCustomerProfileSessionWithExpiryToken();
    if (!$result) {
      $this->assertEquals([], $result);
      return;
    }

    $this->assertIsObject($result);
    $this->assertObjectHasProperty('output', $result);
    $this->assertObjectHasProperty('profile', $result->output);
    $this->assertObjectHasProperty('creditCards', $result->output->profile);

    $creditCards = $result->output->profile->creditCards;

    $this->assertObjectHasProperty("tokenExpirationDate", $creditCards[0]);

    foreach ($creditCards as $index => $card) {
      $expectedCard = $filteredMockData[$index];
      $this->assertEquals($expectedCard->profileCreditCardId, $card->profileCreditCardId);
      $this->assertEquals($expectedCard->creditCardLabel, $card->creditCardLabel);
      $this->assertEquals($expectedCard->creditCardType, $card->creditCardType);
      $this->assertEquals($expectedCard->maskedCreditCardNumber, $card->maskedCreditCardNumber);
      $this->assertEquals($expectedCard->cardHolderName, $card->cardHolderName);
      $this->assertEquals($expectedCard->expirationMonth, $card->expirationMonth);
      $this->assertEquals($expectedCard->expirationYear, $card->expirationYear);
      $this->assertEquals($expectedCard->billingAddress->city, $card->billingAddress->city);
      $this->assertEquals($expectedCard->billingAddress->stateOrProvinceCode, $card->billingAddress->stateOrProvinceCode);
      $this->assertEquals($expectedCard->billingAddress->postalCode, $card->billingAddress->postalCode);
      $this->assertEquals($expectedCard->billingAddress->countryCode, $card->billingAddress->countryCode);
      $this->assertEquals($expectedCard->primary, $card->primary);
    }

    $this->assertObjectHasProperty('postalCode', $result->output->profile->delivery);
  }


  /**
   * Test gset customer profile session with expiry token as false
   *
   * @return void
   */
  public function testGetCustomerProfileSessionWithExpiryTokenWithDataFalse()
  {
    $returnValue = $this->getCustomerProfileSessionData();
    $profileSession = json_decode($returnValue);

    $filteredMockData = [];
    foreach ($profileSession->output->profile->creditCards as $card) {
      if ($card->tokenExpired === "false") {
        $filteredMockData[] = $card;
      }
    }

    $profileSession->output->profile->creditCards = $filteredMockData;

    if (!isset($profileSession->output->profile->delivery)) {
      $profileSession->output->profile->delivery = new \stdClass();
    }
    $profileSession->output->profile->delivery->preferredDeliveryMethod = "CREDIT_CARD";
    $profileSession->output->profile->delivery->preferredStore = "0443";

    $this->customerSession->expects($this->any())
      ->method('getProfileSession')
      ->willReturn($profileSession);

    $this->enhancedProfileMock->expects($this->any())
      ->method('getTokenIsExpired')
      ->willReturn(0);

    $this->toggleConfigMock->expects($this->any())
      ->method('getToggleConfigValue')
      ->willReturn(true);

    $this->shipToHelperMock->expects($this->once())
      ->method('getAddressByLocationId')
      ->with('0443')
      ->willReturn(['success' => 0]);

    $this->loggerMock->expects($this->once())
      ->method('error')
      ->with($this->stringContains('Error in API while getting address by location id : 0443'));

    $result = $this->expressCheckoutMock->getCustomerProfileSessionWithExpiryToken();
    if (!$result) {
      $this->assertEquals([], $result);
      return;
    }

    $this->assertIsObject($result);

    $this->assertObjectHasProperty('delivery', $result->output->profile);
    $this->assertEmpty($result->output->profile->delivery->postalCode);

    $creditCards = $result->output->profile->creditCards;

    foreach ($creditCards as $index => $card) {
      $expectedCard = $filteredMockData[$index];
      $this->assertEquals($expectedCard->profileCreditCardId, $card->profileCreditCardId);
      $this->assertEquals($expectedCard->creditCardLabel, $card->creditCardLabel);
      $this->assertEquals($expectedCard->creditCardType, $card->creditCardType);
      $this->assertEquals($expectedCard->maskedCreditCardNumber, $card->maskedCreditCardNumber);
      $this->assertEquals($expectedCard->cardHolderName, $card->cardHolderName);
      $this->assertEquals($expectedCard->expirationMonth, $card->expirationMonth);
      $this->assertEquals($expectedCard->expirationYear, $card->expirationYear);
      $this->assertEquals($expectedCard->billingAddress->city, $card->billingAddress->city);
      $this->assertEquals($expectedCard->billingAddress->stateOrProvinceCode, $card->billingAddress->stateOrProvinceCode);
      $this->assertEquals($expectedCard->billingAddress->postalCode, $card->billingAddress->postalCode);
      $this->assertEquals($expectedCard->billingAddress->countryCode, $card->billingAddress->countryCode);
      $this->assertEquals($expectedCard->primary, $card->primary);
    }
  }

  /**
   * Test getCustomerProfileSessionWithExpiryToken
   *
   * @return void
   */
  public function testGetCustomerProfileSessionWithExpiryTokenWithoutData()
  {
    $this->customerSession->expects($this->any())->method('getProfileSession')->willReturn(false);
    $this->assertEquals([], $this->expressCheckoutMock->getCustomerProfileSessionWithExpiryToken());
  }

  /**
   * Test Customer Profile Session With Active Expiry Account
   *
   * @return void
   */
  public function testGetCustomerProfileSessionWithActiveExpiryAccount()
  {
    $returnValue = '{
          "transactionId": "106f4966-f8aa-4fd2-a34a-a6a5b74e473f",
          "output": {
            "profile": {
              "accounts": [
                {
                  "accountNumber": "610977553",
                  "maskedAccountNumber": "*7553",
                  "accountLabel": "My Account-553",
                  "accountType": "SHIPPING",
                  "primary": true,
                  "accountValid": false
                }
              ]
            }
          }
        }';

    $session = json_decode($returnValue);

    $this->customerSession->expects($this->any())->method('getProfileSession')
      ->willReturn($session);

    $this->enhancedProfileMock->expects($this->any())
      ->method('getAccountSummary')
      ->with('610977553')
      ->willReturn(['account_status' => 'active']);

    $this->expressCheckoutMock->getCustomerProfileSessionWithExpiryAccount();

    // Verify that the accountValid flag is updated to true.
    $this->assertTrue(
      $session->output->profile->accounts[0]->accountValid
    );
  }

  /**
   * Test Customer Profile Session With In Active Expiry Account
   *
   * @return void
   */
  public function testGetCustomerProfileSessionWithInActiveExpiryAccount()
  {
    $returnValue = '{
          "transactionId": "106f4966-f8aa-4fd2-a34a-a6a5b74e473f",
          "output": {
            "profile": {
              "accounts": [
                {
                  "accountNumber": "610977553",
                  "maskedAccountNumber": "*7553",
                  "accountLabel": "My Account-553",
                  "accountType": "SHIPPING",
                  "primary": true,
                  "accountValid": false
                }
              ]
            }
          }
        }';
    $session = json_decode($returnValue);

    $this->customerSession->expects($this->any())
      ->method('getProfileSession')
      ->willReturn($session);

    $this->enhancedProfileMock->expects($this->any())
      ->method('getAccountSummary')
      ->with('610977553')
      ->willReturn(['account_status' => 'inactive']);

    $this->expressCheckoutMock->getCustomerProfileSessionWithExpiryAccount();

    $this->assertFalse(
      $session->output->profile->accounts[0]->accountValid
    );
  }

  /**
   * Get address data
   *
   * @return json
   */
  public function getAddressData()
  {
    return [
      'success' => 1,
      'address' => '{
          "Id": "0443",
          "address": {
            "address1": "335 Broadway",
            "address2": "",
            "city": "New York",
            "stateOrProvinceCode": "NY",
            "postalCode": "10013",
            "countryCode": "US",
            "addressType": ""
          },
          "name": "Manhattan NYC City Hall",
          "phone": "2124061220",
          "email": "usa0231@fedex.com",
          "locationType": "OFFICE_PRINT",
          "available": true,
          "availabilityReason": "AVAILABLE",
          "pickupEnabled": true,
          "geoCode": {
            "latitude": "40.716473",
            "longitude": "-74.004567"
          }
        }'
    ];
  }

  /**
   * @test getIsRequestFromSdeStoreFclLogin
   *
   * @return void
   */
  public function testGetIsRequestFromSdeStoreFclLogin()
  {
    $expectedResult = true;
    $this->ssoConfigurationMock
      ->expects($this->once())
      ->method('getIsRequestFromSdeStoreFclLogin')
      ->willReturn($expectedResult);

    $this->assertEquals($expectedResult, $this->expressCheckoutMock->getIsRequestFromSdeStoreFclLogin());
  }

  /**
   * @test isSelfRegCustomerWithFclEnabled
   *
   * @return void
   */
  public function testIsSelfRegCustomerWithFclEnabled()
  {
    $expectedResult = false;
    $this->ssoConfigurationMock
      ->expects($this->once())
      ->method('isSelfRegCustomerWithFclEnabled')
      ->willReturn($expectedResult);

    $this->assertEquals($expectedResult, $this->expressCheckoutMock->isSelfRegCustomerWithFclEnabled());
  }
}
