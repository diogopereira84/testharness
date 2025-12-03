<?php

namespace Fedex\CartGraphQl\Test\Unit\Model\Resolver\UpdateOrderDelivery\Data;

use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\Data\ContactData;
use Magento\Directory\Model\Region;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Fedex\CartGraphQl\Model\Checkout\Cart;
use Magento\Quote\Model\Quote;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CartGraphQl\Helper\LoggerHelper;

class ContactDataTest extends TestCase
{
    private $regionMock;
    private $cartIntegrationRepositoryMock;
    private $dateTimeMock;
    private $cartRepositoryMock;
    private $instoreConfigMock;
    private $jsonSerializerMock;
    private $cartModelMock;
    private $toggleConfigMock;
    private $contactData;
    private $loggerMock;

    protected function setUp(): void
    {
        $this->regionMock = $this->createMock(Region::class);
        $this->cartIntegrationRepositoryMock = $this->createMock(CartIntegrationRepositoryInterface::class);
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->cartRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->instoreConfigMock = $this->createMock(InstoreConfig::class);
        $this->jsonSerializerMock = $this->createMock(JsonSerializer::class);
        $this->cartModelMock = $this->createMock(Cart::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->loggerMock = $this->createMock(LoggerHelper::class);

        $this->contactData = new ContactData(
            $this->loggerMock,
            $this->regionMock,
            $this->cartIntegrationRepositoryMock,
            $this->dateTimeMock,
            $this->cartRepositoryMock,
            $this->instoreConfigMock,
            $this->jsonSerializerMock,
            $this->cartModelMock,
            $this->toggleConfigMock
        );
    }

    public function testGetDataKeyReturnsAlternateContact()
    {
        $this->assertEquals('alternate_contact', $this->contactData->getDataKey());
    }

    public function testProceedWithAlternateContactSetsDataInAddresses()
    {
        $quoteMock = $this->createMock(Quote::class);
        $integrationMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getDeliveryData', 'getPickupLocationId'])
            ->getMock();

        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($integrationMock);

        // Add all required methods for address mock
        $addressMethods = [
            'getId', 'save',
            'setFirstName', 'setLastname', 'setEmail', 'setTelephone', 'setExtNo', 'setContactNumber'
        ];
        $shippingAddressMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods($addressMethods)
            ->getMock();
        $billingAddressMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods($addressMethods)
            ->getMock();

        $quoteMock->method('getShippingAddress')->willReturn($shippingAddressMock);
        $quoteMock->method('getBillingAddress')->willReturn($billingAddressMock);

        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($integrationMock);

        $integrationMock->method('getDeliveryData')->willReturn('{}');
        $integrationMock->method('getPickupLocationId')->willReturn('pickup_id');

        $shippingAddressMock->method('getId')->willReturn(1);
        $billingAddressMock->method('getId')->willReturn(2);

        $this->jsonSerializerMock->method('unserialize')->willReturn([]);

        $alternateContact = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@example.com',
            'telephone' => '1234567890',
            'ext' => '101'
        ];

        // Toggle is enabled
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);

        // Expect all setters and save to be called for both addresses
        foreach ([$shippingAddressMock, $billingAddressMock] as $addressMock) {
            $addressMock->expects($this->once())->method('setFirstName')->with('John');
            $addressMock->expects($this->once())->method('setLastname')->with('Doe');
            $addressMock->expects($this->once())->method('setEmail')->with('john.doe@example.com');
            $addressMock->expects($this->once())->method('setTelephone')->with('1234567890');
            $addressMock->expects($this->once())->method('setExtNo')->with('101');
            $addressMock->expects($this->once())->method('setContactNumber')->with('1234567890');
            $addressMock->expects($this->once())->method('save');
        }

        // Expect setData('is_alternate', 1) to be called once
        $quoteMock->expects($this->once())->method('setData')->with('is_alternate', 1);

        $this->contactData->proceed($quoteMock, ['alternate_contact' => $alternateContact]);
    }

    public function testProceedWithNoAlternateContactDoesNothing()
    {
        $quoteMock = $this->createMock(Quote::class);
        // Toggle is enabled but no alternate_contact provided, nothing should happen
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->contactData->proceed($quoteMock, []);
        $this->assertTrue(true); // No exception means pass
    }

    public function testProceedWithToggleDisabledDoesNothing()
    {
        $quoteMock = $this->createMock(Quote::class);
        $alternateContact = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@example.com',
            'telephone' => '1234567890',
            'ext' => '101'
        ];
        // Toggle is disabled
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(false);
        $this->contactData->proceed($quoteMock, ['alternate_contact' => $alternateContact]);
        $this->assertTrue(true); // No exception means pass
    }
}

