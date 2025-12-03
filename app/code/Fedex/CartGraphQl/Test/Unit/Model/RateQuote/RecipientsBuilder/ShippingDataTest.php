<?php

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\RateQuote\RecipientsBuilder;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Api\Data\RecipientsBuilderDataInterface;
use Fedex\CartGraphQl\Model\RateQuote\ShippingDelivery;
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\Data\ShippingData as ResolverShippingData;
use Fedex\CartGraphQl\Model\RateQuote\RecipientsBuilder\ShippingData;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InStoreConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;

class ShippingDataTest extends TestCase
{
    /**
     * @var ShippingData|MockObject
     */
    protected $shippingData;

     /**
      * @var CartIntegrationInterface|MockObject
      */
    protected $cartIntegrationInterface;

    /**
     * @var InStoreConfigInterface|MockObject
     */
    protected $inStoreConfigInterface;

    /**
     * @var Json|MockObject
     */
    protected $json;

    /**
     * @var ShippingDelivery|MockObject
     */
    protected $shippingDelivery;

    protected function setUp(): void
    {

        $this->cartIntegrationInterface = $this->getMockBuilder(CartIntegrationInterface::class)
            ->setMethods(['getStoreId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->inStoreConfigInterface = $this->getMockBuilder(InStoreConfigInterface::class)
            ->setMethods(['isDeliveryDatesFieldsEnabled'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->json = $this->getMockBuilder(Json::class)
            ->setMethods(['unserialize'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingDelivery = $this->getMockBuilder(ShippingDelivery::class)
            ->setMethods([
                'validateIfLocalDelivery',
                'setLocalDelivery',
                'setExternalDelivery'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);

        $this->shippingData = $objectManager->getObject(
            ShippingData::class,
            [
                'shippingDelivery' => $this->shippingDelivery,
                'instoreConfig' => $this->inStoreConfigInterface,
                'jsonSerializer' => $this->json
            ]
        );
    }

    public function testGetIdentifierKeyReturnsConstant()
    {
        $expected = ResolverShippingData::IDENTIFIER_KEY;
        $result = $this->shippingData->getIdentifierKey();
        $this->assertEquals($expected, $result);
    }

    public function testProceedReturnsLocalDeliveryWithServiceTypeEnabled(): void
    {
        $referenceId = 'REF123';
        $productAssociations = ['sku1'];
        $shippingMethod = 'local_method';
        $deliveryData = ['address' => 'test address'];

        $expected = [
            'arrRecipients' => [
                [
                    'contact' => null,
                    'reference' => $referenceId,
                    ShippingDelivery::LOCAL_DELIVERY => $deliveryData,
                    'productAssociations' => $productAssociations
                ]
            ]
        ];

        $this->cartIntegrationInterface->method('getDeliveryData')
            ->willReturn(json_encode(['shipping_method' => $shippingMethod]));

        $this->json->method('unserialize')
            ->willReturn(['shipping_method' => $shippingMethod]);

        $this->inStoreConfigInterface->method('isEnableServiceTypeForRAQ')
            ->willReturn(true);
        $this->shippingDelivery->method('validateIfLocalDelivery')
            ->with($shippingMethod)
            ->willReturn(true);
        $this->shippingDelivery->method('setLocalDelivery')
            ->willReturn($deliveryData);
        $this->inStoreConfigInterface->method('isDeliveryDatesFieldsEnabled')
            ->willReturn(false);

        $result = $this->shippingData->proceed(
            $referenceId,
            $this->cartIntegrationInterface,
            $productAssociations
        );
        $this->assertEquals($expected, $result);
    }

    public function testProceedReturnsLocalDeliveryWithServiceTypeDisabled(): void
    {
        $referenceId = 'REF123';
        $productAssociations = ['sku1'];
        $shippingMethod = 'local_method';
        $deliveryData = ['address' => 'test address'];

        $expected = [
            'arrRecipients' => [
                [
                    'contact' => null,
                    'reference' => $referenceId,
                    ShippingDelivery::LOCAL_DELIVERY => $deliveryData,
                    'productAssociations' => $productAssociations
                ]
            ]
        ];

        $this->cartIntegrationInterface->method('getDeliveryData')
            ->willReturn(json_encode(['shipping_method' => $shippingMethod]));

        $this->json->method('unserialize')
            ->willReturn(['shipping_method' => $shippingMethod]);

        $this->inStoreConfigInterface->method('isEnableServiceTypeForRAQ')
            ->willReturn(false);
        $this->shippingDelivery->method('validateIfLocalDelivery')
            ->with($shippingMethod)
            ->willReturn(true);
        $this->shippingDelivery->method('setLocalDelivery')
            ->willReturn($deliveryData);
        $this->inStoreConfigInterface->method('isDeliveryDatesFieldsEnabled')
            ->willReturn(false);

        $result = $this->shippingData->proceed(
            $referenceId,
            $this->cartIntegrationInterface,
            $productAssociations
        );
        $this->assertEquals($expected, $result);
    }

    public function testProceedReturnsExternalDeliveryWithEstimatedDate(): void
    {
        $referenceId = 'EXT123';
        $shippingMethod = 'external_method';
        $productAssociations = ['skuA'];
        $shippingEstimatedDeliveryLocalTime = '2025-05-22T12:00:00';

        $expected = [
            'arrRecipients' => [
                [
                    'contact' => null,
                    'reference' => $referenceId,
                    ShippingDelivery::EXTERNAL_DELIVERY => [
                        'externalData' => true,
                        'requestedDeliveryLocalTime' => '2025-05-23T18:00:00'
                    ],
                    'productAssociations' => $productAssociations
                ]
            ]
        ];

        $this->cartIntegrationInterface->method('getDeliveryData')
            ->willReturn(json_encode(['shipping_method' => $shippingMethod]));

        $this->json->method('unserialize')
            ->willReturn(['shipping_method' => $shippingMethod]);

        $this->inStoreConfigInterface->method('isEnableServiceTypeForRAQ')
            ->willReturn(true);
        $this->shippingDelivery->method('validateIfLocalDelivery')
            ->with($shippingMethod)
            ->willReturn(false);
        $this->shippingDelivery->method('setExternalDelivery')
            ->with(['shipping_method' => $shippingMethod], null, $shippingEstimatedDeliveryLocalTime)
            ->willReturn(['externalData' => true]);
        $this->inStoreConfigInterface->method('isDeliveryDatesFieldsEnabled')
            ->willReturn(true);

        $result = $this->shippingData->proceed(
            $referenceId,
            $this->cartIntegrationInterface,
            $productAssociations,
            null,
            '2025-05-23T18:00:00',
            $shippingEstimatedDeliveryLocalTime
        );
        $this->assertEquals($expected, $result);
    }
}
