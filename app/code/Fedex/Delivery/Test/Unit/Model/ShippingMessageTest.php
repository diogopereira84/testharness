<?php
/**
 * @category  Fedex
 * @package   Fedex_Delivery
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Delivery\Test\Unit\Model;

use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\Delivery\Model\ShippingMessage\Rule\RuleInterface;
use Fedex\Delivery\Model\ShippingMessage\RuleCompositeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\FXOPricing\Api\Data\RateInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteInterface;
use InvalidArgumentException;
use Magento\Quote\Api\Data\CartInterface;
use Fedex\Delivery\Api\ShippingMessageInterface;
use Fedex\Delivery\Model\ShippingMessage\TransportInterface;
use Fedex\Delivery\Model\ShippingMessage\Transport;
use Fedex\Delivery\Model\ShippingMessage;

class ShippingMessageTest extends TestCase
{
    /**
     * Show message key
     */
    private const SHOW_FREE_SHIPPING_MESSAGE_KEY = 'show_free_shipping_message';

    /**
     * Show message status true
     */
    private const SHOW_FREE_SHIPPING_MESSAGE_TRUE = 1;

    /**
     * Show message status false
     */
    private const SHOW_FREE_SHIPPING_MESSAGE_FALSE = 0;

    /**
     * Message text key
     */
    private const FREE_SHIPPING_MESSAGE_KEY = 'free_shipping_message';

    /**
     * Message text value when to show
     */
    private const FREE_SHIPPING_MESSAGE_SHOW_VALUE = 'We selected the best discount for you';

    /**
     * Message text value when do not show
     */
    private const FREE_SHIPPING_MESSAGE_NOT_SHOW_VALUE = null;

    /**
     * Response when should show message
     */
    private const RESPONSE_SHOW_MESSAGE = [
        self::SHOW_FREE_SHIPPING_MESSAGE_KEY => self::SHOW_FREE_SHIPPING_MESSAGE_TRUE,
        self::FREE_SHIPPING_MESSAGE_KEY => self::FREE_SHIPPING_MESSAGE_SHOW_VALUE
    ];

    /**
     * Response when should not show message
     */
    private const RESPONSE_NOT_SHOW_MESSAGE = [
        self::SHOW_FREE_SHIPPING_MESSAGE_KEY => self::SHOW_FREE_SHIPPING_MESSAGE_FALSE,
        self::FREE_SHIPPING_MESSAGE_KEY => self::FREE_SHIPPING_MESSAGE_NOT_SHOW_VALUE
    ];

    /**
     * @var MockObject|TransportInterface
     */
    private MockObject|TransportInterface $transportMock;

    /**
     * @var MockObject|DeliveryHelper
     */
    private MockObject|DeliveryHelper $deliveryHelperMock;

    /**
     * @var MockObject|RuleCompositeInterface
     */
    private MockObject|RuleCompositeInterface $compositeMock;

    /**
     * @var ShippingMessage
     */
    private ShippingMessage $shippingMessage;

    /**
     * Setup tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->deliveryHelperMock = $this->createMock(DeliveryHelper::class);
        $this->compositeMock = $this->getMockForAbstractClass(RuleCompositeInterface::class);
        $this->transportMock = $this->getMockForAbstractClass(TransportInterface::class);
        $this->shippingMessage = new ShippingMessage($this->compositeMock, $this->deliveryHelperMock);
    }

    /**
     * Test method getMessage when should show message
     *
     * @return void
     */
    public function testGetMessageShow(): void
    {
        $this->compositeMock
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $this->deliveryHelperMock
            ->expects($this->once())
            ->method('getPromoMessageText')
            ->willReturn(self::FREE_SHIPPING_MESSAGE_SHOW_VALUE);
        $this->assertEquals(
            self::RESPONSE_SHOW_MESSAGE,
            $this->shippingMessage->getMessage($this->transportMock)
        );
    }

    /**
     * Test method isValid when should not show message
     *
     * @return void
     */
    public function testGetMessageDoNotShow(): void
    {
        $this->compositeMock
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $this->assertEquals(
            self::RESPONSE_NOT_SHOW_MESSAGE,
            $this->shippingMessage->getMessage($this->transportMock)
        );
    }
}
