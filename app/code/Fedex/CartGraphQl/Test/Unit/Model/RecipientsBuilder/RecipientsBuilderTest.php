<?php

/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\RecipientsBuilder;

use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Model\RateQuote\RecipientsBuilder\PickupData;
use Fedex\CartGraphQl\Model\RateQuote\RecipientsBuilder\ShippingData;
use Fedex\FXOPricing\Model\RateQuoteApi\InStoreRecipientsBuilder;
use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Model\RateQuote\RecipientsBuilder;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;

class RecipientsBuilderTest extends TestCase
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     *
     * Cart integration repository instance.
     */
    protected $cartIntegrationRepository;

    /**
     * @var InStoreRecipientsBuilder
     *
     * Instance of the InStoreRecipientsBuilder used for building in-store recipient data in tests.
     */
    protected $inStoreRecipientsBuilder;

    /**
     * @var mixed
     *
     * Stores the data related to pickup information for testing purposes.
     */
    protected $pickupData;

    /**
     * @var mixed
     *
     * $shippingData Stores the shipping data used in the test cases.
     */
    protected $shippingData;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     *
     * Cart integration interface instance.
     */
    protected $cartIntegrationInterface;

    /**
     * @var RecipientsBuilder
     *
     * Instance of the RecipientsBuilder used for testing purposes.
     */
    protected $recipientsBuilder;

    /**
     * Sets up the environment before each test.
     */
    protected function setUp(): void
    {
        $this->cartIntegrationRepository = $this->getMockBuilder(CartIntegrationRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getByQuoteId'])
            ->addMethods(['getDeliveryData'])
            ->getMockForAbstractClass();
        $this->inStoreRecipientsBuilder = $this->createMock(InStoreRecipientsBuilder::class);
        $this->pickupData = $this->createMock(PickupData::class);
        $this->shippingData = $this->createMock(ShippingData::class);
        $this->cartIntegrationInterface = $this->createMock(CartIntegrationInterface::class);

        $this->recipientsBuilder = new RecipientsBuilder(
            $this->cartIntegrationRepository,
            $this->inStoreRecipientsBuilder,
            [
                $this->pickupData,
                $this->shippingData
            ]
        );
    }

    /**
     * Tests the execute method when no delivery data is provided.
     *
     * @return void
     */
    public function testExecuteWithNoDeliveryData()
    {
        $this->cartIntegrationRepository->expects($this->once())
            ->method('getByQuoteId')
            ->willReturn($this->cartIntegrationInterface);

        $this->inStoreRecipientsBuilder->expects($this->once())->method('build');

        $this->recipientsBuilder->build(
            'referenceId',
            '1234',
            []
        );
    }

    /**
     * Tests the execute method with delivery data.
     *
     * @return void
     */
    public function testExecuteWithDeliveryData()
    {
        $this->cartIntegrationInterface->expects($this->once())
            ->method('getDeliveryData')
            ->willReturn('{}');
        $this->cartIntegrationRepository->expects($this->once())
            ->method('getByQuoteId')
            ->willReturn($this->cartIntegrationInterface);

        $this->pickupData->expects($this->once())->method('getData')->willReturn(null);
        $this->shippingData->expects($this->once())->method('getData')->willReturn(['some_data']);

        $this->recipientsBuilder->build(
            'referenceId',
            '1234',
            []
        );
    }

    /**
     * Tests that the build method returns null when a NoSuchEntityException is thrown.
     *
     * @return void
     */
    public function testBuildReturnsNullWhenNoSuchEntityExceptionIsThrown(): void
    {
        $this->cartIntegrationRepository
            ->expects($this->once())
            ->method('getByQuoteId')
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException(__('Not found')));

        $result = $this->recipientsBuilder->build(
            'referenceId',
            '1234',
            [],
            'pickupTime',
            'deliveryTime',
            'shippingEstimatedDeliveryLocalTime',
            'holdUntilDate'
        );

        $this->assertNull($result);
    }
}
