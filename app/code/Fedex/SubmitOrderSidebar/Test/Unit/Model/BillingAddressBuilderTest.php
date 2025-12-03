<?php
/**
 * @category Fedex
 * @package  Fedex_SubmitOrderSidebar
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model;

use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\SubmitOrderSidebar\Model\BillingAddressBuilder;

class BillingAddressBuilderTest extends TestCase
{
    /**
     * @var Address|MockObject
     */
    protected $addressMock;

    /**
     * @var Quote|MockObject
     */
    protected $cartMock;

    /**
     * @var QuoteRepository|MockObject
     */
    protected $cartRepositoryMock;

    /**
     * @var AddressInterfaceFactory|MockObject
     */
    protected $addressFactoryMock;

    /**
     * @var Region|MockObject
     */
    protected $regionMock;

    /**
     * @var RegionFactory|MockObject
     */
    protected $regionFactoryMock;

    /**
     * @var ObjectManager
     */
    protected ObjectManager $objectManager;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->addressMock = $this->createMock(Address::class);
        $this->cartMock = $this->createMock(Quote::class);
        $this->addressFactoryMock = $this->createPartialMock(
            \Magento\Quote\Api\Data\AddressInterfaceFactory::class,
            ['create']
        );
        $this->regionFactoryMock = $this->createPartialMock(
            RegionFactory::class,
            ['create']
        );
        $this->regionMock = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRegionId', 'loadByCode', 'getName'])->getMock();
    }

    public function testBuildWithValidValues(): void
    {
        $paymentData = (object) [
            'billingAddress' => (object) [
                'state' => 'TX',
                'company' => 'Testing Company',
                'address' => '7601 Preston Rd',
                'addressTwo' => '180',
                'city' => 'Plano',
                'zip' => '75024',
            ]
        ];
        $region = 'some name';
        $regionId = 'someID';
        $street = $paymentData->billingAddress->address . " " . $paymentData->billingAddress->addressTwo;
        $this->cartMock->expects($this->any())->method('getBillingAddress')->willReturn($this->addressMock);
        $this->regionMock->expects($this->once())->method('loadByCode')->willReturn($this->regionMock);
        $this->regionMock->expects($this->once())->method('getName')->willReturn($region);
        $this->regionMock->expects($this->once())->method('getRegionId')->willReturn($regionId);
        $this->regionFactoryMock->expects($this->once())->method('create')->willReturn($this->regionMock);
        $this->addressFactoryMock->expects($this->once())->method('create')
        ->willReturn($this->objectManager->getObject(Address::class));

        $builder = new BillingAddressBuilder($this->addressFactoryMock, $this->regionFactoryMock);
        $address = $builder->build($paymentData, $this->cartMock);

        $this->assertEquals($street, $address->getData('street'));
        $this->assertEquals($paymentData->billingAddress->company, $address->getCompany());
        $this->assertEquals($paymentData->billingAddress->city, $address->getCity());
        $this->assertEquals($paymentData->billingAddress->zip, $address->getPostcode());
        $this->assertEquals($region, $address->getData('region'));
        $this->assertEquals($regionId, $address->getData('region_id'));
        $this->assertInstanceOf(AddressInterface::class, $address);
    }

    public function testBuildWithInvalidValues()
    {
        $paymentData = (object) [
            'billingAddress' => (object) []
        ];
        $this->addressFactoryMock->expects($this->once())->method('create')
        ->willReturn($this->objectManager->getObject(Address::class));

        $builder = new BillingAddressBuilder($this->addressFactoryMock, $this->regionFactoryMock);
        $address = $builder->build($paymentData, $this->cartMock);

        $this->assertInstanceOf(AddressInterface::class, $address);
    }

    /**
     * Test for getCustomerDetails
     */
    public function testGetCustomerDetails()
    {
        $rateQuoteApiData = [
            'rateQuoteRequest' => [
                'sourceRetailLocationId' => null,
                'previousQuoteId' => null,
                'action' => 'SAVE_COMMIT',
                'retailPrintOrder' => [
                    'fedExAccountNumber' => "",
                    'origin' => [
                        'orderNumber' => "24823482348",
                        'orderClient' => 'MAGENTO',
                        'site' => "https://fedex.com",
                        'siteName' => null
                    ],
                    'orderContact' => [
                        'contact' => [
                            'contactId' => null,
                            'personName' => [
                                'firstName' => "Yogesh",
                                'lastName' => "Suryawanshi",
                            ],
                            'company' => [
                                'name' => 'FXO',
                            ],
                            'emailDetail' => [
                                'emailAddress' => "yogesh.suryawanshi@igglobal.com",
                            ],
                            'phoneNumberDetails' => [
                                0 => [
                                    'phoneNumber' => [
                                        'number' => "956965656565",
                                        'extension' => null,
                                    ],
                                    'usage' => 'PRIMARY',
                                ],
                            ],
                        ],
                    ],
                    'customerNotificationEnabled' => false,
                    'notificationRegistration' => [
                        'webhook' => [
                            'url' => "https://fedex.com",
                            'auth' => null,
                        ],
                    ],
                    'profileAccountId' => null,
                    'expirationDays' => '30',
                    'products' => "test",
                    'recipients' => [
                        0 => [
                            'reference' => "8234982348",
                            'contact' => [
                                'contactId' => null,
                                'personName' => [
                                    'firstName' => "Yogesh",
                                    'lastName' => "Suryawanshi",
                                ],
                                'company' => [
                                    'name' => 'FXO',
                                ],
                                'emailDetail' => [
                                    'emailAddress' => "yogesh.suryawanshi",
                                ],
                                'phoneNumberDetails' => [
                                    0 => [
                                        'phoneNumber' => [
                                            'number' => "963254254555",
                                            'extension' => null,
                                        ],
                                        'usage' => 'PRIMARY',
    
                                    ],
                                ],
                            ],
                            'shipmentDelivery' => [
                                'address' => [
                                    'streetLines' => "234",
                                    'city' => "Plano",
                                    'stateOrProvinceCode' => "Tx",
                                    'postalCode' => "75024",
                                    'countryCode' => 'US',
                                    'addressClassification' => "Home",
                                ],
                                'holdUntilDate' => null,
                                'serviceType' => "Shipment",
                                'fedExAccountNumber' => "12345",
                                'deliveryInstructions' => null,
                                'poNumber' => null,
                            ],
                            'productAssociations' => "5545",
                        ],
                    ],
                ],
                'coupons' => "MGT01",
                'teamMemberId' => null,
            ],
        ];

        $builder = new BillingAddressBuilder($this->addressFactoryMock, $this->regionFactoryMock);
        $this->assertNotNull($builder->getCustomerDetails($rateQuoteApiData));
    }

    /**
     * Test case for getUpdatedCreditCardDetail
     */
    public function testGetUpdatedCreditCardDetail()
    {
        $data = (object) [
            "output" => (object) [
                "creditCard" => (object) [
                    "creditCardToken" => "13442323",
                    "cardHolderName" => "Braj Mohan"
                ]
            ]
        ];

        $builder = new BillingAddressBuilder($this->addressFactoryMock, $this->regionFactoryMock);
        $this->assertNotNull($builder->getUpdatedCreditCardDetail($data, "13442323", "Braj Mohan"));
    }
}
