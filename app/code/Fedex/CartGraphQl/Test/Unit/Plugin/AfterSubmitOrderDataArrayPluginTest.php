<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Plugin\AfterSubmitOrderDataArrayPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderDataArray;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\CartGraphQl\Model\RateQuote\ShippingDelivery;
use Fedex\InStoreConfigurations\Model\Organization;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;

class AfterSubmitOrderDataArrayPluginTest extends TestCase
{
    /**
     * @var InstoreConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $instoreConfig;

    /**
     * @var ShippingDelivery|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shippingDelivery;

    /**
     * @var RequestQueryValidator|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestQueryValidator;

    /**
     * @var organization|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $organization;

    /**
     * @var AfterSubmitOrderDataArrayPlugin|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $afterSubmitOrderDataArrayPlugin;

    /**
     * @var Quote|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $quote;

    /**
     * @var Address|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shippingAddressMock;

    /**
     * @var SubmitOrderDataArray|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $submitOrderDataArray;

    /**
     * @return void
     */
    protected function setUp(): void
    {

        $this->instoreConfig = $this->getMockBuilder(InstoreConfig::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingDelivery = $this->getMockBuilder(ShippingDelivery::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestQueryValidator = $this->getMockBuilder(RequestQueryValidator::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->organization = $this->getMockBuilder(Organization::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getShippingAddress',
                'getBillingAddress',
                'getCustomerFirstname',
                'getCustomerLastname',
                'getCustomerEmail',
                'getCustomerTelephone',
                'getExtNo'])
            ->getMock();

        $this->shippingAddressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFirstname', 'getLastname', 'getCompany', 'getEmail', 'getTelephone', 'getExtNo'])
            ->getMock();

        $this->submitOrderDataArray = $this->getMockBuilder(SubmitOrderDataArray::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->afterSubmitOrderDataArrayPlugin = $objectManagerHelper->getObject(
            AfterSubmitOrderDataArrayPlugin::class,
            [
                'instoreConfig' => $this->instoreConfig,
                'shippingDelivery' => $this->shippingDelivery,
                'requestQueryValidator' => $this->requestQueryValidator,
                'organization' => $this->organization
            ]
        );
    }

    /**
     * Test case for the `afterGetReceipientInfoUpdated` method.
     *
     * This test verifies that the `afterGetReceipientInfoUpdated` method correctly updates the recipient information
     * in the result array based on the provided recipient data object and other parameters.
     *
     * @return void
     */
    public function testAfterGetReceipientInfoUpdated(): void
    {
        $result = [['shipmentDelivery' => 'test']];
        $isPickup = false;
        $isOrderApproval = false;

        $shipperRegionMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getData'])
            ->getMock();
            
        $shipperRegionMock->method('getData')
            ->with('code')
            ->willReturn('US');

        $recipientDataObjectMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods([
                'getQuote',
                'getShipperRegion',
                'getShipMethod',
                'getContactId'
            ])
            ->getMock();

        $recipientDataObjectMock->method('getShipperRegion')
            ->willReturn($shipperRegionMock);

        $recipientDataObjectMock->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->quote);

        $recipientDataObjectMock->expects($this->any())
            ->method('getContactId')
            ->willReturn('CONTACT123');

        $recipientDataObjectMock->expects($this->any())
            ->method('getShipperRegion')
            ->willReturn((object)['code' => 'US']);

        $recipientDataObjectMock->expects($this->any())
            ->method('getShipMethod')
            ->willReturn('local_method');

        $this->quote->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($this->shippingAddressMock);

        $this->shippingAddressMock->expects($this->any())
            ->method('getFirstname')
            ->willReturn('John');
        $this->shippingAddressMock->expects($this->any())
            ->method('getLastname')
            ->willReturn('Doe');
        $this->shippingAddressMock->expects($this->any())
            ->method('getCompany')
            ->willReturn('Fedex');
        $this->shippingAddressMock->expects($this->any())
            ->method('getEmail')
            ->willReturn('john.doe@example.com');
        $this->shippingAddressMock->expects($this->any())
            ->method('getTelephone')
            ->willReturn('1234567890');
        $this->shippingAddressMock->expects($this->any())
            ->method('getExtNo')
            ->willReturn('123');

        $this->instoreConfig->expects($this->any())
            ->method('isEnableServiceTypeForRAQ')
            ->willReturn(true);

        $this->requestQueryValidator->expects($this->any())
            ->method('isGraphQl')
            ->willReturn(true);

        $this->shippingDelivery->expects($this->any())
            ->method('validateIfLocalDelivery')
            ->willReturn(true);

        $this->shippingDelivery->expects($this->any())
            ->method('setLocalDelivery')
            ->willReturn(['type' => 'local']);

        $this->organization->expects($this->any())
            ->method('getOrganization')
            ->with('Fedex')
            ->willReturn('Fedex');

        $expectedContact = [
            'contactId' => 'CONTACT123',
            'personName' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'company' => [
                'name' => 'Fedex',
            ],
            'emailDetail' => [
                'emailAddress' => 'john.doe@example.com',
            ],
            'phoneNumberDetails' => [
                [
                    'phoneNumber' => [
                        'number' => '1234567890',
                        'extension' => '123',
                    ],
                    'usage' => 'PRIMARY',
                ],
            ],
        ];

        $result = $this->afterSubmitOrderDataArrayPlugin->afterGetReceipientInfoUpdated(
            $this->submitOrderDataArray,
            $result,
            $isPickup,
            $recipientDataObjectMock,
            $isOrderApproval
        );

        $this->assertEquals($expectedContact, $result[0]['contact']);
    }

    /**
     * Test case for the `afterGetReceipientInfoUpdated` method
     *
     * This test verifies the behavior of the `afterGetReceipientInfoUpdated` method when handling external delivery paths.
     * It ensures that the method correctly processes recipient information and returns the expected result.
     *
     * @return void
     */
    public function testAfterGetReceipientInfoUpdatedElse(): void
    {
        $result = [['shipmentDelivery' => 'test']];
        $isPickup = false;
        $isOrderApproval = false;

        // Mocks
        $shipperRegionMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getData'])
            ->getMock();
        $shipperRegionMock->method('getData')->with('code')->willReturn('US');

        $recipientDataObjectMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getQuote', 'getShipperRegion', 'getShipMethod', 'getContactId'])
            ->getMock();
        $recipientDataObjectMock->method('getQuote')->willReturn($this->quote);
        $recipientDataObjectMock->method('getContactId')->willReturn('CONTACT123');
        $recipientDataObjectMock->method('getShipperRegion')->willReturn($shipperRegionMock);
        $recipientDataObjectMock->method('getShipMethod')->willReturn('external_method');

        $this->quote->method('getShippingAddress')->willReturn($this->shippingAddressMock);

        $this->shippingAddressMock->method('getFirstname')->willReturn('John');
        $this->shippingAddressMock->method('getLastname')->willReturn('Doe');
        $this->shippingAddressMock->method('getCompany')->willReturn('Fedex');
        $this->shippingAddressMock->method('getEmail')->willReturn('john.doe@example.com');
        $this->shippingAddressMock->method('getTelephone')->willReturn('1234567890');
        $this->shippingAddressMock->method('getExtNo')->willReturn('123');

        $this->instoreConfig->method('isEnableServiceTypeForRAQ')->willReturn(true);
        $this->requestQueryValidator->method('isGraphQl')->willReturn(true);

        // EXTERNAL delivery path
        $this->shippingDelivery->method('validateIfLocalDelivery')->willReturn(false);
        $this->shippingDelivery->method('setExternalDelivery')->willReturn(['externalType' => 'external']);

        $this->organization->method('getOrganization')->with('Fedex')->willReturn('Fedex');

        $expectedContact = [
            'contactId' => 'CONTACT123',
            'personName' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'company' => [
                'name' => 'Fedex',
            ],
            'emailDetail' => [
                'emailAddress' => 'john.doe@example.com',
            ],
            'phoneNumberDetails' => [
                [
                    'phoneNumber' => [
                        'number' => '1234567890',
                        'extension' => '123',
                    ],
                    'usage' => 'PRIMARY',
                ],
            ],
        ];

        $result = $this->afterSubmitOrderDataArrayPlugin->afterGetReceipientInfoUpdated(
            $this->submitOrderDataArray,
            $result,
            $isPickup,
            $recipientDataObjectMock,
            $isOrderApproval
        );

        $this->assertEquals([
            'contact' => $expectedContact,
            ShippingDelivery::EXTERNAL_DELIVERY => ['externalType' => 'external']
        ], $result[0]);
    }

    /**
     * Test case for the afterPrepareShippingRateQuoteRequestData method in the AfterSubmitOrderDataArrayPlugin class.
     *
     * This test verifies that the afterPrepareShippingRateQuoteRequestData method correctly modifies the
     * shipping rate quote request data by adding customer and billing address details when certain conditions are met.
     *
     * @return void
     */
    public function testAfterPrepareShippingRateQuoteRequestData(): void
    {
        $initialResult = [
            'rateQuoteRequest' => [
                'retailPrintOrder' => []
            ]
        ];

        $billingAddressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCompany'])
            ->getMock();
        $billingAddressMock->method('getCompany')->willReturn('Fedex');

        $this->quote->method('getBillingAddress')->willReturn($billingAddressMock);
        $this->quote->method('getCustomerFirstname')->willReturn('John');
        $this->quote->method('getCustomerLastname')->willReturn('Doe');
        $this->quote->method('getCustomerEmail')->willReturn('john.doe@example.com');
        $this->quote->method('getCustomerTelephone')->willReturn('1234567890');
        $this->quote->method('getExtNo')->willReturn('123');

        $this->instoreConfig->method('isEnableServiceTypeForRAQ')->willReturn(true);
        $this->requestQueryValidator->method('isGraphQl')->willReturn(true);
        $this->organization->method('getOrganization')->with('Fedex')->willReturn('Fedex');

        $result = $this->afterSubmitOrderDataArrayPlugin->afterPrepareShippingRateQuoteRequestData(
            $this->submitOrderDataArray,
            $initialResult,
            'rateQuoteId123',
            'someAction',
            new \stdClass(),
            $this->quote,
            false
        );

        $expected = [
            'rateQuoteRequest' => [
                'retailPrintOrder' => [
                    'orderContact' => [
                        'contact' => [
                            'personName' => [
                                'firstName' => 'John',
                                'lastName' => 'Doe',
                            ],
                            'company' => [
                                'name' => 'Fedex',
                            ],
                            'emailDetail' => [
                                'emailAddress' => 'john.doe@example.com',
                            ],
                            'phoneNumberDetails' => [
                                [
                                    'phoneNumber' => [
                                        'number' => '1234567890',
                                        'extension' => '123',
                                    ],
                                    'usage' => 'PRIMARY',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $result);
    }

     /**
      * Test case for the afterPrepareShippingRateQuoteRequestData.
      *
      * @return void
      */
    public function testAfterGetReceipientInfo(): void
    {
        $result = [['shipmentDelivery' => 'test']];
        $isPickup = false;
        $isOrderApproval = false;

        $shipperRegionMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getData'])
            ->getMock();
        $shipperRegionMock->method('getData')->with('code')->willReturn('US');

        $recipientDataObjectMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getQuote', 'getShipperRegion', 'getShipMethod', 'getContactId'])
            ->getMock();
        $recipientDataObjectMock->method('getQuote')->willReturn($this->quote);
        $recipientDataObjectMock->method('getShipperRegion')->willReturn($shipperRegionMock);
        $recipientDataObjectMock->method('getShipMethod')->willReturn('some_method');
        $recipientDataObjectMock->method('getContactId')->willReturn('CONTACT123');

        // Quote & Address Mocks
        $this->quote->method('getShippingAddress')->willReturn($this->shippingAddressMock);

        $this->shippingAddressMock->method('getFirstname')->willReturn('John');
        $this->shippingAddressMock->method('getLastname')->willReturn('Doe');
        $this->shippingAddressMock->method('getCompany')->willReturn('Fedex');
        $this->shippingAddressMock->method('getEmail')->willReturn('john.doe@example.com');
        $this->shippingAddressMock->method('getTelephone')->willReturn('1234567890');
        $this->shippingAddressMock->method('getExtNo')->willReturn('123');
        $this->instoreConfig->method('isEnableServiceTypeForRAQ')->willReturn(true);
        $this->requestQueryValidator->method('isGraphQl')->willReturn(true);
        $this->shippingDelivery->method('validateIfLocalDelivery')->willReturn(true);
        $this->shippingDelivery->method('setLocalDelivery')->willReturn(['type' => 'local']);
        $this->organization->method('getOrganization')->with('Fedex')->willReturn('Fedex');

        // Expected Result
        $expectedContact = [
            'contactId' => 'CONTACT123',
            'personName' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'company' => [
                'name' => 'Fedex',
            ],
            'emailDetail' => [
                'emailAddress' => 'john.doe@example.com',
            ],
            'phoneNumberDetails' => [
                [
                    'phoneNumber' => [
                        'number' => '1234567890',
                        'extension' => '123',
                    ],
                    'usage' => 'PRIMARY',
                ],
            ],
        ];

        $expected = [
            [
                'contact' => $expectedContact,
                ShippingDelivery::LOCAL_DELIVERY => ['type' => 'local']
            ]
        ];

        $actual = $this->afterSubmitOrderDataArrayPlugin->afterGetReceipientInfo(
            $this->submitOrderDataArray,
            $result,
            $isPickup,
            $recipientDataObjectMock,
            $isOrderApproval
        );

        $this->assertEquals($expected, $actual);
    }
}
