<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\PlaceOrder;

use Exception;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Model\PlaceOrder\RequestData\PickupData;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Model\PlaceOrder\RequestData;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Helper\Data as CartDataHelper;

class RequestDataTest extends TestCase
{
    protected $cartIntegrationRepository;
    protected $pickupData;
    /**
     * @var (\Fedex\Cart\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cartHelper;
    protected $cartIntegrationInterface;
    protected $quoteMock;
    protected $recipientsBuilder;
    protected function setUp(): void
    {
        $this->cartIntegrationRepository = $this->getMockBuilder(CartIntegrationRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getByQuoteId'])
            ->addMethods(['getDeliveryData'])
            ->getMockForAbstractClass();
        $this->pickupData = $this->createMock(PickupData::class);
        $this->cartHelper = $this->createMock(CartDataHelper::class);
        $this->cartIntegrationInterface = $this->createMock(CartIntegrationInterface::class);
        $this->quoteMock = $this->createMock(Quote::class);
        $this->recipientsBuilder = new RequestData(
            $this->cartIntegrationRepository,
            $this->cartHelper,
            [$this->pickupData]
        );
    }

    public function testBuild()
    {
        $this->cartIntegrationRepository->expects($this->once())
            ->method('getByQuoteId')
            ->willReturn($this->cartIntegrationInterface);

        $this->pickupData->expects($this->once())->method('getData')->willReturn($this->getPickupDataMock());

        $this->recipientsBuilder->build(
            $this->quoteMock
        );
    }

    public function testBuildException()
    {
        $this->cartIntegrationRepository->expects($this->once())
            ->method('getByQuoteId')
            ->willThrowException(new Exception("exception message"));

        $this->expectException(Exception::class);

        $this->recipientsBuilder->build(
            $this->quoteMock,
            []
        );
    }

    private function getPickupDataMock(): array
    {
        return [
            "pickupData" => json_encode([
                "addressInformation" => [
                    "estimate_pickup_time" => "2024-05-04 00:00:00",
                    "estimate_pickup_time_for_api" => "2024-05-04 00:00:00"
                ]
            ])
        ];
    }
}
