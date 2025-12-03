<?php
/**
 * @category     Fedex
 * @package      Fedex_SubmitOrderSidebar
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\RateQuote;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Model\RateQuote\RecipientsBuilder;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\CartGraphQl\Api\RecipientsBuilderInterface;
use Fedex\FXOPricing\Model\RateQuoteApi\InStoreRecipientsBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\Cart\Api\Data\CartIntegrationInterface;

class RecipientsBuilderTest extends TestCase
{
    /**
     * @var RecipientsBuilder
     */
    protected $recipientsBuilder;

    /**
     * @var CartIntegrationRepositoryInterface
     */
    protected $cartIntegrationRepository;

    /**
     * @var InStoreRecipientsBuilder
     */
    protected $inStoreRecipientsBuilder;

    /**
     * @var array
     */
    protected $deliveryData = [];

    /**
     * @var CartIntegrationInterface
     */
    protected $cartIntegrationInterface;

    protected function setUp(): void
    {
        $this->cartIntegrationRepository = $this->getMockBuilder(CartIntegrationRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getByQuoteId','getDeliveryData'])
            ->getMockForAbstractClass();

        $this->inStoreRecipientsBuilder = $this->getMockBuilder(InStoreRecipientsBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['build'])
            ->getMock();

        $this->cartIntegrationInterface = $this->getMockBuilder(CartIntegrationInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);

        $this->recipientsBuilder = $objectManager->getObject(
            RecipientsBuilder::class,
            [
                'cartIntegrationRepository' => $this->cartIntegrationRepository,
                'inStoreRecipientsBuilder' => $this->inStoreRecipientsBuilder,
                'deliveryData' => $this->deliveryData,
            ]
        );
    }

    public function testBuildWithDeliveryData()
    {
        $referenceId = 'test_reference_id';
        $cartId = 123;
        $productAssociations = ['product1', 'product2'];
        $requestedPickupLocalTime = '2023-10-01T10:00:00Z';
        $requestedDeliveryLocalTime = '2023-10-02T10:00:00Z';
        $shippingEstimatedDeliveryLocalTime = '2023-10-03T10:00:00Z';
        $holdUntilDate = '2023-10-04';

        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->with($cartId)
            ->willReturn($this->cartIntegrationInterface);

        $this->inStoreRecipientsBuilder->expects($this->any())
            ->method('build')
            ->with(
                $referenceId,
                $cartId,
                $productAssociations,
                $requestedPickupLocalTime,
                $requestedDeliveryLocalTime,
                $shippingEstimatedDeliveryLocalTime,
                $holdUntilDate
            )
            ->willReturn(['recipient_data']);

        $result = $this->recipientsBuilder->build(
            $referenceId,
            $cartId,
            $productAssociations,
            $requestedPickupLocalTime,
            $requestedDeliveryLocalTime,
            $shippingEstimatedDeliveryLocalTime,
            $holdUntilDate
        );

        $this->assertEquals(['recipient_data'], $result);
    }

    public function testBuildWithDeliveryDataHandlerReturningArray()
    {
        $referenceId = 'test_reference_id';
        $cartId = 123;
        $productAssociations = ['product1', 'product2'];
        $requestedPickupLocalTime = '2023-10-01T10:00:00Z';
        $requestedDeliveryLocalTime = '2023-10-02T10:00:00Z';
        $shippingEstimatedDeliveryLocalTime = '2023-10-03T10:00:00Z';
        $holdUntilDate = '2023-10-04';

        $expectedResult = ['handled_by' => 'custom_delivery_data'];

        $this->cartIntegrationInterface->expects($this->any())
            ->method('getDeliveryData')
            ->willReturn(true);

        $deliveryDataHandler = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getData'])
            ->getMock();

        $deliveryDataHandler->expects($this->any())
            ->method('getData')
            ->with(
                $referenceId,
                $this->cartIntegrationInterface,
                $productAssociations,
                $requestedPickupLocalTime,
                $requestedDeliveryLocalTime,
                $shippingEstimatedDeliveryLocalTime,
                $holdUntilDate
            )
            ->willReturn($expectedResult);

        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->with($cartId)
            ->willReturn($this->cartIntegrationInterface);

        $recipientsBuilder = new RecipientsBuilder(
            $this->cartIntegrationRepository,
            $this->inStoreRecipientsBuilder,
            [$deliveryDataHandler]
        );

        $result = $recipientsBuilder->build(
            $referenceId,
            $cartId,
            $productAssociations,
            $requestedPickupLocalTime,
            $requestedDeliveryLocalTime,
            $shippingEstimatedDeliveryLocalTime,
            $holdUntilDate
        );

        $this->assertEquals($expectedResult, $result);
    }

    public function testBuildHandlesNoSuchEntityException()
    {
        $referenceId = 'test_reference_id';
        $cartId = 123;
        $productAssociations = ['product1', 'product2'];
        $requestedPickupLocalTime = '2023-10-01T10:00:00Z';
        $requestedDeliveryLocalTime = '2023-10-02T10:00:00Z';
        $shippingEstimatedDeliveryLocalTime = '2023-10-03T10:00:00Z';
        $holdUntilDate = '2023-10-04';

        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->with($cartId)
            ->willThrowException(new NoSuchEntityException(__('Entity not found')));

        $result = $this->recipientsBuilder->build(
            $referenceId,
            $cartId,
            $productAssociations,
            $requestedPickupLocalTime,
            $requestedDeliveryLocalTime,
            $shippingEstimatedDeliveryLocalTime,
            $holdUntilDate
        );
        $this->assertNull($result);
    }
}
