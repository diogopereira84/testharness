<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Test\Unit\Model\Resolver;

use Fedex\FuseBiddingQuote\Model\Resolver\LoadCartByQuoteId;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Cart\Data\AddProductsToCartOutputFactory;
use Magento\GraphQl\Model\Query\Context;
use Magento\GraphQl\Model\Query\ContextExtensionInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Cart\Data\Error as CartError;
use Fedex\FuseBiddingQuote\Helper\FuseBidGraphqlHelper;
use Magento\Quote\Model\QuoteFactory;
use Psr\Log\LoggerInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use PHPUnit\Framework\TestCase;

/**
 * Test class for LoadCartByQuoteId
 */
class LoadCartByQuoteIdTest extends TestCase
{
    protected $contextMock;
    protected $extensionAttributesMock;
    protected $quoteMock;
    protected $cartErrorMock;
    protected $loadCartByQuoteId;
    /**
     * @var GetCartForUser $getCartForUser
     */
    protected $getCartForUser;

    /**
     * @var CartRepositoryInterface $cartRepository
     */
    protected $cartRepository;

    /**
     * @var AddProductsToCartOutputFactory $addProductsToCartOutputFactory
     */
    protected $addProductsToCartOutputFactory;

    /**
     * @var FuseBidGraphqlHelper $fuseBidGraphqlHelper
     */
    protected $fuseBidGraphqlHelper;

    /**
     * @var QuoteFactory $quoteFactory
     */
    protected $quoteFactory;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var QuoteIdMask $quoteIdMaskResource
     */
    protected $quoteIdMaskResource;

    /**
     * Setup mock
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->fuseBidGraphqlHelper = $this->getMockBuilder(FuseBidGraphqlHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['validateCartUid'])
            ->getMock();
        
        $this->getCartForUser = $this->getMockBuilder(GetCartForUser::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMock();

        $this->cartRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->addProductsToCartOutputFactory = $this->getMockBuilder(AddProductsToCartOutputFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->onlyMethods(['getExtensionAttributes'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->extensionAttributesMock = $this->getMockBuilder(ContextExtensionInterface::class)
            ->onlyMethods(['getStore'])
            ->addMethods(['getId'])
            ->getMockForAbstractClass();

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->onlyMethods(['getData', 'getErrors'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cartErrorMock = $this->getMockBuilder(CartError::class)
            ->setMethods(['getText', 'getCode', 'getMessage', 'getCartItemPosition'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->setMethods(['create', 'load', 'getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['info'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quoteIdMaskResource = $this->getMockBuilder(QuoteIdMask::class)
            ->setMethods(['getUnmaskedQuoteId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loadCartByQuoteId = new LoadCartByQuoteId(
            $this->getCartForUser,
            $this->cartRepository,
            $this->addProductsToCartOutputFactory,
            $this->fuseBidGraphqlHelper,
            $this->quoteFactory,
            $this->logger,
            $this->quoteIdMaskResource
        );
    }

    /**
     * Test Resolve method
     *
     * @return void
     */
    public function testResolve()
    {
        $uid = '13131ASADasfasf4321';
        $args = ['uid' => $uid];
        $this->executeCommonCode();
        $this->cartErrorMock->expects($this->once())->method('getText')->willReturn('some error message');
        $result = $this->loadCartByQuoteId->resolve(
            $this->createMock(Field::class),
            $this->contextMock,
            $this->createMock(ResolveInfo::class),
            null,
            $args
        );

        $this->assertIsArray($result);
    }

    /**
     * Test Resolve with message code
     *
     * @return void
     */
    public function testResolveWithMessageCode()
    {
        $uid = '13131ASADasfasf4321';
        $args = ['uid' => $uid];

        $this->executeCommonCode();
        $this->cartErrorMock->expects($this->once())->method('getText')
        ->willReturn('Could not find a product with SKU');
        $result = $this->loadCartByQuoteId->resolve(
            $this->createMock(Field::class),
            $this->contextMock,
            $this->createMock(ResolveInfo::class),
            null,
            $args
        );

        $this->assertIsArray($result);
    }
    
    /**
     * Common code to test resolve method
     *
     * @return void
     */
    public function executeCommonCode()
    {
        $this->contextMock->expects($this->once())->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);
        $this->extensionAttributesMock->expects($this->once())->method('getStore')->willReturnSelf();
        $this->extensionAttributesMock->expects($this->once())->method('getId')->willReturn(1);
        $this->getCartForUser->expects($this->once())->method('execute')->willReturn($this->quoteMock);
        $this->addProductsToCartOutputFactory->expects($this->once())->method('create')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getData')->willReturn(true);
        $this->cartErrorMock->expects($this->once())->method('getCode')->willReturn('Test');
        $this->cartErrorMock->expects($this->once())->method('getMessage')->willReturn('Test Mesage');
        $this->cartErrorMock->expects($this->once())->method('getCartItemPosition')->willReturn(3);
        $this->quoteMock->expects($this->any())->method('getErrors')->willReturn([$this->cartErrorMock]);
        $this->quoteIdMaskResource->expects($this->once())->method('getUnmaskedQuoteId')->willReturn(13424);
        $this->quoteFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('load')->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('getCustomerId')->willReturn(3453);
    }
}
