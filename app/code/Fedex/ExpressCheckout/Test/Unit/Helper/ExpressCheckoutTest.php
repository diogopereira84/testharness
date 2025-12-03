<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\ExpressCheckout\Test\Unit\Helper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\App\Helper\Context;
use Psr\Log\LoggerInterface;
use Magento\Directory\Model\Region;
use Magento\Quote\Model\Quote\PaymentFactory;
use Fedex\ExpressCheckout\Helper\ExpressCheckout;
use Magento\Quote\Model\Quote\Payment;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Prepare test objects.
 */
class ExpressCheckoutTest extends TestCase
{
    protected $quoteAddressMock;
    protected $expressCheckoutMock;
    /**
     * Profile credit card
     */
    public const CREDIT_CARD = [
        'billingAddress' =>
            [
                "stateOrProvinceCode" => 'TX',
                "countryCode" => 50,
                "streetLines" => ['plano', 'city'],
                "postalCode" => '75024',
                "city" => 'planoss'
            ]
        ];
    
    /**
     * Profile address
     */
    public const PROFILE_ADDRESS = [
            'firstName' => 'Bhairav',
            'lastName' => 'Singh',
            'email' => 'bhairav.singh@infogain.com',
            'phoneNumber' => '2222222222'
    ];

    /**
     * @var ObjectManagerHelper|MockObject
     */
    private $objectManagerHelper;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Region|MockObject
     */
    private $regionMock;

    /**
     * @var PaymentFactory|MockObject
     */
    private $paymentFactoryMock;

    /**
     * @var Payment|MockObject
     */
    private $paymentMock;

    /**
     * @var Quote|MockObject
     */
    private $quoteMock;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->regionMock = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadByCode', 'getId'])
            ->getMock();
        $this->paymentFactoryMock = $this->getMockBuilder(PaymentFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->setMethods(['setMethod'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods([
              'getShippingAddress',
              'getBillingAddress',
              'setPayment',
              'getPayment',
              'importData',
              'setData'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteAddressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->expressCheckoutMock = $this->objectManagerHelper->getObject(
            ExpressCheckout::class,
            [
                'context' => $this->contextMock,
                'logger' => $this->loggerMock,
                'region' => $this->regionMock,
                'paymentFactory' => $this->paymentFactoryMock
            ]
        );
    }

    /**
     * @test setPaymentInformation
     *
     * @return void
     */
    public function testSetPaymentInformation()
    {
        $paymentMethod = 'fedexaccount';
        $importData = ['method' => $paymentMethod];
        $creditCard = self::CREDIT_CARD;
        $profileAddress = self::PROFILE_ADDRESS;

        $billingData = [
            'addressInformation' => [
                'billing_address' => [
                    'region' => $creditCard["billingAddress"]["stateOrProvinceCode"],
                    'region_id' => 66,
                    'region_code' => $creditCard["billingAddress"]["stateOrProvinceCode"],
                    'country_id' => $creditCard["billingAddress"]["countryCode"],
                    'street' => [
                        0 => $creditCard["billingAddress"]["streetLines"][0],
                    ],
                    'postcode' => $creditCard["billingAddress"]["postalCode"],
                    'city' => $creditCard["billingAddress"]["city"],
                    'firstname' => $profileAddress["firstName"],
                    'lastname' => $profileAddress["lastName"],
                    'email' => $profileAddress["email"],
                    'telephone' => $profileAddress["phoneNumber"],
                ]
            ]
        ];

        $this->paymentFactoryMock->expects($this->any())
            ->method('create')
            ->willreturn($this->paymentMock);
        $this->paymentMock->expects($this->any())
            ->method('setMethod')
            ->with($paymentMethod)
            ->willReturnSelf();
        $this->quoteMock->expects($this->any())
            ->method('setPayment')
            ->with($this->paymentMock)
            ->willReturnSelf();
        $this->quoteMock->expects($this->any())
            ->method('getPayment')
            ->willReturnSelf();
        $this->quoteMock->expects($this->any())
            ->method('importData')
            ->with($importData)
            ->willReturnSelf();
        $this->regionMock->expects($this->any())
            ->method('loadByCode')
            ->with('TX', 50)
            ->willReturnSelf();
        $this->regionMock->expects($this->any())
            ->method('getId')
            ->willReturn(66);
        $expressCheckoutMock = $this->getMockBuilder(ExpressCheckout::class)
            ->disableOriginalConstructor()
            ->setMethods(['preparePaymentBillingAddress', 'getRegionId'])
            ->getMock();
        $expressCheckoutMock->expects($this->any())
            ->method('preparePaymentBillingAddress')
            ->with($creditCard, $profileAddress)
            ->willReturn($billingData);
        $expressCheckoutMock->expects($this->any())
            ->method('getRegionId')
            ->with($creditCard["billingAddress"])
            ->willReturn(66);
        $this->quoteMock->expects(static::atLeastOnce())
            ->method('getBillingAddress')
            ->willReturn($this->quoteAddressMock);

        $this->assertSame(
            null,
            $this->expressCheckoutMock->setPaymentInformation(
                json_encode($creditCard),
                $paymentMethod,
                json_encode($profileAddress),
                $this->quoteMock
            )
        );
    }

    /**
     * @test prepareShippingData
     *
     * @return void
     */
    public function testPrepareShippingData()
    {
        $creditCard = self::CREDIT_CARD;
        $profileAddress = self::PROFILE_ADDRESS;
        
        $addressData = [
            'addressInformation' => [
                'shipping_address' => [
                    'region' => $creditCard["billingAddress"]["stateOrProvinceCode"],
                    'region_id' => 66,
                    'region_code' => $creditCard["billingAddress"]["stateOrProvinceCode"],
                    'country_id' => $creditCard["billingAddress"]["countryCode"],
                    'street' => [
                        0 => $creditCard["billingAddress"]["streetLines"][0],
                    ],
                    'postcode' => $creditCard["billingAddress"]["postalCode"],
                    'city' => $creditCard["billingAddress"]["city"],
                    'firstname' => $profileAddress["firstName"],
                    'lastname' => $profileAddress["lastName"],
                    'email' => $profileAddress["email"],
                    'telephone' => $profileAddress["phoneNumber"],
                ],
                'billing_address' => [
                    'region' => $creditCard["billingAddress"]["stateOrProvinceCode"],
                    'region_id' => 66,
                    'region_code' => $creditCard["billingAddress"]["stateOrProvinceCode"],
                    'country_id' => $creditCard["billingAddress"]["countryCode"],
                    'street' => [
                        0 => $creditCard["billingAddress"]["streetLines"][0],
                    ],
                    'postcode' => $creditCard["billingAddress"]["postalCode"],
                    'city' => $creditCard["billingAddress"]["city"],
                    'firstname' => $profileAddress["firstName"],
                    'lastname' => $profileAddress["lastName"],
                    'email' => $profileAddress["email"],
                    'telephone' => $profileAddress["phoneNumber"],
                ],
                'shipping_carrier_code' => "fedexshipping",
                'shipping_method_code' => "PICKUP",
                'carrier_title' => "Fedex Store Pickup",
                'method_title' => 3202,
                'amount' => 0,
                'price_excl_tax' => 0,
                'price_incl_tax' => 0,
            ]
        ];

        $this->regionMock->expects($this->any())
            ->method('loadByCode')
            ->with('TX', 50)
            ->willReturnSelf();

        $this->regionMock->expects($this->any())
            ->method('getId')
            ->willReturn(66);

        $this->assertSame(
            $addressData,
            $this->expressCheckoutMock->prepareShippingData(
                $creditCard["billingAddress"],
                3202,
                $profileAddress
            )
        );
    }

    /**
     * @test SgetPaymentInformation
     *
     * @return void
     */
    public function testPreparePaymentBillingAddress()
    {
        $creditCard = self::CREDIT_CARD;
        $profileAddress = self::PROFILE_ADDRESS;

        $billingData = [
            'addressInformation' => [
                'billing_address' => [
                    'region' => $creditCard["billingAddress"]["stateOrProvinceCode"],
                    'region_id' => 66,
                    'region_code' => $creditCard["billingAddress"]["stateOrProvinceCode"],
                    'country_id' => $creditCard["billingAddress"]["countryCode"],
                    'street' => [
                        0 => $creditCard["billingAddress"]["streetLines"][0],
                    ],
                    'postcode' => $creditCard["billingAddress"]["postalCode"],
                    'city' => $creditCard["billingAddress"]["city"],
                    'firstname' => $profileAddress["firstName"],
                    'lastname' => $profileAddress["lastName"],
                    'email' => $profileAddress["email"],
                    'telephone' => $profileAddress["phoneNumber"],
                ]
            ]
        ];

        $this->regionMock->expects($this->any())
            ->method('loadByCode')
            ->with('TX', 50)
            ->willReturnSelf();
        $this->regionMock->expects($this->any())
            ->method('getId')
            ->willReturn(66);

        $this->assertSame(
            $billingData,
            $this->expressCheckoutMock->preparePaymentBillingAddress(
                json_encode($creditCard),
                $profileAddress
            )
        );
    }

    /**
     * @test setCustomerInformation
     *
     * @return void
     */
    public function testSetCustomerInformation()
    {
        $profileAddress = self::PROFILE_ADDRESS;

        $this->quoteMock->expects($this->any())
            ->method("setData")
            ->willReturn($this->quoteMock);

        $this->assertSame(null, $this->expressCheckoutMock->setCustomerInformation($profileAddress, $this->quoteMock));
    }

    /**
     * @test setShippingBillingAddress
     *
     * @return void
     */
    public function testSetShippingBillingAddress()
    {
        $creditCard = self::CREDIT_CARD;

        $profileAddress = self::PROFILE_ADDRESS;

        $customerAddressData = [
            'addressInformation' => [
                'shipping_address' => [
                    'region' => $creditCard["billingAddress"]["stateOrProvinceCode"],
                    'region_id' => 66,
                    'region_code' => $creditCard["billingAddress"]["stateOrProvinceCode"],
                    'country_id' => $creditCard["billingAddress"]["countryCode"],
                    'street' => [
                        0 => $creditCard["billingAddress"]["streetLines"][0],
                    ],
                    'postcode' => $creditCard["billingAddress"]["postalCode"],
                    'city' => $creditCard["billingAddress"]["city"],
                    'firstname' => $profileAddress["firstName"],
                    'lastname' => $profileAddress["lastName"],
                    'email' => $profileAddress["email"],
                    'telephone' => $profileAddress["phoneNumber"],
                ],
                'billing_address' => [
                    'region' => $creditCard["billingAddress"]["stateOrProvinceCode"],
                    'region_id' => 66,
                    'region_code' => $creditCard["billingAddress"]["stateOrProvinceCode"],
                    'country_id' => $creditCard["billingAddress"]["countryCode"],
                    'street' => [
                        0 => $creditCard["billingAddress"]["streetLines"][0],
                    ],
                    'postcode' => $creditCard["billingAddress"]["postalCode"],
                    'city' => $creditCard["billingAddress"]["city"],
                    'firstname' => $profileAddress["firstName"],
                    'lastname' => $profileAddress["lastName"],
                    'email' => $profileAddress["email"],
                    'telephone' => $profileAddress["phoneNumber"],
                ],
                'shipping_carrier_code' => "fedexshipping",
                'shipping_method_code' => "PICKUP",
                'carrier_title' => "Fedex Store Pickup",
                'method_title' => 3202,
                'amount' => 0,
                'price_excl_tax' => 0,
                'price_incl_tax' => 0,
            ]
        ];
        
        $this->quoteMock->expects(static::atLeastOnce())
            ->method('getBillingAddress')
            ->willReturn($this->quoteAddressMock);
        $this->quoteMock->expects(static::atLeastOnce())
            ->method('getShippingAddress')
            ->willReturn($this->quoteAddressMock);
        $this->quoteAddressMock->expects($this->any())
            ->method('addData')
            ->with($customerAddressData['addressInformation']['shipping_address'])
            ->willReturnSelf();
        $this->quoteAddressMock->expects($this->any())
            ->method('addData')
            ->with($customerAddressData['addressInformation']['billing_address'])
            ->willReturnSelf();

        $this->assertSame(
            null,
            $this->expressCheckoutMock->setShippingBillingAddress(
                $this->quoteMock,
                $customerAddressData
            )
        );
    }
}
