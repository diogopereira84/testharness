<?php
/**
 * @category     Fedex
 * @package      Fedex_SubmitOrderSidebar
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model\RateQuoteApi;

use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\FXOPricing\Model\RateQuoteApi\InStoreRecipientsBuilder;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;

class InStoreRecipientsBuilderTest extends TestCase
{
    /**
     * @var CartIntegrationRepositoryInterface|MockObject
     */
    private $cartIntegrationRepository;

    /**
     * @var InStoreRecipientsBuilder
     */
    private $inStoreRecipientsBuilder;

    /**
     * @var CartIntegrationRepositoryInterface|MockObject
     */
    private $instoreConfig;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->cartIntegrationRepository = $this->createMock(CartIntegrationRepositoryInterface::class);
        $this->instoreConfig = $this->createMock(InstoreConfig::class);
        $this->inStoreRecipientsBuilder = new InStoreRecipientsBuilder(
            $this->cartIntegrationRepository,
            $this->instoreConfig
        );
    }

    /**
     * @return void
     */
    public function testBuild()
    {
        $referenceId = '12345';
        $cartId = '54321';
        $productAssociations = [
            'product1',
            'product2'
        ];
        $integration = $this->getMockBuilder(CartIntegrationRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getStoreId'])
            ->onlyMethods(['getByQuoteId'])
            ->getMockForAbstractClass();
        $integration->method('getByQuoteId')->willReturn($integration);
        $integration->method('getStoreId')->willReturn('storeId');
        $this->cartIntegrationRepository->method('getByQuoteId')->willReturn($integration);

        $expectedResult = [
            'arrRecipients' => [
                0 => [
                    'contact' => null,
                    'reference' => $referenceId,
                    'pickUpDelivery' => [
                        'location' => [
                            'id' => 'storeId',
                        ],
                        'requestedPickupLocalTime' => null
                    ],
                    'productAssociations' => $productAssociations,
                ],
            ]
        ];

        $result = $this->inStoreRecipientsBuilder->build($referenceId, $cartId, $productAssociations, null);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return void
     */
    public function testBuildIfIsDeliveryDatesFieldsEnabledIsTrue()
    {
        $referenceId = '12345';
        $cartId = '54321';
        $productAssociations = [
            'product1',
            'product2'
        ];
        $this->instoreConfig->method('isDeliveryDatesFieldsEnabled')->willReturn(true);
        $integration = $this->getMockBuilder(CartIntegrationRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getStoreId'])
            ->onlyMethods(['getByQuoteId'])
            ->getMockForAbstractClass();
        $integration->method('getByQuoteId')->willReturn($integration);
        $integration->method('getStoreId')->willReturn('storeId');
        $this->cartIntegrationRepository->method('getByQuoteId')->willReturn($integration);

        $expectedResult = [
            'arrRecipients' => [
                0 => [
                    'contact' => null,
                    'reference' => $referenceId,
                    'pickUpDelivery' => [
                        'location' => [
                            'id' => 'storeId',
                        ],
                        'requestedPickupLocalTime' => null,
                        'holdUntilDate' => null
                    ],
                    'productAssociations' => $productAssociations,

                    'requestedDeliveryLocalTime' => null,
                ],
            ]
        ];

        $result = $this->inStoreRecipientsBuilder->build($referenceId, $cartId, $productAssociations, null);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return void
     */
    public function testBuildNoSuchEntityException()
    {
        $referenceId = '12345';
        $cartId = '54321';
        $productAssociations = [
            'product1',
            'product2'
        ];
        $this->cartIntegrationRepository->method('getByQuoteId')->willThrowException(new NoSuchEntityException());
        $this->assertNull($this->inStoreRecipientsBuilder->build($referenceId, $cartId, $productAssociations, null));
    }

    /**
     * @return void
     */
    public function testBuildReturnNull()
    {
        $referenceId = '12345';
        $cartId = '54321';
        $productAssociations = [
            'product1',
            'product2'
        ];
        $this->cartIntegrationRepository->method('getByQuoteId')->willReturn(false);
        $this->assertNull($this->inStoreRecipientsBuilder->build($referenceId, $cartId, $productAssociations, null));
    }
}
