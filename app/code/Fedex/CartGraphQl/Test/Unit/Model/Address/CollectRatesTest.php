<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Address;

use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Model\Address\CollectRates\PickupRate;
use Fedex\CartGraphQl\Model\Address\CollectRates\ShippingRate;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Model\Address\CollectRates;
use Fedex\B2b\Model\Quote\Address;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\CartGraphQl\Model\Address\Builder;
use Psr\Log\LoggerInterface;

class CollectRatesTest extends TestCase
{
    protected $cartIntegrationRepository;
    protected $cartIntegrationInterface;
    protected $addressBuilder;
    protected $pickupRate;
    protected $shippingRate;
    protected $address;
    protected $collectRates;

    protected $logger;
    protected function setUp(): void
    {
        $this->cartIntegrationRepository = $this->getMockBuilder(CartIntegrationRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getByQuoteId'])
            ->addMethods(['getDeliveryData'])
            ->getMockForAbstractClass();
        $this->cartIntegrationInterface = $this->createMock(CartIntegrationInterface::class);
        $this->addressBuilder = $this->createMock(Builder::class);
        $this->pickupRate = $this->createMock(PickupRate::class);
        $this->shippingRate = $this->createMock(ShippingRate::class);
        $this->address = $this->createMock(Address::class);
        $this->address->expects($this->any())->method('getQuote')->willReturnSelf();
        $this->address->expects($this->once())->method('getId')->willReturn(5411);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->collectRates = new CollectRates(
            $this->cartIntegrationRepository,
            $this->addressBuilder,
            $this->logger,
            [
                $this->pickupRate,
                $this->shippingRate,
            ]
        );
    }

    public function testExecuteWithNoDeliveryData()
    {
        $this->cartIntegrationRepository->expects($this->once())
            ->method('getByQuoteId')
            ->willReturn($this->cartIntegrationInterface);

        $this->addressBuilder->expects($this->once())->method('setShippingData');

        $this->collectRates->execute($this->address);
    }

    public function testExecuteWithDeliveryData()
    {
        $this->cartIntegrationInterface->expects($this->once())
            ->method('getDeliveryData')
            ->willReturn('{}');
        $this->cartIntegrationRepository->expects($this->once())
            ->method('getByQuoteId')
            ->willReturn($this->cartIntegrationInterface);

        $this->pickupRate->expects($this->once())->method('collect')->willReturnSelf();
        $this->shippingRate->expects($this->once())->method('collect')->willReturnSelf();

        $this->collectRates->execute($this->address);
    }

    public function testExecuteWithNoSuchEntityException()
    {
        $quoteId = 5411;

        $exception = new NoSuchEntityException(
            __('No such entity found with quote_id = %1', $quoteId)
        );
        $this->cartIntegrationRepository->expects($this->once())
            ->method('getByQuoteId')
            ->with($quoteId)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error in Fetching Quote Integration:'));

        $this->addressBuilder->expects($this->once())
            ->method('setShippingData')
            ->with($this->address, $this->address);

        $this->pickupRate->expects($this->never())->method('collect');
        $this->shippingRate->expects($this->never())->method('collect');

        $this->collectRates->execute($this->address);
    }
}
