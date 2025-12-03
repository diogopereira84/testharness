<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Test\Unit\Model\Resolver;

use Fedex\FuseBiddingQuote\Model\Resolver\InitializeNegotiableQuote;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Serialize\SerializerInterface;
use Fedex\UploadToQuote\Helper\GraphqlApiHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;
use Fedex\Delivery\Helper\QuoteDataHelper;
use Fedex\FuseBiddingQuote\Helper\FuseBidGraphqlHelper;
use Magento\NegotiableQuote\Model\PurgedContentFactory;
use Magento\NegotiableQuote\Model\PurgedContent;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Model\Quote\Integration;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Fedex\FuseBiddingQuote\Helper\FuseBidHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;

class InitializeNegotiableQuoteTest extends TestCase
{
    /**
     * @var Customer|MockObject
     */
    protected $customerMock;

    /**
     * @var InitializeNegotiableQuote|MockObject
     */
    private $initializeNegotiableQuote;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepositoryMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var GraphqlApiHelper|MockObject
     */
    private $graphqlApiHelperMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var QuoteDataHelper|MockObject
     */
    private $quoteDataHelperMock;

    /**
     * @var FuseBidGraphqlHelper|MockObject
     */
    private $fuseBidGraphqlHelperMock;

    /**
     * @var PurgedContentFactory|MockObject
     */
    private $purgedContentFactoryMock;

    /**
     * @var purgedContent|MockObject
     */
    private $purgedContentMock;

    /**
     * @var Quote|MockObject
     */
    private $quoteMock;

    /**
     * @var CartIntegrationRepositoryInterface|MockObject
     */
    private $quoteIntegrationRepositoryMock;

    /**
     * @var Integration|MockObject
     */
    private $quoteIntegrationMock;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var CustomerFactory|MockObject
     */
    protected $customerFactoryMock;

    /**
     * @var CustomerInterfaceFactory|MockObject
     */
    protected $customerInterfaceFactoryMock;

    /**
     * @var CustomerInterface|MockObject
     */
    protected $customerInterfaceMock;

    /**
     * @var Collection|MockObject
     */
    protected $customerCollectionMock;

     /**
      * @var FuseBidHelper|MockObject
      */
    protected $fuseBidHelper;

    /**
     * Setup function
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quoteRepositoryMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['serialize'])
            ->getMockForAbstractClass();

        $this->graphqlApiHelperMock = $this->getMockBuilder(GraphqlApiHelper::class)
            ->setMethods(['getQuoteContactInfo', 'getQuoteLineItems', 'getFxoAccountNumberOfQuote',
                'getQuoteCompanyName', 'getQuoteNotes','getQuoteInfo','getRateResponse'
                ,'addLogsForGraphqlApi'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMockForAbstractClass();

        $this->quoteDataHelperMock = $this->getMockBuilder(QuoteDataHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['createNegotiableQuote'])
            ->getMock();

        $this->fuseBidGraphqlHelperMock = $this->getMockBuilder(FuseBidGraphqlHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'validateToggleConfig',
                'getQuoteIdFromArgs',
                'isNegotiableQuote',
                'updateQuoteWithCustomerInfo',
                'updateDummyCustomerInQuote',
                'getCustomerByRetailCustomerId',
                'saveRetailCustomerId',
                'validateContactInfoFixConfig',
                'validateComment',
                'saveNegotiableQuoteComment'
            ])
            ->getMock();

        $this->purgedContentFactoryMock = $this->getMockBuilder(PurgedContentFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->quoteIntegrationRepositoryMock = $this->getMockBuilder(CartIntegrationRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getByQuoteId'])
            ->getMockForAbstractClass();

        $this->quoteIntegrationMock = $this->getMockBuilder(Integration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->purgedContentMock = $this->createMock(PurgedContent::class);

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(
                [
                    'getExtensionAttributes',
                    'getNegotiableQuote',
                    'getCustomerFirstname',
                    'getCustomerMiddlename',
                    'getCustomerLastname',
                    'getCustomerEmail',
                    'getStoreId'
                ]
            )
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
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerInterfaceFactoryMock = $this->getMockBuilder(CustomerInterfaceFactory::class)
            ->setMethods(
                [
                    'create',
                    'getId',
                    'getFirstname',
                    'getLastname',
                    'getEmail',
                    'getCustomAttribute'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerInterfaceMock = $this->getMockBuilder(CustomerInterface::class)
            ->setMethods(
                [
                    'getId',
                    'getFirstname',
                    'getLastname',
                    'getEmail',
                    'setFirstname',
                    'setLastName',
                    'setEmail'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerMock = $this->getMockBuilder(customer::class)
            ->disableOriginalConstructor()
            ->setMethods([
               'create',
               'setFirstname',
               'setLastName',
               'setEmail',
               'save',
               'getId'
               ])
            ->getMockForAbstractClass();

        $this->customerCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSelect', 'where','getFirstItem'])
            ->getMock();

        $this->fuseBidHelper = $this->getMockBuilder(FuseBidHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isToggleTeamMemberInfoEnabled'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->initializeNegotiableQuote = $objectManagerHelper->getObject(
            InitializeNegotiableQuote::class,
            [
                'quoteRepository' => $this->quoteRepositoryMock,
                'serializer' => $this->serializerMock,
                'graphqlApiHelper' => $this->graphqlApiHelperMock,
                'logger' => $this->loggerMock,
                'quoteDataHelper' => $this->quoteDataHelperMock,
                'fuseBidGraphqlHelper' => $this->fuseBidGraphqlHelperMock,
                'purgedContentFactory' => $this->purgedContentFactoryMock,
                'cartIntegrationRepository' => $this->quoteIntegrationRepositoryMock,
                'customerRepository' => $this->customerRepositoryMock,
                'customer' => $this->customerFactoryMock,
                'customerInterfaceFactory' => $this->customerInterfaceFactoryMock,
                'fuseBidHelper' => $this->fuseBidHelper
            ]
        );
    }

    /**
     * Test Resolve  method
     *
     * @return void
     */
    public function testResolve()
    {
        $args = ['uid' => '123','comment' => 'test'];
        $quoteId = 1;
        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('validateToggleConfig')
            ->willReturn(true);
        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('isNegotiableQuote')
            ->willReturn(false);
        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('getQuoteIdFromArgs')
            ->with($args['uid'])
            ->willReturn($quoteId);
        $this->quoteRepositoryMock->expects($this->any())
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->quoteMock);
        $this->quoteIntegrationRepositoryMock->expects($this->any())
            ->method('getByQuoteId')
            ->willReturn($this->quoteIntegrationMock);
        $this->testCreateDummyCustomer();
        $this->quoteIntegrationMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn(123);
        $this->purgedContentFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->purgedContentMock);
        $quoteResponse = [
            'quote_id' => $quoteId,
            'quote_status' => 'created',
            'hub_centre_id' => '123',
            'location_id' => '123',
            'quote_creation_date' => '2024-01-01',
            'quote_updated_date' => '2024-01-02',
            'quote_submitted_date' => '2024-01-02',
            'quote_expiration_date' => '2024-01-10',
            'contact_info' => null,
            'rateSummary' => [],
            'line_items' => [],
            'fxo_print_account_number' => '',
            'activities' => []
        ];
        $this->graphqlApiHelperMock->expects($this->any())
            ->method('getQuoteInfo')
            ->with($this->quoteMock)
            ->willReturn($quoteResponse);

        $this->fuseBidHelper->expects($this->any())
            ->method('isToggleTeamMemberInfoEnabled')
            ->willReturn(true);

        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('validateComment')
            ->with('test')
            ->willReturn(true);

        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('saveNegotiableQuoteComment')
            ->with($quoteId, 'test')
            ->willReturn(true);

        $resolveInfoMock = $this->createMock(ResolveInfo::class);
        $fieldMock = $this->createMock(Field::class);
        $result = $this->initializeNegotiableQuote->resolve($fieldMock, null, $resolveInfoMock, null, $args);

        $this->assertArrayHasKey('quote_id', $result);
        $this->assertEquals($quoteResponse['quote_id'], $result['quote_id']);

        $this->assertArrayHasKey('quote_status', $result);
        $this->assertEquals($quoteResponse['quote_status'], $result['quote_status']);

        $this->assertArrayHasKey('hub_centre_id', $result);
        $this->assertEquals($quoteResponse['hub_centre_id'], $result['hub_centre_id']);

        $this->assertArrayHasKey('location_id', $result);
        $this->assertEquals($quoteResponse['location_id'], $result['location_id']);
    }

    public function testResolveException()
    {
        $args = ['uid' => '123'];
        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('validateToggleConfig')
            ->willReturn(false);
        $this->expectException(GraphQlInputException::class);
        $resolveInfoMock = $this->createMock(ResolveInfo::class);
        $fieldMock = $this->createMock(Field::class);
        $this->assertEquals(
            'Fuse Bidding toggle is not enabled',
            $this->initializeNegotiableQuote->resolve(
                $fieldMock,
                null,
                $resolveInfoMock,
                null,
                $args
            )
        );
    }

    /**
     * Test Resolve  method
     *
     * @return void
     */
    public function testResolveWithTeamMemberInfoDisabled()
    {
        $args = ['uid' => '123'];
        $quoteId = 1;
        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('validateToggleConfig')
            ->willReturn(true);
        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('isNegotiableQuote')
            ->willReturn(false);
        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('getQuoteIdFromArgs')
            ->with($args['uid'])
            ->willReturn($quoteId);
        $this->quoteRepositoryMock->expects($this->any())
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->quoteMock);
        $this->quoteIntegrationRepositoryMock->expects($this->any())
            ->method('getByQuoteId')
            ->willReturn($this->quoteIntegrationMock);
        $this->testCreateDummyCustomer();
        $this->quoteIntegrationMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn(123);
        $this->purgedContentFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->purgedContentMock);
        $quoteResponse = [
            'quote_id' => $quoteId,
            'quote_status' => 'created',
            'hub_centre_id' => '123',
            'location_id' => '123',
            'quote_creation_date' => '2024-01-01',
            'quote_updated_date' => '2024-01-02',
            'quote_submitted_date' => '2024-01-02',
            'quote_expiration_date' => '2024-01-10',
            'contact_info' => null,
            'rateSummary' => [],
            'line_items' => [],
            'fxo_print_account_number' => '',
            'activities' => []
        ];
        $this->graphqlApiHelperMock->expects($this->any())
            ->method('getQuoteInfo')
            ->with($this->quoteMock)
            ->willReturn($quoteResponse);

        $this->fuseBidHelper->expects($this->any())
            ->method('isToggleTeamMemberInfoEnabled')
            ->willReturn(false);

        $resolveInfoMock = $this->createMock(ResolveInfo::class);
        $fieldMock = $this->createMock(Field::class);
        $result = $this->initializeNegotiableQuote->resolve($fieldMock, null, $resolveInfoMock, null, $args);

        $expectedResponse = [
            'quote_id' => $quoteId,
            'quote_status' => 'created',
            'hub_centre_id' => '123',
            'location_id' => '123',
            'quote_creation_date' => '2024-01-01',
            'quote_updated_date' => '2024-01-02',
            'quote_submitted_date' => '2024-01-02',
            'quote_expiration_date' => '2024-01-10',
            'contact_info' => null,
            'rateSummary' => [
                'grossAmount' => null,
                'discounts' => null,
                'totalDiscountAmount' => null,
                'netAmount' => null,
                'taxableAmount' => null,
                'taxAmount' => null,
                'totalAmount' => null,
                'totalFees' => null,
                'productsTotalAmount' => null,
                'deliveriesTotalAmount' => null,
                'estimatedVsActual' => null
            ],
            'line_items' => null,
            'fxo_print_account_number' => null,
            'activities' => [],
            'is_bid' => 1,
            'lte_identifier' => null
        ];

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test create dummy customer
     *
     * @return void
     */
    public function testCreateDummyCustomer()
    {
        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('setFirstname')->with('Null')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setLastname')->with('Null')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('setEmail')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('save')->with($this->customerMock)->willReturnSelf();
        $this->assertTrue(true);
    }

    /**
     * Test the resolve method with exception.
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testResolveThrowException()
    {
        $errorMsg = 'Some error message';
        $args = ['uid' => '123'];
        $quoteId = 1;
        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('validateToggleConfig')
            ->willReturn(true);
        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('getQuoteIdFromArgs')
            ->with($args['uid'])
            ->willReturn($quoteId);
        $this->quoteRepositoryMock->expects($this->any())
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->quoteMock);
        $this->quoteIntegrationRepositoryMock->expects($this->any())
            ->method('getByQuoteId')
            ->willThrowException(new \Exception($errorMsg));
        ;
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage($errorMsg);

        $this->initializeNegotiableQuote->resolve(
            $this->createMock(Field::class),
            null,
            $this->createMock(ResolveInfo::class),
            null,
            $args
        );
    }

    /**
     * Test Process customer for ProcessCustomerForQuoteWithExistingRetailCustomer
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testProcessCustomerForQuoteWithExistingRetailCustomer()
    {
        $quoteId = 123;

        $this->quoteRepositoryMock->expects($this->any())
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->quoteMock);
        $this->quoteIntegrationRepositoryMock->expects($this->any())
            ->method('getByQuoteId')
            ->willReturn($this->quoteIntegrationMock);
        $this->quoteIntegrationMock->expects($this->any())
            ->method('getRetailCustomerId')
            ->willReturn('123');
        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('getCustomerByRetailCustomerId')
            ->willReturn($this->customerMock);

        $this->assertNull($this->initializeNegotiableQuote->processCustomerForQuote($quoteId, $this->quoteMock));
    }

      /**
       * Test Process customer for testProcessCustomerForQuotExceptionInRetailCustomerId
       *
       * @return void
       * @throws GraphQlInputException
       */
    public function testProcessCustomerForQuotExceptionInRetailCustomerId()
    {
        $quoteId = 123;
        $errorMsg = 'Some error message';
        $this->quoteRepositoryMock->expects($this->any())
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->quoteMock);
        $this->quoteIntegrationRepositoryMock->expects($this->any())
            ->method('getByQuoteId')
            ->willReturn($this->quoteIntegrationMock);
        $this->quoteIntegrationMock->expects($this->any())
            ->method('getRetailCustomerId')
            ->willReturn('123');
        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('getCustomerByRetailCustomerId')
            ->willThrowException(new \Exception($errorMsg));
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($errorMsg);

        $this->initializeNegotiableQuote->processCustomerForQuote($quoteId, $this->quoteMock);
    }

      /**
       * Test Process customer for testProcessCustomerForQuotExceptionInRetailCustomerId
       *
       * @return void
       * @throws GraphQlInputException
       */
    public function testProcessCustomerForQuoteExceptionInRetailCustomerId()
    {
        $quoteId = 123;
        $exception = new NoSuchEntityException(
            __('No such entity found with quote_id = %1', $quoteId)
        );
        $this->quoteRepositoryMock->expects($this->any())
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->quoteMock);
        $this->quoteIntegrationRepositoryMock->expects($this->any())
            ->method('getByQuoteId')
            ->willThrowException($exception);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error in Fetching Quote Integration:'));

        $this->testCreateDummyCustomer();

        $this->initializeNegotiableQuote->processCustomerForQuote($quoteId, $this->quoteMock);
    }

    /**
     * Test method testCreateCustomerFromRetailCustomerId
     *
     * @return void
     */
    public function testCreateCustomerFromRetailCustomerId()
    {
        $retailCustomerId = 12345;
        $this->quoteMock->expects($this->any())
            ->method('getCustomerFirstname')
            ->willReturn('Dummy');
        $this->quoteMock->expects($this->any())
            ->method('getCustomerMiddlename')
            ->willReturn('Middle');
        $this->quoteMock->expects($this->any())
            ->method('getCustomerLastname')
            ->willReturn('Customer');
        $this->quoteMock->expects($this->any())
            ->method('getCustomerEmail')
            ->willReturn('john.doe@example.com');
        $this->quoteMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn(123);
        $this->customerRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->customerMock);
        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('getCustomerByRetailCustomerId')
            ->with($retailCustomerId)
            ->willReturn('');
        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('saveRetailCustomerId')
            ->willReturn($this->customerMock);
        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('validateContactInfoFixConfig')
            ->willReturn(true);
        $this->customerInterfaceFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerInterfaceMock);
        $this->customerInterfaceMock->expects($this->any())
            ->method('setFirstname')
            ->with('Dummy')
            ->willReturnSelf();
        $this->customerInterfaceMock->expects($this->any())
            ->method('setMiddlename')
            ->with('Middle')
            ->willReturnSelf();
        $this->customerInterfaceMock->expects($this->any())
            ->method('setLastname')
            ->with('Customer')
            ->willReturnSelf();
        $this->customerInterfaceMock->expects($this->any())
            ->method('setEmail')
            ->with('12345@fedex.com')
            ->willReturnSelf();
        $this->customerInterfaceMock->expects($this->any())
            ->method('setCustomAttribute')
            ->withConsecutive(
                ['secondary_email', 'john.doe@example.com'],
                ['retail_customer_id', $retailCustomerId]
            )
            ->willReturnSelf();
        $this->customerRepositoryMock->expects($this->any())
            ->method('save')
            ->with($this->customerInterfaceMock)
            ->willReturn($this->customerInterfaceMock);

        $this->assertEquals($this->customerInterfaceMock, $this->initializeNegotiableQuote->createCustomerFromRetailCustomerId($retailCustomerId, $this->quoteMock));
    }

    /**
     * Test method testCreateCustomerFromRetailCustomerId
     *
     * @return void
     */
    public function testCreateCustomerFromRetailCustomerIdException()
    {
        $retailCustomerId = 12345;
        $errorMsg = "Customer not found";
        $this->customerRepositoryMock->expects($this->any())
            ->method('get')
            ->willThrowException(new NoSuchEntityException());
        $this->quoteMock->expects($this->any())
            ->method('getCustomerFirstname')
            ->willReturn('Dummy');
        $this->quoteMock->expects($this->any())
            ->method('getCustomerMiddlename')
            ->willReturn('Middle');
        $this->quoteMock->expects($this->any())
            ->method('getCustomerLastname')
            ->willReturn('Customer');
        $this->quoteMock->expects($this->any())
            ->method('getCustomerEmail')
            ->willReturn('12345@fedex.com');
        $this->quoteMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn(123);
        $this->customerInterfaceFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerInterfaceMock);
        $this->customerInterfaceMock->expects($this->any())
            ->method('setFirstname')
            ->with('Dummy')
            ->willReturnSelf();
        $this->customerInterfaceMock->expects($this->any())
            ->method('setMiddlename')
            ->with('Middle')
            ->willReturnSelf();
        $this->customerInterfaceMock->expects($this->any())
            ->method('setLastname')
            ->with('Customer')
            ->willReturnSelf();
        $this->customerInterfaceMock->expects($this->any())
            ->method('setEmail')
            ->with('12345@fedex.com')
            ->willReturnSelf();
        $this->customerInterfaceMock->expects($this->any())
            ->method('setCustomAttribute')
            ->withConsecutive(
                ['secondary_email', '12345@fedex.com'],
                ['retail_customer_id', $retailCustomerId]
            )
            ->willReturnSelf();
        $this->customerRepositoryMock->expects($this->any())
            ->method('save')
            ->with($this->customerInterfaceMock)
            ->willReturn($this->customerInterfaceMock);
        $this->assertEquals($this->customerInterfaceMock, $this->initializeNegotiableQuote->createCustomerFromRetailCustomerId($retailCustomerId, $this->quoteMock));
    }
}
