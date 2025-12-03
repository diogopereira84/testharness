<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model;

use Fedex\MarketplaceCheckout\Model\FreightCheckoutPricing;
use Fedex\MarketplaceRates\Helper\Data;
use Magento\Directory\Model\ResourceModel\Region\Collection;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\Quote\Address;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class FreightCheckoutPricingTest extends TestCase
{
    /** @var Curl&MockObject */
    private $curlClientMock;

    /** @var Json&MockObject */
    private $jsonSerializerMock;

    /** @var LoggerInterface&MockObject */
    private $loggerMock;

    /** @var Data&MockObject */
    private $helperMock;

    /** @var CollectionFactory&MockObject */
    private $collectionFactoryMock;

    /** @var ToggleConfig&MockObject */
    private $toggleConfigMock;

    /** @var FreightCheckoutPricing */
    private $freightCheckoutPricing;

    /** @var Collection&MockObject */
    private $collectionMock;

    protected function setUp(): void
    {
        $this->curlClientMock = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonSerializerMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->helperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->freightCheckoutPricing = new FreightCheckoutPricing(
            $this->curlClientMock,
            $this->jsonSerializerMock,
            $this->loggerMock,
            $this->helperMock,
            $this->collectionFactoryMock,
            $this->toggleConfigMock
        );
    }

    /**
     * @dataProvider formatClassValueDataProvider
     */
    public function testFormatClassValue($input, $expected)
    {
        $method = new \ReflectionMethod(FreightCheckoutPricing::class, 'formatClassValue');
        $method->setAccessible(true);
        $actual = $method->invoke($this->freightCheckoutPricing, $input);
        $this->assertEquals($expected, $actual);
    }

    public function formatClassValueDataProvider(): array
    {
        return [
            'integer' => [100, 'CLASS_100'],
            'float_with_zero_decimal' => [100.0, 'CLASS_100'],
            'float_with_decimal' => [100.5, 'CLASS_100_5'],
            'string_integer' => ['100', 'CLASS_100'],
            'string_float' => ['100.5', 'CLASS_100_5'],
            'string_comma_float' => ['100,5', 'CLASS_100'],
            'zero' => [0, false],
            'negative' => [-100, false],
            'empty_string' => ['', false],
            'non_numeric_string' => ['abc', false],
        ];
    }

    public function testExecuteWithValidResponse()
    {
        $shopData = [
            'freight_state' => 'Tennessee',
            'freight_city' => 'Memphis',
            'freight_postcode' => '38116',
            'freight_account_number' => '123456789'
        ];

        $shippingAddress = $this->createMock(Address::class);
        $shippingAddress->method('getCity')->willReturn('Nashville');
        $shippingAddress->method('getRegionCode')->willReturn('TN');
        $shippingAddress->method('getCountryId')->willReturn('US');
        $shippingAddress->method('getPostcode')->willReturn('37203');

        $shipDate = '2023-01-01';
        $package = [
            'freightClass' => '100',
            'type' => 'box',
            'weight' => 50,
            'specialServices' => false,
            'shape' => [
                'length' => 10,
                'width' => 10,
                'depth' => 10,
                'volume' => 1000,
                'area' => 100
            ]
        ];

        $regionData = ['code' => 'TN'];
        $this->collectionMock->method('addRegionNameFilter')->willReturnSelf();
        $this->collectionMock->method('addCountryCodeFilter')->willReturnSelf();
        $this->collectionMock->method('getFirstItem')->willReturnSelf();
        $this->collectionMock->method('toArray')->willReturn($regionData);
        $this->collectionFactoryMock->method('create')->willReturn($this->collectionMock);

        $this->helperMock->method('getFreightShippingRatesUrl')
            ->willReturn('https://api.example.com/freight-rates');
        $this->helperMock->method('getFedexRatesToken')->willReturn('token123');

        $jsonRequest = '{"request":"data"}';
        $this->jsonSerializerMock->method('serialize')->willReturn($jsonRequest);

        $apiResponse = '{"output":{"rateReplyDetails":[{"rate":100}]}}';
        $this->curlClientMock->method('getBody')->willReturn($apiResponse);
        $this->curlClientMock->method('getStatus')->willReturn(200);

        $normalizedResponse = ['output' => ['rateReplyDetails' => [['rate' => 100]]]];
        $this->jsonSerializerMock->method('unserialize')->willReturn($normalizedResponse);

        $result = $this->freightCheckoutPricing->execute(
            $shopData,
            $shippingAddress,
            $shipDate,
            $package
        );

        $this->assertEquals([['rate' => 100]], $result);
    }

    public function testExecuteWithEmptyRegion()
    {
        $shopData = [
            'freight_state' => '',
            'freight_city' => 'Memphis',
            'freight_postcode' => '38116',
            'freight_account_number' => '123456789'
        ];

        $shippingAddress = $this->createMock(Address::class);
        $shipDate = '2023-01-01';
        $package = [
            'freightClass' => '100',
            'type' => 'box',
            'weight' => 50,
            'specialServices' => false,
            'shape' => [
                'length' => 10,
                'width' => 10,
                'depth' => 10,
                'volume' => 1000,
                'area' => 100
            ]
        ];

        $this->helperMock->method('getFreightShippingRatesUrl')
            ->willReturn('https://api.example.com/freight-rates');
        $this->helperMock->method('getFedexRatesToken')->willReturn('token123');
        $this->jsonSerializerMock->method('serialize')->willReturn('{}');

        $apiResponse = '{"output":{"rateReplyDetails":[{"rate":100}]}}';
        $this->curlClientMock->method('getBody')->willReturn($apiResponse);
        $this->curlClientMock->method('getStatus')->willReturn(200);
        $this->jsonSerializerMock->method('unserialize')
            ->willReturn(['output' => ['rateReplyDetails' => [['rate' => 100]]]]);

        $result = $this->freightCheckoutPricing->execute(
            $shopData,
            $shippingAddress,
            $shipDate,
            $package
        );

        $this->assertEquals([['rate' => 100]], $result);
    }

    public function testExecuteWithApiError()
    {
        $shopData = [
            'freight_state' => 'Tennessee',
            'freight_city' => 'Memphis',
            'freight_postcode' => '38116',
            'freight_account_number' => '123456789'
        ];

        $shippingAddress = $this->createMock(Address::class);
        $package = [
            'freightClass' => '100',
            'type' => 'box',
            'weight' => 50,
            'specialServices' => false,
            'shape' => [
                'length' => 10,
                'width' => 10,
                'depth' => 10,
                'volume' => 1000,
                'area' => 100
            ]
        ];

        $this->collectionMock->method('addRegionNameFilter')->willReturnSelf();
        $this->collectionMock->method('addCountryCodeFilter')->willReturnSelf();
        $this->collectionMock->method('getFirstItem')->willReturnSelf();
        $this->collectionMock->method('toArray')->willReturn(['code' => 'TN']);
        $this->collectionFactoryMock->method('create')->willReturn($this->collectionMock);

        $this->helperMock->method('getFreightShippingRatesUrl')
            ->willReturn('https://api.example.com/freight-rates');
        $this->helperMock->method('getFedexRatesToken')->willReturn('token123');
        $this->jsonSerializerMock->method('serialize')->willReturn('{}');

        $this->curlClientMock->method('getStatus')->willReturn(400);

        $result = $this->freightCheckoutPricing->execute(
            $shopData,
            $shippingAddress,
            '2023-01-01',
            $package
        );

        $this->assertEquals([], $result);
    }

    public function testExecuteWithInvalidFreightClass()
    {
        $shopData = [
            'freight_state' => 'Tennessee',
            'freight_city' => 'Memphis',
            'freight_postcode' => '38116',
            'freight_account_number' => '123456789'
        ];

        $shippingAddress = $this->createMock(Address::class);
        $package = [
            'freightClass' => 0,
            'type' => 'box',
            'weight' => 50,
            'specialServices' => false,
            'shape' => [
                'length' => 10,
                'width' => 10,
                'depth' => 10,
                'volume' => 1000,
                'area' => 100
            ]
        ];

        $this->collectionMock->method('addRegionNameFilter')->willReturnSelf();
        $this->collectionMock->method('addCountryCodeFilter')->willReturnSelf();
        $this->collectionMock->method('getFirstItem')->willReturnSelf();
        $this->collectionMock->method('toArray')->willReturn(['code' => 'TN']);
        $this->collectionFactoryMock->method('create')->willReturn($this->collectionMock);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->willReturnSelf();

        $result = $this->freightCheckoutPricing->execute(
            $shopData,
            $shippingAddress,
            '2023-01-01',
            $package
        );

        $this->assertEquals([], $result);
    }

    public function testExecuteWithQuantity()
    {
        $shopData = [
            'freight_state' => 'Tennessee',
            'freight_city' => 'Memphis',
            'freight_postcode' => '38116',
            'freight_account_number' => '123456789'
        ];

        $shippingAddress = $this->createMock(Address::class);
        $shippingAddress->method('getCity')->willReturn('Nashville');
        $shippingAddress->method('getRegionCode')->willReturn('TN');
        $shippingAddress->method('getCountryId')->willReturn('US');
        $shippingAddress->method('getPostcode')->willReturn('37203');

        $package = [
            'freightClass' => '100',
            'type' => 'box',
            'weight' => 50,
            'specialServices' => false,
            'quantity' => 5,
            'shape' => [
                'length' => 10,
                'width' => 10,
                'depth' => 10,
                'volume' => 1000,
                'area' => 100
            ]
        ];

        $this->collectionMock->method('addRegionNameFilter')->willReturnSelf();
        $this->collectionMock->method('addCountryCodeFilter')->willReturnSelf();
        $this->collectionMock->method('getFirstItem')->willReturnSelf();
        $this->collectionMock->method('toArray')->willReturn(['code' => 'TN']);
        $this->collectionFactoryMock->method('create')->willReturn($this->collectionMock);

        $this->helperMock->method('getFreightShippingRatesUrl')
            ->willReturn('https://api.example.com/freight-rates');
        $this->helperMock->method('getFedexRatesToken')->willReturn('token123');
        $this->jsonSerializerMock->method('serialize')->willReturn('{}');

        $apiResponse = '{"output":{"rateReplyDetails":[{"rate":100}]}}';
        $this->curlClientMock->method('getBody')->willReturn($apiResponse);
        $this->curlClientMock->method('getStatus')->willReturn(200);
        $this->jsonSerializerMock->method('unserialize')
            ->willReturn(['output' => ['rateReplyDetails' => [['rate' => 100]]]]);

        $result = $this->freightCheckoutPricing->execute(
            $shopData,
            $shippingAddress,
            '2023-01-01',
            $package
        );

        $this->assertEquals([['rate' => 100]], $result);
    }

    public function testExecuteWithException()
    {
        $shopData = [
            'freight_state' => 'Tennessee',
            'freight_city' => 'Memphis',
            'freight_postcode' => '38116',
            'freight_account_number' => '123456789'
        ];

        $shippingAddress = $this->createMock(Address::class);
        $package = [
            'freightClass' => '100',
            'type' => 'box',
            'weight' => 50,
            'specialServices' => false,
            'shape' => [
                'length' => 10,
                'width' => 10,
                'depth' => 10,
                'volume' => 1000,
                'area' => 100
            ]
        ];

        $this->collectionMock->method('addRegionNameFilter')->willReturnSelf();
        $this->collectionMock->method('addCountryCodeFilter')->willReturnSelf();
        $this->collectionMock->method('getFirstItem')->willReturnSelf();
        $this->collectionMock->method('toArray')->willReturn(['code' => 'TN']);
        $this->collectionFactoryMock->method('create')->willReturn($this->collectionMock);

        $this->helperMock->method('getFreightShippingRatesUrl')
            ->willThrowException(new \Exception('API Error'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->willReturnSelf();

        $result = $this->freightCheckoutPricing->execute(
            $shopData,
            $shippingAddress,
            '2023-01-01',
            $package
        );

        $this->assertEquals([], $result);
    }
    
    public function testToggleConfigPreferredShipping()
    {
        $toggleConstant = 'tiger_d217815';
        
        $shopData = [
            'freight_state' => 'Tennessee',
            'freight_city' => 'Memphis',
            'freight_postcode' => '38116',
            'freight_account_number' => '123456789'
        ];

        $shippingAddress = $this->createMock(Address::class);
        $package = [
            'freightClass' => '100',
            'type' => 'box',
            'weight' => 50,
            'specialServices' => false,
            'shape' => [
                'length' => 10,
                'width' => 10,
                'depth' => 10,
                'volume' => 1000,
                'area' => 100
            ]
        ];

        $this->collectionMock->method('addRegionNameFilter')->willReturnSelf();
        $this->collectionMock->method('addCountryCodeFilter')->willReturnSelf();
        $this->collectionMock->method('getFirstItem')->willReturnSelf();
        $this->collectionMock->method('toArray')->willReturn(['code' => 'TN']);
        $this->collectionFactoryMock->method('create')->willReturn($this->collectionMock);

        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with($this->equalTo($toggleConstant))
            ->willReturn(true);
            
        $reflectionMethod = new \ReflectionMethod(FreightCheckoutPricing::class, 'getRateRequestType');
        $reflectionMethod->setAccessible(true);
        
        $result = $reflectionMethod->invoke($this->freightCheckoutPricing);
        $this->assertEquals(["PREFERRED"], $result);
        
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with($this->equalTo($toggleConstant))
            ->willReturn(false);
            
        $toggleConfigMock2 = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $toggleConfigMock2->method('getToggleConfigValue')
            ->with($this->equalTo($toggleConstant))
            ->willReturn(false);
            
        $freightCheckoutPricing2 = new FreightCheckoutPricing(
            $this->curlClientMock,
            $this->jsonSerializerMock,
            $this->loggerMock,
            $this->helperMock,
            $this->collectionFactoryMock,
            $toggleConfigMock2
        );
        
        $result = $reflectionMethod->invoke($freightCheckoutPricing2);
        $this->assertEquals(["ACCOUNT"], $result);
    }
}
