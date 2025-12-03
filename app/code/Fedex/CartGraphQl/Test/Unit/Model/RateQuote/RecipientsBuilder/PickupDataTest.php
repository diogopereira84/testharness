<?php

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\RateQuote\RecipientsBuilder;

use Fedex\CartGraphQl\Model\RateQuote\RecipientsBuilder\PickupData;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Api\Data\RecipientsBuilderDataInterface;
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\Data\PickupData as ResolverPickupData;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InStoreConfigInterface;

class PickupDataTest extends TestCase
{
    /**
     * @var PickupData|MockObject
     */
    protected $pickupData;

    /**
     * @var CartIntegrationInterface|MockObject
     */
    protected $cartIntegrationInterface;

    /**
     * @var InStoreConfigInterface|MockObject
     */
    protected $inStoreConfigInterface;

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

        $objectManager = new ObjectManager($this);

        $this->pickupData = $objectManager->getObject(
            PickupData::class,
            [
                'instoreConfig' => $this->inStoreConfigInterface
            ]
        );
    }

    public function testGetIdentifierKeyReturnsConstant()
    {
        $expected = ResolverPickupData::IDENTIFIER_KEY;
        $result = $this->pickupData->getIdentifierKey();
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests that the proceed method returns the expected array structure when delivery dates fields are disabled
     */
    public function testProceedReturnsExpectedArrayWithDeliveryDatesDisabled()
    {
        $referenceId = 'ABC123';
        $storeId = 789;
        $productAssociations = ['product_1', 'product_2'];
        $requestedPickupLocalTime = '2024-05-21T10:00:00';

        $this->cartIntegrationInterface
            ->expects($this->any())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->inStoreConfigInterface
            ->method('isDeliveryDatesFieldsEnabled')
            ->willReturn(false);

        $result = $this->pickupData->proceed(
            $referenceId,
            $this->cartIntegrationInterface,
            $productAssociations,
            $requestedPickupLocalTime
        );

        $expected = [
            'arrRecipients' => [
                [
                    'contact' => null,
                    'reference' => $referenceId,
                    'pickUpDelivery' => [
                        'location' => [
                            'id' => $storeId,
                        ],
                        'requestedPickupLocalTime' => $requestedPickupLocalTime,
                    ],
                    'productAssociations' => $productAssociations,
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Tests that the proceed method returns the expected array structure when delivery dates fields are Enabled
     */
    public function testProceedReturnsExpectedArrayWithDeliveryDatesEnabled()
    {
        $referenceId = 'XYZ789';
        $storeId = 456;
        $productAssociations = ['product_a'];
        $requestedPickupLocalTime = '2024-06-01T08:00:00';
        $requestedDeliveryLocalTime = '2024-06-02T17:00:00';
        $holdUntilDate = '2024-06-03';

        $this->cartIntegrationInterface
            ->expects($this->any())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->inStoreConfigInterface
            ->method('isDeliveryDatesFieldsEnabled')
            ->willReturn(true);

        $result = $this->pickupData->proceed(
            $referenceId,
            $this->cartIntegrationInterface,
            $productAssociations,
            $requestedPickupLocalTime,
            $requestedDeliveryLocalTime,
            null,
            $holdUntilDate
        );

        $expected = [
            'arrRecipients' => [
                [
                    'contact' => null,
                    'reference' => $referenceId,
                    'pickUpDelivery' => [
                        'location' => [
                            'id' => $storeId,
                        ],
                        'requestedPickupLocalTime' => $requestedPickupLocalTime,
                        'holdUntilDate' => $holdUntilDate,
                    ],
                    'productAssociations' => $productAssociations,
                    'requestedDeliveryLocalTime' => $requestedDeliveryLocalTime,
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }
}
