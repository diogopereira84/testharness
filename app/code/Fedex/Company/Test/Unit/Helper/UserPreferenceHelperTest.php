<?php

/**
 * Fedex
 * Copyright (C) 2021 Fedex <info@fedex.com>
 *
 * PHP version 7
 *
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Fedex <info@fedex.com>
 * @copyright 2006-2021 Fedex (http://www.fedex.com/)
 * @license   http://opensource.org/licenses/gpl-3.0.html
 * GNU General Public License,version 3 (GPL-3.0)
 * @link      http://fedex.com
 */

declare(strict_types=1);

namespace Fedex\Company\Test\Unit\Helper;

use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Fedex\Login\Model\UserPreferenceFactory;
use Fedex\Login\Model\UserPreference;
use Fedex\Login\Model\ResourceModel\UserPreference\Collection;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Company\Api\CompanyManagementInterface;
use Psr\Log\LoggerInterface;
use Fedex\Company\Helper\UserPreferenceHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Customer\Model\Customer;
use Magento\Company\Api\Data\CompanyInterface;




/**
 * Unit tests for Company Helper Data.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UserPreferenceHelperTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $userPreferenceFactoryMock;
    protected $userPreferenceMock;
    protected $customerSessionMock;
    /**
     * @var (\Magento\Company\Api\CompanyManagementInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyManagementInterfaceMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerInterfaceMock;
    protected $customer;
    /**
     * @var (\Magento\Company\Api\Data\CompanyInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyMock;
    protected $userPreferenceCollectionMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $helperMock;
    /**
     * Test setUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userPreferenceFactoryMock = $this->getMockBuilder(UserPreferenceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->userPreferenceMock = $this->getMockBuilder(UserPreference::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->setMethods(['isLoggedIn', 'getProfileSession', 'getCustomer', 'getCustomerId','setProfileSession'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyManagementInterfaceMock = $this->getMockBuilder(CompanyManagementInterface::class)
            ->setMethods(['getByCustomerId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerInterfaceMock = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['error'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();

        $this->companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->addMethods(['getCompanyUrlExtention'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->userPreferenceCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getId', 'getFirstItem','getData'])
            ->getMock();




        $this->objectManager = new ObjectManager($this);

        $this->helperMock = $this->objectManager->getObject(
            UserPreferenceHelper::class,
            [
                'context' => $this->contextMock,
                'userPreference' => $this->userPreferenceFactoryMock,
                'customerSession' => $this->customerSessionMock,
                'companyManagement' => $this->companyManagementInterfaceMock,
                'logger' => $this->loggerInterfaceMock,

            ]
        );
    }

        public function testUpdateProfileResponse() {


            $userProfileData = '{
                "transactionId": "5e18c896-986e-480e-ad3f-92e135908092",
                "output": {
                    "profile": {
                        "userProfileId": "9808beed-d759-4194-99e7-d63baba34756",
                        "uuId": "Rj4N3XlXtB",
                        "customerId": "Rj4N3XlXtB",
                        "contact": {
                            "personName": {
                                "firstName": "Jon",
                                "lastName": "Jamison"
                            },
                            "company": {
                                "name": "Jon Jamisons Test Company"
                            },
                            "emailDetail": {
                                "emailAddress": "jonathan.jamison@fedex.com"
                            },
                            "phoneNumberDetails": [
                                {
                                    "phoneNumber": {
                                        "number": "2148932910"
                                    },
                                    "primary": false
                                },
                                {
                                    "phoneNumber": {},
                                    "primary": false
                                },
                                {
                                    "phoneNumber": {},
                                    "primary": false
                                }
                            ],
                            "address": {
                                "streetLines": [
                                    "7900 Legacy Dr",
                                    ""
                                ],
                                "city": "Plano",
                                "stateOrProvinceCode": "TX",
                                "postalCode": "75024",
                                "countryCode": "US"
                            }
                        },
                        "canvaId": "054235dd-e202-4cf7-a9cb-e3c3896fd1b2",
                        "preferences": [
                            {
                                "name": "YOUR_REFERENCE",
                                "values": [
                                    {
                                        "name": "defaultValue",
                                        "value": "MK 1385"
                                    }
                                ]
                            },
                            {
                                "name": "DEPARTMENT_NUMBER",
                                "values": [
                                    {
                                        "name": "defaultValue",
                                        "value": "Marketing"
                                    }
                                ]
                            },
                            {
                                "name": "PURCHASE_ORDER_NUMBER",
                                "values": [
                                    {
                                        "name": "defaultValue",
                                        "value": "8675309"
                                    }
                                ]
                            },
                            {
                                "name": "INVOICE_NUMBER",
                                "values": [
                                    {
                                        "name": "defaultValue",
                                        "value": "4.69E+12"
                                    }
                                ]
                            }
                        ]
                    }
                }
            }';

            $profileInfo = json_decode($userProfileData);

            $this->customerSessionMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);

            $this->customerSessionMock->expects($this->any())
            ->method('getProfileSession')
            ->willReturn($profileInfo);

            $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customer);

            $this->customer->expects($this->any())
            ->method('getData')
            ->willReturn('test@gmail.com');

            $this->customerSessionMock->expects($this->any())
            ->method('getCustomerId')
            ->willReturn(12);

            $this->userPreferenceFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->userPreferenceMock);

            $this->userPreferenceMock
            ->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->userPreferenceCollectionMock);

             $this->userPreferenceCollectionMock
             ->expects($this->any())
             ->method('addFieldToFilter')
             ->willReturn($this->userPreferenceCollectionMock);

             $data = [];
             $this->userPreferenceCollectionMock
             ->expects($this->any())
             ->method('getData')
             ->willReturn($data);

            $this->helperMock->updateProfileResponse($profileInfo);

        }

        public function testUpdateProfileResponseInelse() {


            $userProfileData = '{
                "transactionId": "5e18c896-986e-480e-ad3f-92e135908092",
                "output": {
                    "profile": {
                        "userProfileId": "9808beed-d759-4194-99e7-d63baba34756",
                        "uuId": "Rj4N3XlXtB",
                        "customerId": "Rj4N3XlXtB",
                        "contact": {
                            "personName": {
                                "firstName": "Jon",
                                "lastName": "Jamison"
                            },
                            "company": {
                                "name": "Jon Jamisons Test Company"
                            },
                            "emailDetail": {
                                "emailAddress": "jonathan.jamison@fedex.com"
                            },
                            "phoneNumberDetails": [
                                {
                                    "phoneNumber": {
                                        "number": "2148932910"
                                    },
                                    "primary": false
                                },
                                {
                                    "phoneNumber": {},
                                    "primary": false
                                },
                                {
                                    "phoneNumber": {},
                                    "primary": false
                                }
                            ],
                            "address": {
                                "streetLines": [
                                    "7900 Legacy Dr",
                                    ""
                                ],
                                "city": "Plano",
                                "stateOrProvinceCode": "TX",
                                "postalCode": "75024",
                                "countryCode": "US"
                            }
                        },
                        "canvaId": "054235dd-e202-4cf7-a9cb-e3c3896fd1b2",
                        "preferences": [
                            {
                                "name": "YOUR_REFERENCE",
                                "values": [
                                    {
                                        "name": "defaultValue",
                                        "value": "MK 1385"
                                    }
                                ]
                            },
                            {
                                "name": "DEPARTMENT_NUMBER",
                                "values": [
                                    {
                                        "name": "defaultValue",
                                        "value": "Marketing"
                                    }
                                ]
                            },
                            {
                                "name": "PURCHASE_ORDER_NUMBER",
                                "values": [
                                    {
                                        "name": "defaultValue",
                                        "value": "8675309"
                                    }
                                ]
                            },
                            {
                                "name": "INVOICE_NUMBER",
                                "values": [
                                    {
                                        "name": "defaultValue",
                                        "value": "4.69E+12"
                                    }
                                ]
                            }
                        ]
                    }
                }
            }';

            $profileInfo = json_decode($userProfileData);

            $this->customerSessionMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);

            $this->customerSessionMock->expects($this->any())
            ->method('getProfileSession')
            ->willReturn($profileInfo);

            $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customer);

            $this->customer->expects($this->any())
            ->method('getData')
            ->willReturn('test@gmail.com');

            $this->customerSessionMock->expects($this->any())
            ->method('getCustomerId')
            ->willReturn(12);

            $this->userPreferenceFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->userPreferenceMock);

            $this->userPreferenceMock
            ->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->userPreferenceCollectionMock);

             $this->userPreferenceCollectionMock
             ->expects($this->any())
             ->method('addFieldToFilter')
             ->willReturn($this->userPreferenceCollectionMock);

             $data[0] = ['key'=>'billing_1','value'=>'123'];
             $data[1] = ['key'=>'INVOICE_NUMBER','value' =>'34577'];
             $this->userPreferenceCollectionMock
             ->expects($this->any())
             ->method('getData')
             ->willReturn($data);

             $this->customerSessionMock->expects($this->any())
            ->method('setProfileSession')
            ->willReturn(true);

            $this->helperMock->updateProfileResponse($profileInfo);

        }


        public function testUpdateProfileResponseUserProfileEmpty() {

            $userProfileData = null;

            $profileInfo = json_decode($this->getProfileInfo());

            $this->customerSessionMock
            ->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);

            $this->customerSessionMock
            ->expects($this->any())
            ->method('getProfileSession')
            ->willReturn($profileInfo);

            $this->customerSessionMock
            ->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customer);

            $this->customer
            ->expects($this->any())
            ->method('getData')
            ->willReturn('test@gmail.com');

            $this->customerSessionMock
            ->expects($this->any())
            ->method('getCustomerId')
            ->willReturn(12);

            $this->userPreferenceFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->userPreferenceMock);

            $this->userPreferenceMock
            ->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->userPreferenceCollectionMock);

             $this->userPreferenceCollectionMock
             ->expects($this->any())
             ->method('addFieldToFilter')
             ->willReturn($this->userPreferenceCollectionMock);

            $this->helperMock->updateProfileResponse($userProfileData);



        }

        /**
         * Get profile info data
         *
         * @return JSON
         */
        public function getProfileInfo()
        {
            return '{
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
        }


    public function testValidateSheetDataSuccess()
    {
        $datas = [
            ['email', 'billing_1',  'billing_2', 'billing_3', 'INVOICE_NUMBER'],
            ['test@.com', 'Product 2', 'Desc 2', '200', '46345267'],
        ];
        $extUrl = 'test';

        $this->userPreferenceFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->userPreferenceMock);

       $this->userPreferenceMock
            ->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->userPreferenceCollectionMock);

        $this->userPreferenceCollectionMock
             ->expects($this->any())
             ->method('addFieldToFilter')
             ->willReturn($this->userPreferenceCollectionMock);

       $this->userPreferenceCollectionMock->expects($this->any())
            ->method('getFirstItem')->willReturn($this->userPreferenceCollectionMock);

       $this->userPreferenceCollectionMock->expects($this->any())->method('getId')->willReturn(2);

        $result = $this->helperMock->validateSheetData($datas,$extUrl);

        $this->assertNotNull($result['message']);
    }


       public function testValidateSheetDataSuccessHeaderDouble()
    {
        $datas = [
            ['email', 'billing_1',  'billing_1', 'billing_3', 'INVOICE_NUMBER'],
            ['test@.com', 'Product 2', 'Desc 2', '200', '46345267'],
        ];
        $extUrl = 'test';

        $result = $this->helperMock->validateSheetData($datas,$extUrl);

        $this->assertNotNull($result['message']);
    }

    public function testValidateSheetDataSuccessEmpty()
    {
        $datas = [
            ['email', 'billing_1',  'billing_2', 'billing_3', 'INVOICE_NUMBER']
        ];
        $extUrl = 'test';

        $result = $this->helperMock->validateSheetData($datas,$extUrl);

        $this->assertNotNull($result['message']);
    }

    public function testValidateSheetDataSuccessEmailBlank()
    {
        $datas = [
            ['email', 'billing_1',  'billing_2', 'billing_3', 'INVOICE_NUMBER'],
            ['', 'Product 2', 'Desc 2', '200', '46345267'],
        ];
        $extUrl = 'test';

        $result = $this->helperMock->validateSheetData($datas,$extUrl);

        $this->assertNotNull($result['message']);
    }


    public function testValidateSheetDataSuccessInvoiceError()
    {
        $datas = [
            ['email', 'billing_1',  'billing_2', 'billing_3', 'invoice_number'],
            ['test@.com', 'Product 2', 'Desc 2', '200', 'E345267'],
        ];
        $extUrl = 'test';

        $this->userPreferenceFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->userPreferenceMock);

       $this->userPreferenceMock
            ->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->userPreferenceCollectionMock);

        $this->userPreferenceCollectionMock
             ->expects($this->any())
             ->method('addFieldToFilter')
             ->willReturn($this->userPreferenceCollectionMock);

       $this->userPreferenceCollectionMock->expects($this->any())
            ->method('getFirstItem')->willReturn($this->userPreferenceCollectionMock);

        $result = $this->helperMock->validateSheetData($datas,$extUrl);

        $this->assertNotNull($result['message']);
    }




}
