<?php

use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Plugin\UpdateGuestCartAfterContactInformation;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\CartGraphQl\Model\Resolver\UpdateGuestCartContactInformation;
use Fedex\FXOPricing\Model\FXORateQuote;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\FuseBiddingQuote\Helper\FuseBidGraphqlHelper;
use Fedex\CartGraphQl\Model\Checkout\Cart;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\CartGraphQl\Model\Address\CollectRates\ShippingRate;
use Magento\Quote\Model\Quote;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use Magento\GraphQl\Model\Query\ContextExtensionInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\B2b\Model\Quote\Address;

class UpdateGuestCartAfterContactInformationTest extends TestCase
{
    protected $extensionAttributesMock;
    private $updateGuestCartAfterContactInformation;
    private $modelFXORateQuoteMock;
    private $cartRepositoryMock;
    private $cartModelMock;
    private $fuseBidGraphqlHelperMock;
    private $cartIntegrationRepositoryMock;
    private $shippingRateMock;
    private $contextMock;
    private $fieldMock;
    private $updateGuestCartContactInformationMock;
    private $cartMock;

    protected function setUp(): void
    {
        $this->modelFXORateQuoteMock = $this->createMock(FXORateQuote::class);
        $this->cartRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->cartModelMock = $this->createMock(Cart::class);
        $this->fuseBidGraphqlHelperMock = $this->createMock(FuseBidGraphqlHelper::class);
        $this->cartIntegrationRepositoryMock = $this->createMock(CartIntegrationRepositoryInterface::class);
        $this->shippingRateMock = $this->createMock(ShippingRate::class);
        $this->contextMock = $this->getMockBuilder(ContextInterface::class)
            ->addMethods(['getExtensionAttributes', 'getUserId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->fieldMock = $this->createMock(Field::class);
        $this->updateGuestCartContactInformationMock = $this->createMock(UpdateGuestCartContactInformation::class);
        $this->cartMock = $this->createMock(Quote::class);
        $this->extensionAttributesMock = $this->getMockBuilder(ContextExtensionInterface::class)
            ->onlyMethods(['getStore'])
            ->addMethods(['getId'])
            ->getMockForAbstractClass();
        $this->updateGuestCartAfterContactInformation = new UpdateGuestCartAfterContactInformation(
            $this->modelFXORateQuoteMock,
            $this->cartRepositoryMock,
            $this->cartModelMock,
            $this->fuseBidGraphqlHelperMock,
            $this->cartIntegrationRepositoryMock,
            $this->shippingRateMock
        );
    }

    public function testAfterResolveValidatesToggleConfigAndCollectsRates()
    {
        $contextMock = $this->contextMock;
        $resolveRequestMock = $this->createMock(ResolveRequest::class);

        $requests = [$resolveRequestMock];

        $args = [
            'cart_id' => "1"
        ];
        $resolveRequestMock->expects($this->any())
            ->method('getArgs')
            ->willReturn(['input' => $args]);

        $this->fuseBidGraphqlHelperMock->expects($this->once())
            ->method('validateToggleConfig')
            ->willReturn(true);

        $this->extensionAttributesMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->extensionAttributesMock->expects($this->any())->method('getId')->willReturn(1);

        $this->contextMock->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);

        $this->fuseBidGraphqlHelperMock->expects($this->once())
            ->method('getCartForBidQuote')
            ->with('1', $this->anything())
            ->willReturn($this->cartMock);

        $this->cartIntegrationRepositoryMock->expects($this->once())
            ->method('getByQuoteId')
            ->with($this->cartMock->getId())
            ->willReturn($this->createMock(CartIntegrationInterface::class));

        $this->modelFXORateQuoteMock->expects($this->once())
            ->method('getFXORateQuote')
            ->with($this->cartMock);

        $shippingAddressMock = $this->createMock(Address::class);
        $shippingAddressMock->expects($this->any())
            ->method('getCountryId')
            ->willReturn('US');

        $this->cartMock->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($shippingAddressMock);

        $this->shippingRateMock->expects($this->any())
            ->method('collect')
            ->with($shippingAddressMock, $this->createMock(CartIntegrationInterface::class));

        $shippingAddressMock->expects($this->any())
            ->method('save');

        $this->cartRepositoryMock->expects($this->any())
            ->method('save')
            ->with($this->cartMock);

        $result = 'test result';
        $response = $this->updateGuestCartAfterContactInformation->afterResolve(
            $this->updateGuestCartContactInformationMock,
            $result,
            $contextMock,
            $this->fieldMock,
            $requests
        );

        $this->assertEquals($result, $response);
    }

    public function testAfterResolveRateQuoteRequestWithException(): void
    {
        $resolveRequestMock = $this->createMock(ResolveRequest::class);

        $requests = [$resolveRequestMock];
        $resolveResult = [];
        $args = [
            'cart_id' => "1"
        ];
        $resolveRequestMock->expects($this->any())
            ->method('getArgs')
            ->willReturn(['input' => $args]);

        $this->cartModelMock->expects(static::once())
            ->method('getCart')
            ->with(1)
            ->willReturn($this->cartMock);

        $exception = new GraphQlFujitsuResponseException(__("Some message"));
        $this->modelFXORateQuoteMock->expects(static::any())
            ->method('getFXORateQuote')
            ->with($this->cartMock)
            ->willThrowException($exception);

        $this->expectException(GraphQlFujitsuResponseException::class);
        $this->updateGuestCartAfterContactInformation->afterResolve(
            $this->updateGuestCartContactInformationMock,
            $resolveResult,
            $this->contextMock,
            $this->fieldMock,
            $requests
        );
    }
}
