<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Test\Unit\Helper;

use Fedex\FuseBiddingQuote\Helper\FuseBidGraphqlHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use PHPUnit\Framework\TestCase;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\Registry;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\Customer;
use Psr\Log\LoggerInterface;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\NegotiableQuote\Model\CommentFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class FuseBidGraphqlHelperTest extends TestCase
{

    protected $customerMock;
    protected $customerCollectionMock;
    /**
     * @var FuseBidGraphqlHelper|MockObject
     */
    private $fuseBidGraphqlHelper;

     /**
      * @var context|MockObject
      */
    private $contextMock;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var CustomeFactory|MockObject
     */
    private $customerFactoryMock;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    protected $quoteRepositoryMock;

    /**
     * @var QuoteIdMask|MockObject
     */
    private $quoteIdMaskResourceMock;

    /**
     * @var QuoteFactory|MockObject
     */
    protected $quoteFactoryMock;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    /**
     * @var GetCartForUser|MockObject
     */
    protected $getCartForUserMock;

    /**
     * @var Quote|MockObject
     */
    protected $quoteMock;

     /**
      * @var cartExtensionInterface|MockObject
      */
    protected $cartExtensionInterfaceMock;

     /**
      * @var negotiableQuoteInterface|MockObject
      */
    protected $negotiableQuoteInterfaceMock;

     /**
      * @var LoggerInterface|MockObjet
      */
    private $loggerMock;

    /**
     * @var FuseBidViewModel|MockObjet
     */
    private $fuseBidViewModelMock;

    /**
     * @var ModuleDataSetupInterface|MockObject
     */
    protected $moduleDataSetupMock;

    /**
     * @var CommentFactory|MockObject
     */
    protected $commentFactory;

    /**
     * Setup function
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->fuseBidViewModelMock = $this->getMockBuilder(FuseBidViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['isFuseBidToggleEnabled','isContactInfoFix'])
            ->getMock();
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->setMethods(
                [
                    'save',
                    'getById'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerFactoryMock = $this->getMockBuilder(CustomerFactory::class)
            ->setMethods(
                [
                    'create',
                    'getCollection',
                    'load'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quoteRepositoryMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->quoteIdMaskResourceMock = $this->getMockBuilder(QuoteIdMask::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUnmaskedQuoteId'])
            ->getMock();

        $this->quoteFactoryMock = $this->getMockBuilder(QuoteFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['register'])
            ->getMockForAbstractClass();

        $this->getCartForUserMock = $this->getMockBuilder(GetCartForUser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteMock = $this->getMockBuilder(Quote::class)
        ->disableOriginalConstructor()
        ->setMethods([
            'getIsBid',
            'getExtensionAttributes',
            'getCustomerId',
            'load',
            'setCustomerFirstname',
            'setCustomerLastname',
            'setCustomerEmail',
            'setCustomerIsGuest',
            'setCustomerId',
            'setCustomerGroupId'
        ])
        ->getMock();

        $this->cartExtensionInterfaceMock = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();

        $this->negotiableQuoteInterfaceMock = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMockForAbstractClass();

        $this->customerMock = $this->getMockBuilder(customer::class)
        ->disableOriginalConstructor()
        ->setMethods([
            'setFirstName',
            'setLastname',
            'setEmail',
            'getCollection',
            'getId',
            'getFirstname',
            'getLastname',
            'getGroupId',
            'load',
            'delete'
        ])
        ->getMockForAbstractClass();

        $this->customerCollectionMock =  $this->getMockBuilder(collection::class)
        ->disableOriginalConstructor()
        ->setMethods(['where', 'getSelect', 'getFirstItem'])
        ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMockForAbstractClass();

        $this->moduleDataSetupMock = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->setMethods(
                [
                    'getTable',
                    'getConnection',
                    'update',
                    'endSetup'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->commentFactory = $this->getMockBuilder(CommentFactory::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'create',
                'setParentId',
                'setComment',
                'save',
                'getParentId',
                'getComment',
                'setCreatorType',
                'setCreatorId',
                'setType'
            ])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->fuseBidGraphqlHelper = $objectManagerHelper->getObject(
            FuseBidGraphqlHelper::class,
            [
                'context' => $this->contextMock,
                'customer' => $this->customerFactoryMock,
                'customerRepository' => $this->customerRepositoryMock,
                'quoteRepository' => $this->quoteRepositoryMock,
                'quoteIdMaskResource' => $this->quoteIdMaskResourceMock,
                'quoteFactory' => $this->quoteFactoryMock,
                'registry' => $this->registryMock,
                'getCartForUser' => $this->getCartForUserMock,
                'logger' => $this->loggerMock,
                'fuseBidViewModel' => $this->fuseBidViewModelMock,
                'moduleDataSetup' => $this->moduleDataSetupMock,
                'negotiableQuoteCommentFactory' => $this->commentFactory
            ]
        );
    }

    /**
     * Test function for ValidateCartUidThrowsException
     *
     * @return void
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    public function testValidateCartUidThrowsException()
    {
        $args = [];
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage("uid value must be specified.");
        $this->fuseBidGraphqlHelper->validateCartUid($args);
    }

    /**
     * Test function for validateToggleConfig for true case
     *
     * @return void
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    public function testValidateToggleConfigTruecase()
    {
        $this->fuseBidViewModelMock->expects($this->once())
            ->method('isFuseBidToggleEnabled')
            ->willReturn(true);
        $this->fuseBidGraphqlHelper->validateToggleConfig();
    }

    /**
     * Test function for validateToggleConfig
     *
     * @return void
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    public function testValidateToggleConfig()
    {
        $this->fuseBidViewModelMock->expects($this->once())
            ->method('isFuseBidToggleEnabled')
            ->willReturn(false);
        $this->fuseBidGraphqlHelper->validateToggleConfig();
    }

    /**
     * Test function for validateContactInfoFixConfig for true case
     *
     * @return void
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    public function testValidateContactInfoFixConfigTruecase()
    {
        $this->fuseBidViewModelMock->expects($this->once())
            ->method('isContactInfoFix')
            ->willReturn(true);

        $this->assertEquals(true, $this->fuseBidGraphqlHelper->validateContactInfoFixConfig());
    }

    /**
     * Test function for validateContactInfoFixConfig
     *
     * @return void
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    public function testValidateContactInfoFixConfig()
    {
        $this->fuseBidViewModelMock->expects($this->once())
            ->method('isContactInfoFix')
            ->willReturn(false);
            $this->assertEquals(false, $this->fuseBidGraphqlHelper->validateContactInfoFixConfig());
    }

    /**
     * Test function for validateTemplate
     *
     * @return void
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    public function testValidateTemplate()
    {
        $args = [];
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage("template value must be specified.");
        $this->fuseBidGraphqlHelper->validateTemplate($args);
    }

    /**
     * Test function for testQuoteIdMaskResource
     *
     * @return void
     */
    public function testQuoteIdMaskResource()
    {
        $quoteUid = 'test-uid';
        $quoteId = 123;
        $this->quoteIdMaskResourceMock
            ->method('getUnmaskedQuoteId')
            ->with($quoteUid)
            ->willReturn($quoteId);
        $result = $this->fuseBidGraphqlHelper->getQuoteIdFromArgs($quoteUid);

        $this->assertEquals($quoteId, $result);
    }

    /**
     * Common code to call
     *
     * @return void
     */
    public function commonCodeTocall($args)
    {

        $quoteId = 101;
        $retailCustomerId = 123;
        $customerFirstname = 'John';
        $customerLastname = 'Doe';
        $customerGroupId = 3;
        $this->quoteIdMaskResourceMock->expects($this->once())
            ->method('getUnmaskedQuoteId')
            ->with($args['cart_id'])
            ->willReturn($quoteId);
        $this->quoteRepositoryMock->expects($this->once())
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->quoteMock);
        $this->quoteMock->method('getIsBid')->willReturn(true);
        $this->quoteMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->cartExtensionInterfaceMock);
        $this->cartExtensionInterfaceMock->expects($this->any())
        ->method('getNegotiableQuote')->willReturn($this->negotiableQuoteInterfaceMock);
        $this->negotiableQuoteInterfaceMock->expects($this->any())
        ->method('getData')->willReturnSelf();
        $this->customerFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerMock);
        $this->customerMock->expects($this->once())
            ->method('getCollection')
            ->willReturn($this->customerCollectionMock);
        $this->customerCollectionMock->expects($this->once())
        ->method('getSelect')
        ->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())
        ->method('where')
        ->with("retail_customer_id = ?", $retailCustomerId)
        ->willReturnSelf();
        $this->customerCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->customerMock);
        $this->quoteFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $this->customerMock->expects($this->any())->method('getFirstname')->willReturn($customerFirstname);
        $this->customerMock->expects($this->any())->method('getLastname')->willReturn($customerLastname);
        $this->customerMock->expects($this->any())->method('getGroupId')->willReturn($customerGroupId);
        $this->quoteMock->expects($this->any())->method('setCustomerEmail')->with(null);
        $this->quoteMock->expects($this->any())->method('setCustomerIsGuest')->with(0);
        $this->quoteMock->expects($this->any())->method('setCustomerGroupId')->willReturn(null);
    }

    /**
     * Test function for UpdateCartAndCustomerForFuseBidWithExistingCustomer
     *
     * @return void
     */
    public function testUpdateCartAndCustomerForFuseBidWithExistingCustomer()
    {
        $args = [
            'cart_id' => '123abc',
            'contact_information' => [
                'retail_customer_id' => 123,
                'firstname' => 'test',
                'lastname' => 'test',
                'email' =>'test@email.com'
            ]
        ];
        $customerId = 123;
        $this->commonCodeTocall($args);
        $this->customerRepositoryMock->expects($this->any())
            ->method('getById')
            ->with(123)
            ->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())
            ->method('getId')
            ->willReturn(123);
        $this->quoteFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $this->quoteMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn(123);
        $this->quoteMock->expects($this->any())->method('setCustomerId')->with($customerId);

        $result = $this->fuseBidGraphqlHelper->updateCartAndCustomerForFuseBid($args);
    }

    /**
     * Test function for UpdateCartAndCustomerForFuseBidWithExistingCustomer
     *
     * @return void
     */
    public function testUpdateCartAndCustomerForFuseBidWithCustomerNull()
    {
        $args = [
            'cart_id' => '123abc',
            'contact_information' => [
                'retail_customer_id' => 123,
                'firstname' => 'John',
                'lastname' => 'test',
                'email' =>'test@email.com'
            ]
        ];
        $this->commonCodeTocall($args);
        $customerMock = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerInterface::class)
            ->getMock();
        $customerMock->expects($this->any())->method('getId')->willReturn(null);
        $this->customerRepositoryMock->expects($this->any())
        ->method('getById')
        ->willReturn($customerMock);
        $this->customerRepositoryMock->expects($this->any())
            ->method('save');
        $this->customerMock->expects($this->any())
            ->method('getId')
            ->willReturn(123);
        $connectionMock = $this->createMock(AdapterInterface::class);
        $this->moduleDataSetupMock->expects($this->any())
            ->method('getTable')
            ->with('customer_entity')
            ->willReturn('customer_entity');
        $this->moduleDataSetupMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($connectionMock);
        $connectionMock->expects($this->any())
            ->method('update')
            ->with(
                'customer_entity',
                ['retail_customer_id' => '123'],
                ['entity_id = ?' => null]
            );
        $this->quoteFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $this->quoteMock->expects($this->any())
            ->method('getCustomerId')
            ->willReturn(123);
        $this->quoteMock->expects($this->any())->method('setCustomerId')->with(null);
        $this->customerFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $result = $this->fuseBidGraphqlHelper->updateCartAndCustomerForFuseBid($args);
    }

     /**
      * Test function for testGetCartForBidQuoteWhenQuoteIsBid
      *
      * @return void
      */
    public function testGetCartForBidQuoteWhenQuoteIsBid()
    {
        $cartId = 'test_cart_id';
        $storeId = 1;
        $customerId = 123;
        $this->quoteFactoryMock->expects($this->any())
        ->method('create')
        ->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())
        ->method('load')
        ->willReturnSelf();
        $this->quoteMock->method('getIsBid')->willReturn(true);
        $this->quoteMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        // Mock the getCartForUser->execute() call
        $this->getCartForUserMock->expects($this->once())
            ->method('execute')
            ->with($cartId, $customerId, $storeId)
            ->willReturn($this->quoteMock);
        $result = $this->fuseBidGraphqlHelper->getCartForBidQuote($cartId, $storeId);
    }

    /**
     * Test function for testGetCartForBidQuoteWhenQuoteIsNotBid
     *
     * @return void
     */
    public function testGetCartForBidQuoteWhenQuoteIsNotBid()
    {
        $cartId = 'test_cart_id';
        $storeId = 1;
        $this->quoteMock->method('getIsBid')->willReturn(false);
        $this->quoteFactoryMock->expects($this->any())
        ->method('create')
        ->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())
        ->method('load')
        ->willReturnSelf();
        $this->getCartForUserMock->expects($this->once())
            ->method('execute')
            ->with($cartId, null, $storeId)
            ->willReturn($this->quoteMock);

        $result = $this->fuseBidGraphqlHelper->getCartForBidQuote($cartId, $storeId);
    }

    /**
     * Test function for testUpdateDummyCustomerInfoInQuote
     *
     * @return void
     */
    public function testUpdateDummyCustomerInfoInQuote()
    {
        $this->quoteFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->customerRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->customerMock);
        
            $this->assertTrue(true);

        $result = $this->fuseBidGraphqlHelper->updateDummyCustomerInQuote($this->quoteMock, $this->customerMock);
    }

    /**
     * Test function for saveNegotiableQuoteComment
     *
     * @return void
     */
    public function testSaveNegotiableQuoteComment()
    {
        $quoteId = 1;
        $commentText = 'test';
        $this->commentFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->commentFactory->expects($this->once())
            ->method('setParentId')
            ->with($this->equalTo($quoteId));

        $this->commentFactory->expects($this->once())
            ->method('setComment')
            ->with($this->equalTo($commentText));

        $this->commentFactory->expects($this->once())
            ->method('setCreatorType')
            ->with($this->equalTo(2));

        $this->commentFactory->expects($this->once())
            ->method('setCreatorId')
            ->with($this->equalTo(2));

        $this->commentFactory->expects($this->once())
            ->method('setType');

        $this->commentFactory->expects($this->once())
            ->method('save');

        $this->assertNull($this->fuseBidGraphqlHelper->saveNegotiableQuoteComment($quoteId, $commentText));
    }

    /**
     * Test function for testValidateCommentValid
     *
     * @return void
     */
    public function testValidateCommentThrowsException()
    {
        $validComment = "";
        $this->expectException(GraphQlInputException::class);
        $this->assertEquals('comment cannot be empty', $this->fuseBidGraphqlHelper->validateComment($validComment));
    }
}
