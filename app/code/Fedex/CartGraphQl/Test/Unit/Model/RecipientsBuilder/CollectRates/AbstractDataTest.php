<?php
namespace Fedex\CartGraphQl\Test\Unit\Model\RecipientsBuilder\CollectRates;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Directory\Model\Region;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Stdlib\DateTime;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\CartGraphQl\Model\RateQuote\ShippingDelivery;
use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Test\Unit\Model\RecipientsBuilder\CollectRates\ConcreteData;

class AbstractDataTest extends TestCase
{
    /**
     * @var mixed $region Stores the region information associated with the recipient.
     */
    private $region;

    /**
     * @var CartIntegrationRepositoryInterface
     */
    private $cartIntegrationRepository;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var InstoreConfig
     */
    private $instoreConfig;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var ShippingDelivery
     */
    private $shippingDelivery;

    /**
     * @var CartIntegrationInterface
     */
    private $integration;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        $this->region = $this->createMock(Region::class);
        $this->cartIntegrationRepository = $this->createMock(CartIntegrationRepositoryInterface::class);
        $this->dateTime = $this->createMock(DateTime::class);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->instoreConfig = $this->createMock(InstoreConfig::class);
        $this->jsonSerializer = $this->createMock(JsonSerializer::class);
        $this->shippingDelivery = $this->createMock(ShippingDelivery::class);
        $this->integration = $this->createMock(CartIntegrationInterface::class);
    }

    /**
     * Test that the getIdentifierKey method returns the expected key.
     */
    public function testGetDataReturnsProceedResultWhenIdentifierKeyExists()
    {
        $referenceId = 'ref123';
        $productAssociations = ['sku1', 'sku2'];
        $deliveryData = ['test_key' => 'value'];

        $this->integration->method('getDeliveryData')->willReturn(json_encode($deliveryData));
        $this->jsonSerializer->method('unserialize')->willReturn($deliveryData);

        $data = new ConcreteData(
            $this->region,
            $this->cartIntegrationRepository,
            $this->dateTime,
            $this->cartRepository,
            $this->instoreConfig,
            $this->jsonSerializer,
            $this->shippingDelivery
        );

        $result = $data->getData($referenceId, $this->integration, $productAssociations);
        $this->assertEquals(['proceeded' => true], $result);
    }

    /**
     * Test that the getIdentifierKey method returns null when the identifier key does not exist.
     */
    public function testGetDataReturnsNullWhenIdentifierKeyDoesNotExist()
    {
        $referenceId = 'ref123';
        $productAssociations = ['sku1', 'sku2'];
        $deliveryData = ['other_key' => 'value'];

        $this->integration->method('getDeliveryData')->willReturn(json_encode($deliveryData));
        $this->jsonSerializer->method('unserialize')->willReturn($deliveryData);

        $data = new ConcreteData(
            $this->region,
            $this->cartIntegrationRepository,
            $this->dateTime,
            $this->cartRepository,
            $this->instoreConfig,
            $this->jsonSerializer,
            $this->shippingDelivery
        );

        $result = $data->getData($referenceId, $this->integration, $productAssociations);
        $this->assertNull($result);
    }

    /**
     * Test that the getData method returns null when delivery data is not an array.
     */
    public function testGetDataReturnsNullWhenDeliveryDataIsNotArray()
    {
        $referenceId = 'ref123';
        $productAssociations = ['sku1', 'sku2'];
        $deliveryData = null;

        $this->integration->method('getDeliveryData')->willReturn('null');
        $this->jsonSerializer->method('unserialize')->willReturn($deliveryData);

        $data = new ConcreteData(
            $this->region,
            $this->cartIntegrationRepository,
            $this->dateTime,
            $this->cartRepository,
            $this->instoreConfig,
            $this->jsonSerializer,
            $this->shippingDelivery
        );

        $result = $data->getData($referenceId, $this->integration, $productAssociations);
        $this->assertNull($result);
    }
}
