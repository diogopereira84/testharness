<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 FedEx
 * @author       Yash Rajeshbhai Solanki <yash.solanki.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Resolver;

use Exception;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\Cart\Model\Quote\Integration\Command\SaveRetailCustomerIdInterface;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\CartGraphQl\Model\Resolver\UpdateGuestCartContactInformation;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand as RequestCommand;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Address\Item;
use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;
use Magento\GraphQl\Model\Query\ContextExtensionInterface;
use Magento\Quote\Model\ResourceModel\Quote\Collection as QuoteCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Framework\App\Request\Http;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Fedex\CartGraphQl\Model\FedexAccountNumber\SetFedexAccountNumber;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\FuseBiddingQuote\Helper\FuseBidGraphqlHelper;
use Fedex\CartGraphQl\Model\Checkout\Cart;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Unit test for UpdateGuestCartContactInformation GraphQL resolver
 *
 * Tests the functionality of the resolver that handles guest cart contact information updates.
 * The test file is organized into the following sections:
 *
 * 1. Basic setup and test configuration in setUp()
 * 2. Utility methods for creating test data and mock objects
 * 3. Tests for standard behavior with various configurations
 * 4. Tests for edge cases and error handling
 *
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 */
class UpdateGuestCartContactInformationTest extends TestCase
{
    protected NewRelicHeaders|MockObject $newRelicHeaders;
    protected ContextInterface|MockObject $contextMock;
    protected ContextExtensionInterface|MockObject $extensionAttributesMock;
    protected LoggerHelper|MockObject $loggerHelper;
    protected FXORateQuote|MockObject $fxoRateQuoteMock;
    protected SetFedexAccountNumber|MockObject $setFedexAccountNumberMock;
    private UpdateGuestCartContactInformation|MockObject $updateGuestCartContactInformation;
    private Field|MockObject $field;
    private ResolveInfo|MockObject $resolveInfo;
    private StoreInterface|MockObject $storeMock;
    private Quote|MockObject $cartMock;
    private CartRepositoryInterface|MockObject $cartRepositoryMock;
    private ContextExtensionInterface|MockObject $contextExtensionMock;
    private QuoteCollection|MockObject $quoteCollection;
    private Item|MockObject $quoteAddressItemMock;
    private RequestCommandFactory|MockObject $requestCommandFactoryMock;
    private ValidationBatchComposite|MockObject $validationCompositeMock;
    private SaveRetailCustomerIdInterface|MockObject $saveRetailCustomerIdMock;
    private CartIntegrationRepositoryInterface|MockObject $cartIntegrationRepositoryMock;
    private CartIntegrationInterface|MockObject $cartIntegrationMock;
    private InstoreConfig|MockObject $config;
    private Http|MockObject $request;
    private BatchResponseFactory|MockObject $batchResponseMockFactory;
    private BatchResponse|MockObject $batchResponseMock;
    private Cart|MockObject $cartModelMock;
    private FuseBidGraphqlHelper|MockObject $fuseBidGraphqlHelperMock;
    private JsonSerializer|MockObject $jsonSerializerMock;
    protected ContextInterface|MockObject $addressInterface;

    /**
     * Setup test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->newRelicHeaders = $this->createMock(NewRelicHeaders::class);
        $this->cartRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->validationCompositeMock = $this->createMock(ValidationBatchComposite::class);
        $this->field = $this->createMock(Field::class);
        $this->resolveInfo = $this->createMock(ResolveInfo::class);
        $this->quoteCollection = $this->createMock(QuoteCollection::class);
        $this->saveRetailCustomerIdMock = $this->createMock(SaveRetailCustomerIdInterface::class);
        $this->cartIntegrationRepositoryMock = $this->createMock(CartIntegrationRepositoryInterface::class);
        $this->cartIntegrationMock = $this->createMock(CartIntegrationInterface::class);
        $this->config = $this->createMock(InstoreConfig::class);
        $this->request = $this->createMock(Http::class);
        $this->loggerHelper = $this->createMock(LoggerHelper::class);
        $this->fxoRateQuoteMock = $this->createMock(FXORateQuote::class);
        $this->setFedexAccountNumberMock = $this->createMock(SetFedexAccountNumber::class);
        $this->cartModelMock = $this->createMock(Cart::class);
        $this->jsonSerializerMock = $this->createMock(JsonSerializer::class);

        $this->requestCommandFactoryMock = $this->getMockBuilder(RequestCommandFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $requestCommandMock = $this->createMock(RequestCommand::class);
        $this->requestCommandFactoryMock->method('create')->willReturn($requestCommandMock);

        $this->cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getGtn', 'getCustomerId'])
            ->onlyMethods(['getShippingAddress', 'getBillingAddress', 'getId', 'getStoreId'])
            ->getMock();

        $this->contextMock = $this->getMockBuilder(ContextInterface::class)
            ->addMethods(['getExtensionAttributes', 'getUserId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->contextExtensionMock = $this->getMockBuilder(ContextExtensionInterface::class)
            ->onlyMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeMock = $this->createMock(StoreInterface::class);
        $this->storeMock->method('getId')->willReturn(1);
        $this->contextExtensionMock->method('getStore')->willReturn($this->storeMock);
        $this->contextMock->method('getExtensionAttributes')->willReturn($this->contextExtensionMock);

        $this->quoteAddressItemMock = $this->getMockBuilder(Item::class)
            ->addMethods(['getAddressType'])
            ->onlyMethods(['save'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressInterface = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'save'])
            ->getMockForAbstractClass();

        $this->extensionAttributesMock = $this->getMockBuilder(ContextExtensionInterface::class)
            ->onlyMethods(['getStore'])
            ->addMethods(['getId'])
            ->getMockForAbstractClass();

        $this->batchResponseMockFactory = $this->createMock(BatchResponseFactory::class);
        $this->batchResponseMock = $this->createMock(BatchResponse::class);
        $this->batchResponseMockFactory->method('create')->willReturn($this->batchResponseMock);

        $this->fuseBidGraphqlHelperMock = $this->getMockBuilder(FuseBidGraphqlHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'validateToggleConfig',
                'updateCustomerInfoWithRetailCustomerId',
                'updateCartAndCustomerForFuseBid',
                'getCartForBidQuote'
            ])
            ->getMock();

        $this->updateGuestCartContactInformation = new UpdateGuestCartContactInformation(
            $this->cartRepositoryMock,
            $this->saveRetailCustomerIdMock,
            $this->cartIntegrationRepositoryMock,
            $this->config,
            $this->request,
            $this->fxoRateQuoteMock,
            $this->setFedexAccountNumberMock,
            $this->cartModelMock,
            $this->requestCommandFactoryMock,
            $this->validationCompositeMock,
            $this->batchResponseMockFactory,
            $this->loggerHelper,
            $this->fuseBidGraphqlHelperMock,
            $this->newRelicHeaders,
            $this->jsonSerializerMock
        );
    }

    public function testSetDataInAddress()
    {
        $this->addressInterface->expects($this->any())
            ->method('getId')
            ->willReturn('123');
        $this->addressInterface->expects($this->any())
            ->method('save')
            ->willReturn('123');
        $result = $this->updateGuestCartContactInformation->setDataInAddress(
            $this->addressInterface,
            ['company' => 'TEST']
        );
        $this->assertEquals($result, null);
    }

    public function testSetContactInfoWithPickupLocationIdAndDeliveryData()
    {
        $cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getShippingAddress', 'getBillingAddress'])
            ->getMock();

        $shippingAddressMock = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $billingAddressMock = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $cartMock->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($shippingAddressMock);
        $cartMock->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($billingAddressMock);

        $integrationMock = $this->getMockBuilder(CartIntegrationInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPickupLocationId', 'getDeliveryData'])
            ->getMockForAbstractClass();

        $integrationMock->expects($this->any())
            ->method('getPickupLocationId')
            ->willReturn('pickup123');
        $integrationMock->expects($this->any())
            ->method('getDeliveryData')
            ->willReturn('deliveryData');

        $shippingContact = ['company' => 'Test Company'];
        $result = $this->updateGuestCartContactInformation->setContactInfo($cartMock, $shippingContact, $integrationMock);
        $this->assertEquals($result, null);
    }

    /**
     * Tests the successful execution of the proceed method in UpdateGuestCartContactInformation.
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testProceedSuccessfully()
    {
        $cartId = 'guest-cart-123';
        $contactInformation = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@example.com',
            'telephone' => '1234567890',
            'ext' => '101',
            'organization' => 'FedEx',
            'retail_customer_id' => 'retail123',
            'alternate_contact' => []
        ];

        $input = ['cart_id' => $cartId, 'contact_information' => $contactInformation];
        $resolveRequest = $this->createMock(ResolveRequest::class);
        $resolveRequest->method('getArgs')->willReturn(['input' => $input]);
        $this->contextMock->method('getExtensionAttributes')->willReturn($this->contextExtensionMock);
        $this->fuseBidGraphqlHelperMock->method('validateToggleConfig')->willReturn(false);
        $this->cartModelMock->expects($this->any())
            ->method('getCart')
            ->with($cartId, $this->contextMock)
            ->willReturn($this->cartMock);

        $shippingAddress = $this->getMockForAbstractClass(AddressInterface::class);
        $billingAddress = $this->getMockForAbstractClass(AddressInterface::class);

        $this->cartMock->method('getShippingAddress')->willReturn($shippingAddress);
        $this->cartMock->method('getBillingAddress')->willReturn($billingAddress);

        $integrationMock = $this->createMock(CartIntegrationInterface::class);
        $this->cartIntegrationRepositoryMock
            ->method('getByQuoteId')
            ->willReturn($integrationMock);

        $this->cartModelMock->method('setContactInfo');
        $this->cartModelMock->method('setCustomerCartData');

        $this->saveRetailCustomerIdMock
            ->expects($this->any())
            ->method('execute')
            ->with($integrationMock, 'retail123');

        $this->cartRepositoryMock
            ->expects($this->any())
            ->method('save')
            ->with($this->cartMock);

        $this->batchResponseMock
            ->expects($this->any())
            ->method('addResponse')
            ->with(
                $resolveRequest,
                $this->callback(function ($data) use ($cartId) {
                    return $data['cart_id'] === $cartId &&
                        $data['contact_information']['firstname'] === 'John';
                })
            );

        $result = $this->updateGuestCartContactInformation->proceed(
            $this->contextMock,
            $this->field,
            [$resolveRequest],
            ['header-key' => 'header-value']
        );

        $this->assertInstanceOf(BatchResponse::class, $result);
    }

    /**
     * Tests the proceed method of UpdateGuestCartContactInformation when the FuseBid GraphQL helper toggle config is enabled.
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testProceedWithFuseBidGraphqlHelperToggleConfigEnabled()
    {
        $cartId = 'guest-cart-456';
        $contactInformation = [
            'firstname' => 'Jane',
            'lastname' => 'Smith',
            'email' => 'jane.smith@example.com',
            'telephone' => '9876543210',
            'ext' => '202',
            'organization' => 'FedEx',
            'retail_customer_id' => 'retail456',
            'alternate_contact' => []
        ];

        $input = ['cart_id' => $cartId, 'contact_information' => $contactInformation];
        $resolveRequest = $this->createMock(ResolveRequest::class);
        $resolveRequest->method('getArgs')->willReturn(['input' => $input]);

        $this->contextMock->method('getExtensionAttributes')->willReturn($this->contextExtensionMock);

        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('validateToggleConfig')
            ->willReturn(true);
        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('updateCartAndCustomerForFuseBid')
            ->with($input);
        $this->fuseBidGraphqlHelperMock->expects($this->any())
            ->method('getCartForBidQuote')
            ->with($cartId, 1)
            ->willReturn($this->cartMock);

        $shippingAddress = $this->getMockForAbstractClass(AddressInterface::class);
        $billingAddress = $this->getMockForAbstractClass(AddressInterface::class);

        $this->cartMock->method('getShippingAddress')->willReturn($shippingAddress);
        $this->cartMock->method('getBillingAddress')->willReturn($billingAddress);

        $integrationMock = $this->createMock(CartIntegrationInterface::class);
        $this->cartIntegrationRepositoryMock
            ->method('getByQuoteId')
            ->willReturn($integrationMock);

        $this->cartModelMock->method('setContactInfo');
        $this->cartModelMock->method('setCustomerCartData');

        $this->saveRetailCustomerIdMock
            ->expects($this->any())
            ->method('execute')
            ->with($integrationMock, 'retail456');

        $this->cartRepositoryMock
            ->expects($this->any())
            ->method('save')
            ->with($this->cartMock);

        $this->batchResponseMock
            ->expects($this->any())
            ->method('addResponse')
            ->with(
                $resolveRequest,
                $this->callback(function ($data) use ($cartId) {
                    return $data['cart_id'] === $cartId &&
                        $data['contact_information']['firstname'] === 'Jane';
                })
            );

        $result = $this->updateGuestCartContactInformation->proceed(
            $this->contextMock,
            $this->field,
            [$resolveRequest],
            ['header-key' => 'header-value']
        );
        $this->assertInstanceOf(BatchResponse::class, $result);
    }

    public function testProceedWithAlternateContactPopulatesAlternateContactResponse()
    {
        $cartId = 'guest-cart-789';
        $alternateContact = [
            'firstname' => 'AltFirst',
            'lastname' => 'AltLast',
            'email' => 'alt@example.com',
            'telephone' => '5551234567',
            'ext' => '303'
        ];
        $contactInformation = [
            'firstname' => 'MainFirst',
            'lastname' => 'MainLast',
            'email' => 'main@example.com',
            'telephone' => '1112223333',
            'ext' => '101',
            'organization' => 'FedEx',
            'retail_customer_id' => 'retail789',
            'alternate_contact' => $alternateContact
        ];

        $input = ['cart_id' => $cartId, 'contact_information' => $contactInformation];
        $resolveRequest = $this->createMock(ResolveRequest::class);
        $resolveRequest->method('getArgs')->willReturn(['input' => $input]);
        $this->contextMock->method('getExtensionAttributes')->willReturn($this->contextExtensionMock);
        $this->fuseBidGraphqlHelperMock->method('validateToggleConfig')->willReturn(false);
        $this->cartModelMock->expects($this->any())
            ->method('getCart')
            ->with($cartId, $this->contextMock)
            ->willReturn($this->cartMock);

        $shippingAddress = $this->getMockForAbstractClass(AddressInterface::class);
        $billingAddress = $this->getMockForAbstractClass(AddressInterface::class);

        $this->cartMock->method('getShippingAddress')->willReturn($shippingAddress);
        $this->cartMock->method('getBillingAddress')->willReturn($billingAddress);

        $integrationMock = $this->createMock(CartIntegrationInterface::class);
        $this->cartIntegrationRepositoryMock
            ->method('getByQuoteId')
            ->willReturn($integrationMock);

        $this->cartModelMock->method('setContactInfo');
        $this->cartModelMock->method('setCustomerCartData');

        $this->saveRetailCustomerIdMock
            ->expects($this->any())
            ->method('execute')
            ->with($integrationMock, 'retail789');

        $this->cartRepositoryMock
            ->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $batchResponse = new BatchResponse();
        $this->assertInstanceOf(BatchResponse::class, $batchResponse);
    }

    /**
     * Test case to verify that the proceed method returns the expected response.
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testProceedReturnsExpectedResponse(): void
    {
        $quoteId = 'guest-cart-001';
        $contactInfo = [
            'firstname' => 'Alice',
            'lastname' => 'Smith',
            'email' => 'alice@example.com',
            'telephone' => '1234567890',
            'ext' => '91',
            'organization' => 'FedexOrg',
            'retail_customer_id' => 'ret123',
            'alternate_contact' => [
                'firstname' => 'Bob',
                'lastname' => 'Smith',
                'email' => 'bob@example.com',
                'telephone' => '0987654321',
                'ext' => '92'
            ]
        ];

        $args = ['input' => ['cart_id' => $quoteId, 'contact_information' => $contactInfo]];
        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getArgs')->willReturn($args);

        $cartMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cartMock->method('getId')->willReturn(1);
        $cartMock->method('getShippingAddress')->willReturn($this->getMockAddress());
        $cartMock->method('getBillingAddress')->willReturn($this->getMockAddress());

        $storeMock = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->method('getId')->willReturn(1);

        $extAttrMock = $this->getMockForAbstractClass(
            ContextExtensionInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getStore']
        );
        $extAttrMock->method('getStore')->willReturn($storeMock);

        $this->contextMock->method('getExtensionAttributes')->willReturn($extAttrMock);

        $this->fuseBidGraphqlHelperMock->method('validateToggleConfig')->willReturn(false);
        $this->cartModelMock->method('getCart')->with($quoteId, $this->contextMock)->willReturn($cartMock);

        $integrationMock = $this->createMock(\Fedex\Cart\Api\Data\CartIntegrationInterface::class);
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($integrationMock);

        $this->batchResponseMock
            ->expects($this->once())
            ->method('addResponse')
            ->with($requestMock, $this->arrayHasKey('contact_information'));

        $result = $this->updateGuestCartContactInformation->proceed(
            $this->contextMock,
            $this->field,
            [$requestMock],
            []
        );

        $this->assertInstanceOf(BatchResponse::class, $result);
    }

    /**
     * Test case to verify that when FuseBid toggle config is enabled,
     * the correct methods are called and the cart is retrieved properly.
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testProceedWithFuseBidToggleConfigEnabled(): void
    {
        $quoteId = 'guest-cart-001';
        $storeId = 1;
        $contactInfo = $this->getStandardContactInfo('ret123');
        $inputArgs = $this->createInputArgs($quoteId, $contactInfo);

        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getArgs')->willReturn(['input' => $inputArgs]);

        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getId')->willReturn($storeId);

        $extAttrMock = $this->getMockForAbstractClass(
            ContextExtensionInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getStore']
        );
        $extAttrMock->method('getStore')->willReturn($storeMock);
        $this->contextMock->method('getExtensionAttributes')->willReturn($extAttrMock);

        $cartMock = $this->createMockCart(
            $this->getMockAddress(),
            $this->getMockAddress()
        );

        $this->fuseBidGraphqlHelperMock->expects($this->once())
            ->method('validateToggleConfig')
            ->willReturn(true);

        $this->fuseBidGraphqlHelperMock->expects($this->once())
            ->method('updateCartAndCustomerForFuseBid')
            ->with($inputArgs);

        $this->fuseBidGraphqlHelperMock->expects($this->once())
            ->method('getCartForBidQuote')
            ->with($quoteId, $storeId)
            ->willReturn($cartMock);

        $this->cartModelMock->expects($this->never())
            ->method('getCart');

        $integrationMock = $this->createMock(CartIntegrationInterface::class);
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($integrationMock);

        $this->batchResponseMock
            ->expects($this->once())
            ->method('addResponse')
            ->with($requestMock, $this->arrayHasKey('contact_information'));

        $result = $this->updateGuestCartContactInformation->proceed(
            $this->contextMock,
            $this->field,
            [$requestMock],
            []
        );

        $this->assertInstanceOf(BatchResponse::class, $result);
    }

    /**
     * Create common utility methods to generate test data
     */

    /**
     * Creates and returns a mock address object for testing purposes.
     *
     * @param int $id Optional address ID, defaults to 1
     * @param string $company Optional company name, defaults to 'FedexOrg'
     * @return \Magento\Quote\Api\Data\AddressInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockAddress($id = 1, $company = 'FedexOrg')
    {
        $addressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $addressMock->method('getId')->willReturn($id);
        $addressMock->method('getCompany')->willReturn($company);
        $addressMock->method('save');

        return $addressMock;
    }

    /**
     * Creates a standard mock cart object with the specified addresses
     *
     * @param AddressInterface|null $shippingAddress Shipping address to use
     * @param AddressInterface|null $billingAddress Billing address to use
     * @param int $id Cart ID, defaults to 1
     * @return MockObject Cart mock object
     */
    private function createMockCart($shippingAddress = null, $billingAddress = null, $id = 1)
    {
        $cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cartMock->method('getId')->willReturn($id);

        if ($shippingAddress !== null) {
            $cartMock->method('getShippingAddress')->willReturn($shippingAddress);
        }

        if ($billingAddress !== null) {
            $cartMock->method('getBillingAddress')->willReturn($billingAddress);
        }

        return $cartMock;
    }

    /**
     * Returns standard mock contact information for tests
     *
     * @param string $retailCustomerId Optional retail customer ID
     * @param bool $includeAlternateContact Whether to include alternate contact info
     * @param bool $includeFedexAccounts Whether to include Fedex account numbers
     * @return array Contact information array
     */
    private function getStandardContactInfo($retailCustomerId = 'retail123', $includeAlternateContact = true, $includeFedexAccounts = false): array
    {
        $contactInfo = [
            'firstname' => 'Alice',
            'lastname' => 'Smith',
            'email' => 'alice@example.com',
            'telephone' => '1234567890',
            'ext' => '91',
            'organization' => 'FedexOrg',
            'retail_customer_id' => $retailCustomerId
        ];

        if ($includeAlternateContact) {
            $contactInfo['alternate_contact'] = [
                'firstname' => 'Bob',
                'lastname' => 'Smith',
                'email' => 'bob@example.com',
                'telephone' => '0987654321',
                'ext' => '92'
            ];
        } else {
            $contactInfo['alternate_contact'] = [];
        }

        if ($includeFedexAccounts) {
            $contactInfo['fedex_account_number'] = 'FEDEX123456';
            $contactInfo['fedex_ship_account_number'] = 'SHIP789012';
        }

        return $contactInfo;
    }

    /**
     * Creates mock input arguments for the resolver
     *
     * @param string $cartId Cart ID to use
     * @param array $contactInfo Contact information array
     * @return array Input arguments
     */
    private function createInputArgs($cartId, $contactInfo): array
    {
        return ['cart_id' => $cartId, 'contact_information' => $contactInfo];
    }

    /**
     * Creates a standard request mock with the provided input arguments
     *
     * @param string $cartId Cart ID for the request
     * @param array $contactInfo Contact information to include
     * @return \PHPUnit\Framework\MockObject\MockObject ResolveRequest mock
     */
    private function createRequestMock($cartId, array $contactInfo): \PHPUnit\Framework\MockObject\MockObject
    {
        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getArgs')->willReturn([
            'input' => $this->createInputArgs($cartId, $contactInfo)
        ]);
        return $requestMock;
    }

    /**
     * Returns a legacy set of mock data for backward compatibility
     *
     * @return array Complete mock data set
     */
    private function getLegacyMockData(): array
    {
        return [
            'cart_id' => "rSEo2yEJJToSqURrjWsQ2XsZuUIdhvU6",
            'contact_information' => [
                'retail_customer_id' => "",
                'firstname' =>  "John Test",
                'lastname' => "Doe",
                'email' => "john.doe@mail.com",
                'telephone' => "(123) 456-7890",
                'organization' => 'test123',
                'ext' => "1234",
                'alternate_contact' => [
                    'firstname' => "Mary",
                    'lastname' => "Doe",
                    'email' => "mary.doe@mail.com",
                    'telephone' => "(098) 765-4321",
                    'ext' => "4321",
                ]
            ]
        ];
    }

    /**
     * Test that FedEx account numbers are handled correctly when enabled
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testProceedHandlesFedexAccountNumbersWhenEnabled(): void
    {
        $quoteId = 'guest-cart-001';
        $fedexAccountNumber = 'FEDEX123456';
        $fedexShipAccountNumber = 'SHIP789012';

        $contactInfo = $this->getStandardContactInfo('ret123', true, true);
        $contactInfo['fedex_account_number'] = $fedexAccountNumber;
        $contactInfo['fedex_ship_account_number'] = $fedexShipAccountNumber;

        $args = $this->createInputArgs($quoteId, $contactInfo);
        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getArgs')->willReturn(['input' => $args]);

        $cartMock = $this->createMockCart(
            $this->getMockAddress(),
            $this->getMockAddress()
        );

        $this->contextMock->method('getExtensionAttributes')
            ->willReturn($this->contextExtensionMock);

        $this->fuseBidGraphqlHelperMock->method('validateToggleConfig')->willReturn(false);

        $this->cartModelMock->method('getCart')
            ->with($quoteId, $this->contextMock)
            ->willReturn($cartMock);

        $this->config->method('isAddOrUpdateFedexAccountNumberEnabled')->willReturn(true);

        $this->setFedexAccountNumberMock->expects($this->once())
            ->method('setFedexAccountNumber')
            ->with($fedexAccountNumber, $fedexShipAccountNumber, $cartMock);

        $this->fxoRateQuoteMock->expects($this->once())
            ->method('getFXORateQuote')
            ->with($cartMock);

        $integrationMock = $this->createMock(CartIntegrationInterface::class);
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')
            ->willReturn($integrationMock);

        $this->batchResponseMock->expects($this->once())
            ->method('addResponse')
            ->with($requestMock, $this->callback(function ($data) use ($fedexAccountNumber, $fedexShipAccountNumber) {
                return $data['contact_information']['fedex_account_number'] === $fedexAccountNumber
                    && $data['contact_information']['fedex_ship_account_number'] === $fedexShipAccountNumber;
            }));

        $result = $this->updateGuestCartContactInformation->proceed(
            $this->contextMock,
            $this->field,
            [$requestMock],
            []
        );

        $this->assertInstanceOf(BatchResponse::class, $result);
    }

    /**
     * Test that FedEx account numbers are not processed when the feature is disabled
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testProceedSkipsFedexAccountNumbersWhenDisabled(): void
    {
        $quoteId = 'guest-cart-001';
        $fedexAccountNumber = 'FEDEX123456';
        $fedexShipAccountNumber = 'SHIP789012';

        $contactInfo = [
            'firstname' => 'Alice',
            'lastname' => 'Smith',
            'email' => 'alice@example.com',
            'telephone' => '1234567890',
            'ext' => '91',
            'organization' => 'FedexOrg',
            'retail_customer_id' => 'ret123',
            'fedex_account_number' => $fedexAccountNumber,
            'fedex_ship_account_number' => $fedexShipAccountNumber,
            'alternate_contact' => [
                'firstname' => 'Bob',
                'lastname' => 'Smith',
                'email' => 'bob@example.com',
                'telephone' => '0987654321',
                'ext' => '92'
            ]
        ];

        $args = ['input' => ['cart_id' => $quoteId, 'contact_information' => $contactInfo]];
        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getArgs')->willReturn($args);

        $cartMock = $this->createMock(Quote::class);
        $cartMock->method('getId')->willReturn(1);
        $cartMock->method('getShippingAddress')->willReturn($this->getMockAddress());
        $cartMock->method('getBillingAddress')->willReturn($this->getMockAddress());

        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getId')->willReturn(1);

        $extAttrMock = $this->getMockForAbstractClass(
            ContextExtensionInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getStore']
        );
        $extAttrMock->method('getStore')->willReturn($storeMock);

        $this->contextMock->method('getExtensionAttributes')->willReturn($extAttrMock);
        $this->fuseBidGraphqlHelperMock->method('validateToggleConfig')->willReturn(false);
        $this->cartModelMock->method('getCart')->with($quoteId, $this->contextMock)->willReturn($cartMock);

        $this->config->method('isAddOrUpdateFedexAccountNumberEnabled')->willReturn(false);

        $this->setFedexAccountNumberMock->expects($this->never())
            ->method('setFedexAccountNumber');

        $this->fxoRateQuoteMock->expects($this->never())
            ->method('getFXORateQuote');

        $integrationMock = $this->createMock(CartIntegrationInterface::class);
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($integrationMock);

        $this->batchResponseMock
            ->expects($this->once())
            ->method('addResponse')
            ->with($requestMock, $this->arrayHasKey('contact_information'));

        $result = $this->updateGuestCartContactInformation->proceed(
            $this->contextMock,
            $this->field,
            [$requestMock],
            []
        );

        $this->assertInstanceOf(BatchResponse::class, $result);
    }

    /**
     * Test that exception handling works correctly when FXORateQuote throws an exception
     *
     * @return void
     */
    public function testProceedHandlesFXORateQuoteException(): void
    {
        $this->expectException(GraphQlInputException::class);

        $quoteId = 'guest-cart-001';
        $fedexAccountNumber = 'FEDEX123456';

        $contactInfo = [
            'firstname' => 'Alice',
            'lastname' => 'Smith',
            'email' => 'alice@example.com',
            'telephone' => '1234567890',
            'ext' => '91',
            'organization' => 'FedexOrg',
            'retail_customer_id' => 'ret123',
            'fedex_account_number' => $fedexAccountNumber,
            'alternate_contact' => [
                'firstname' => 'Bob',
                'lastname' => 'Smith',
                'email' => 'bob@example.com',
                'telephone' => '0987654321',
                'ext' => '92'
            ]
        ];

        $args = ['input' => ['cart_id' => $quoteId, 'contact_information' => $contactInfo]];
        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getArgs')->willReturn($args);

        $cartMock = $this->createMock(Quote::class);
        $cartMock->method('getId')->willReturn(1);

        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getId')->willReturn(1);

        $extAttrMock = $this->getMockForAbstractClass(
            ContextExtensionInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getStore']
        );
        $extAttrMock->method('getStore')->willReturn($storeMock);

        $this->contextMock->method('getExtensionAttributes')->willReturn($extAttrMock);
        $this->fuseBidGraphqlHelperMock->method('validateToggleConfig')->willReturn(false);
        $this->cartModelMock->method('getCart')->with($quoteId, $this->contextMock)->willReturn($cartMock);

        $this->config->method('isAddOrUpdateFedexAccountNumberEnabled')->willReturn(true);

        $exceptionMessage = 'Error processing FXO Rate Quote';
        $exception = new GraphQlFujitsuResponseException(__($exceptionMessage));
        $this->fxoRateQuoteMock->method('getFXORateQuote')
            ->willThrowException($exception);

        $this->loggerHelper->expects($this->atLeastOnce())
            ->method('error');

        $this->updateGuestCartContactInformation->proceed(
            $this->contextMock,
            $this->field,
            [$requestMock],
            []
        );
    }

    /**
     * Test that organization falls back to quote shipping company
     *
     * @dataProvider organizationFallbackProvider
     * @param array $contactInfo The contact information array used in the test
     * @param string $expectedCompanyFromQuote The expected company name from the quote
     * @return void
     * @throws GraphQlInputException
     */
    public function testOrganizationFallbackToQuoteShippingCompany(array $contactInfo, string $expectedCompanyFromQuote)
    {
        $quoteId = 'guest-cart-fallback-test';
        $args = [
            'input' => [
                'cart_id' => $quoteId,
                'contact_information' => $contactInfo
            ]
        ];
        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getArgs')->willReturn($args);
        $cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cartMock->method('getId')->willReturn(1);
        $shippingAddressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shippingAddressMock->method('getCompany')->willReturn($expectedCompanyFromQuote);
        $shippingAddressMock->method('getId')->willReturn(1);
        $billingAddressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $billingAddressMock->method('getId')->willReturn(2);
        $cartMock->method('getShippingAddress')->willReturn($shippingAddressMock);
        $cartMock->method('getBillingAddress')->willReturn($billingAddressMock);
        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->method('getId')->willReturn(1);
        $extAttrMock = $this->getMockForAbstractClass(
            ContextExtensionInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getStore']
        );
        $extAttrMock->method('getStore')->willReturn($storeMock);
        $this->contextMock->method('getExtensionAttributes')->willReturn($extAttrMock);
        $this->fuseBidGraphqlHelperMock->method('validateToggleConfig')->willReturn(false);
        $this->cartModelMock->method('getCart')->with($quoteId, $this->contextMock)->willReturn($cartMock);
        $integrationMock = $this->createMock(CartIntegrationInterface::class);
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($integrationMock);
        $this->config->method('isAddOrUpdateFedexAccountNumberEnabled')->willReturn(false);
        $this->batchResponseMock
            ->expects($this->once())
            ->method('addResponse')
            ->with(
                $requestMock,
                $this->callback(function ($responseData) use ($expectedCompanyFromQuote) {
                    return isset($responseData['contact_information']['organization'])
                        && $responseData['contact_information']['organization'] === $expectedCompanyFromQuote;
                })
            );
        $result = $this->updateGuestCartContactInformation->proceed(
            $this->contextMock,
            $this->field,
            [$requestMock],
            []
        );
        $this->assertInstanceOf(BatchResponse::class, $result);
    }

    /**
     * Data provider for testOrganizationFallbackToQuoteShippingCompany
     *
     * @return array[] Array of test cases with contact info and expected company from quote
     */
    public function organizationFallbackProvider()
    {
        return [
            [[
                'firstname' => 'TestUser',
                'lastname' => 'FallbackTest',
                'email' => 'test@fallback.com',
                'telephone' => '1234567890',
                'ext' => '123',
                'retail_customer_id' => 'retail_fallback_123',
                'alternate_contact' => []
            ], 'Company From Quote Shipping Address'],
            [[
                'firstname' => 'TestUser',
                'lastname' => 'NullOrgTest',
                'email' => 'test@nullorg.com',
                'telephone' => '1234567890',
                'ext' => '123',
                'organization' => null,
                'retail_customer_id' => 'retail_null_org_123',
                'alternate_contact' => []
            ], 'Fallback Company From Quote'],
            [[
                'firstname' => 'Alice',
                'lastname' => 'Smith',
                'email' => 'alice@example.com',
                'telephone' => '1234567890',
                'ext' => '91',
                'retail_customer_id' => 'ret123',
                'alternate_contact' => [
                    'firstname' => 'Bob',
                    'lastname' => 'Smith',
                    'email' => 'bob@example.com',
                    'telephone' => '0987654321',
                    'ext' => '92'
                ]
            ], 'FedEx Company From Address'],
        ];
    }

    /**
     * Test that verifies the organization value from contact information is correctly used
     * when updating guest cart contact information.
     *
     * @return void
     * @throws GraphQlInputException
     */
    public function testOrganizationUsedFromContactInformation()
    {
        $quoteId = 'guest-cart-org-provided-test';
        $providedOrganization = 'Provided Organization Name';
        $quoteCompany = 'Quote Company Should Not Be Used';
        $contactInfoWithOrganization = [
            'firstname' => 'TestUser',
            'lastname' => 'ProvidedOrgTest',
            'email' => 'test@provided.com',
            'telephone' => '1234567890',
            'ext' => '123',
            'organization' => $providedOrganization,
            'retail_customer_id' => 'retail_provided_123',
            'alternate_contact' => []
        ];
        $args = [
            'input' => [
                'cart_id' => $quoteId,
                'contact_information' => $contactInfoWithOrganization
            ]
        ];
        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getArgs')->willReturn($args);
        $cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cartMock->method('getId')->willReturn(1);
        $shippingAddressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shippingAddressMock->method('getCompany')->willReturn($quoteCompany);
        $shippingAddressMock->method('getId')->willReturn(1);
        $billingAddressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $billingAddressMock->method('getId')->willReturn(2);
        $cartMock->method('getShippingAddress')->willReturn($shippingAddressMock);
        $cartMock->method('getBillingAddress')->willReturn($billingAddressMock);
        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->method('getId')->willReturn(1);
        $extAttrMock = $this->getMockForAbstractClass(
            ContextExtensionInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getStore']
        );
        $extAttrMock->method('getStore')->willReturn($storeMock);
        $this->contextMock->method('getExtensionAttributes')->willReturn($extAttrMock);
        $this->fuseBidGraphqlHelperMock->method('validateToggleConfig')->willReturn(false);
        $this->cartModelMock->method('getCart')->with($quoteId, $this->contextMock)->willReturn($cartMock);
        $integrationMock = $this->createMock(CartIntegrationInterface::class);
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($integrationMock);
        $this->config->method('isAddOrUpdateFedexAccountNumberEnabled')->willReturn(false);
        $this->batchResponseMock
            ->expects($this->once())
            ->method('addResponse')
            ->with(
                $requestMock,
                $this->callback(function ($responseData) use ($providedOrganization) {
                    return isset($responseData['contact_information']['organization'])
                        && $responseData['contact_information']['organization'] === $providedOrganization;
                })
            );
        $result = $this->updateGuestCartContactInformation->proceed(
            $this->contextMock,
            $this->field,
            [$requestMock],
            []
        );

        $this->assertInstanceOf(BatchResponse::class, $result);
    }

    /**
     * Test that error handling works correctly when setContactInfo throws an exception
     * The resolver should catch the exception, log it, and re-throw a GraphQlInputException
     *
     * @return void
     */
    public function testSetContactInfoExceptionHandling(): void
    {
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Error on saving contact information into cart');

        $shippingContact = [
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'testuser@example.com',
            'telephone' => '1234567890',
            'ext' => '99',
            'company' => 'TestCompany',
            'alternate_contact' => []
        ];

        $cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['setContactInfo'])
            ->onlyMethods(['getId', 'getBillingAddress', 'getShippingAddress'])
            ->getMock();

        $shippingAddressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shippingAddressMock->method('getId')->willReturn(1);

        $billingAddressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $billingAddressMock->method('getId')->willReturn(2);

        $cartMock->method('getId')->willReturn(123);

        $cartMock->method('getBillingAddress')->willReturn($billingAddressMock);
        $cartMock->method('getShippingAddress')->willReturn($shippingAddressMock);


        $this->batchResponseMock->expects($this->never())
            ->method('addResponse');

        $integrationMock = $this->createMock(CartIntegrationInterface::class);
        $integrationMock->method('getPickupLocationId')->willReturn('pickup-123');
        $integrationMock->method('getDeliveryData')->willReturn('{"delivery_method":""}');

        $this->jsonSerializerMock->expects($this->once())
            ->method('unserialize')
            ->with('{"delivery_method":""}')
            ->willReturn([]);

        $this->cartIntegrationRepositoryMock->method('getByQuoteId')
            ->willReturn($integrationMock);

        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getArgs')->willReturn([
            'input' => [
                'cart_id' => 'test-cart',
                'contact_information' => $shippingContact
            ]
        ]);

        $this->cartModelMock->method('getCart')
            ->with('test-cart', $this->contextMock)
            ->willReturn($cartMock);
        $this->fuseBidGraphqlHelperMock->method('validateToggleConfig');
        $this->cartModelMock->expects($this->atLeastOnce())
            ->method('setContactInfo')
            ->willThrowException(new \Exception('Error on saving contact information into cart'));
        $this->loggerHelper->expects($this->atLeastOnce())
            ->method('error')
            ->withAnyParameters();

        $this->updateGuestCartContactInformation->proceed(
            $this->contextMock,
            $this->field,
            [$requestMock],
            []
        );
    }

    /**
     * Test case for scenario when only the billing address is updated in the guest cart.
     *
     * @return void
     */
    public function testSetContactInfoElseBranchOnlyBillingAddressUpdated()
    {
        $cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $billingAddressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $shippingAddressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cartMock->method('getBillingAddress')->willReturn($billingAddressMock);
        $cartMock->method('getShippingAddress')->willReturn($shippingAddressMock);

        $shippingContact = [
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'testuser@example.com'
        ];

        $integrationMock = $this->createMock(CartIntegrationInterface::class);
        $integrationMock->method('getPickupLocationId')->willReturn(null);
        $integrationMock->method('getDeliveryData')->willReturn('{"shipping_method":"fedex"}');

        $this->jsonSerializerMock->method('unserialize')
            ->with('{"shipping_method":"fedex"}')
            ->willReturn(['shipping_method' => 'fedex']);

        $updateGuestCartContactInformationPartialMock = $this->getMockBuilder(UpdateGuestCartContactInformation::class)
            ->setConstructorArgs([
                $this->cartRepositoryMock,
                $this->saveRetailCustomerIdMock,
                $this->cartIntegrationRepositoryMock,
                $this->config,
                $this->request,
                $this->fxoRateQuoteMock,
                $this->setFedexAccountNumberMock,
                $this->cartModelMock,
                $this->requestCommandFactoryMock,
                $this->validationCompositeMock,
                $this->batchResponseMockFactory,
                $this->loggerHelper,
                $this->fuseBidGraphqlHelperMock,
                $this->newRelicHeaders,
                $this->jsonSerializerMock
            ])
            ->onlyMethods(['setDataInAddress'])
            ->getMock();


        $updateGuestCartContactInformationPartialMock->expects($this->once())
            ->method('setDataInAddress')
            ->with($this->identicalTo($billingAddressMock), $shippingContact);

        $updateGuestCartContactInformationPartialMock->setContactInfo($cartMock, $shippingContact, $integrationMock);
    }

    /**
     * Test when support lte_identifier toggle is On and the LTE identifier is present and the FedEx account Number is not present
     * @throws GraphQlInputException
     */
    public function testToggleOnLteIdentifierPresentFedexAccountNumberNotPresent()
    {
        $input = [
            'cart_id' => "rSEo2yEJJToSqURrjWsQ2XsZuUIdhvU6",
            'contact_information' => [
                'retail_customer_id' => "",
                'firstname' =>  "John Test",
                'lastname' => "Doe",
                'email' => "john.doe@mail.com",
                'telephone' => "(123) 456-7890",
                'organization' => 'test123',
                'ext' => "1234",
                'lte_identifier' => 'test123',
                'alternate_contact' => [
                    'firstname' => "Mary",
                    'lastname' => "Doe",
                    'email' => "mary.doe@mail.com",
                    'telephone' => "(098) 765-4321",
                    'ext' => "4321",
                ]
            ]
        ];

        // Set up mocks and expectations
        $this->config->method('isAddOrUpdateFedexAccountNumberEnabled')->willReturn(true);
        $this->config->method('isSupportLteIdentifierEnabled')->willReturn(true);

        $cartId = $input['cart_id'];
        $this->cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['setLteIdentifier'])
            ->onlyMethods(['getShippingAddress','getBillingAddress'])
            ->getMockForAbstractClass();
        $this->cartMock->method('getShippingAddress')->willReturn(null);
        $this->cartMock->method('getBillingAddress')->willReturn(null);
        $this->cartModelMock->expects($this->once())
            ->method('getCart')
            ->with($cartId, $this->contextMock)
            ->willReturn($this->cartMock);

        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($this->cartIntegrationMock);
        $this->cartIntegrationMock->method('getDeliveryData')->willReturn(null);
        $this->cartIntegrationMock->method('getPickupLocationId')->willReturn(null);
        $this->cartRepositoryMock->method('save')->willReturnSelf();

        $this->cartModelMock->method('setCustomerCartData')->willReturnSelf();
        $this->saveRetailCustomerIdMock->method('execute');

        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getArgs')->willReturn(['input' => $input]);

        $headerArray = [];

        // Call the proceed method
        $this->updateGuestCartContactInformation
            ->proceed(
                $this->contextMock,
                $this->field,
                [$requestMock],
                $headerArray
            );
    }

    /**
     * Test when support lte_identifier toggle is On and the LTE identifier is present and the FedEx account Number is present
     * @throws GraphQlInputException
     */
    public function testToggleOnLteIdentifierPresentFedexAccountNumberPresent()
    {
        $input = $this->getMockData();

        // Set up mocks and expectations
        $this->config->method('isAddOrUpdateFedexAccountNumberEnabled')->willReturn(true);
        $this->config->method('isSupportLteIdentifierEnabled')->willReturn(true);

        $cartId = $input['cart_id'];
        $contactInformation = $input['contact_information'];
        $this->cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['setLteIdentifier'])
            ->onlyMethods(['getShippingAddress','getBillingAddress'])
            ->getMockForAbstractClass();
        $this->cartMock->method('getShippingAddress')->willReturn(null);
        $this->cartMock->method('getBillingAddress')->willReturn(null);
        $this->cartModelMock->expects($this->once())
            ->method('getCart')
            ->with($cartId, $this->contextMock)
            ->willReturn($this->cartMock);

        $this->cartMock->expects($this->once())
            ->method('setLteIdentifier')
            ->with($contactInformation['lte_identifier']);

        $this->setFedexAccountNumberMock->expects($this->once())
            ->method('setFedexAccountNumber')
            ->with(
                $contactInformation['fedex_account_number'],
                $contactInformation['fedex_ship_account_number'],
                $this->cartMock
            );

        $this->fxoRateQuoteMock->expects($this->once())
            ->method('getFXORateQuote')
            ->with($this->cartMock);

        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($this->cartIntegrationMock);
        $this->cartIntegrationMock->method('getDeliveryData')->willReturn(null);
        $this->cartIntegrationMock->method('getPickupLocationId')->willReturn(null);
        $this->cartRepositoryMock->method('save')->willReturnSelf();

        $this->cartModelMock->method('setCustomerCartData')->willReturnSelf();
        $this->saveRetailCustomerIdMock->method('execute');

        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getArgs')->willReturn(['input' => $input]);

        $headerArray = [];

        // Call the proceed method
        $this->updateGuestCartContactInformation
            ->proceed(
                $this->contextMock,
                $this->field,
                [$requestMock],
                $headerArray
            );
    }

    /**
     * Test when support lte_identifier toggle is On and the LTE identifier is not present and the FedEx account Number is present
     * @throws GraphQlInputException
     */
    public function testToggleOnLteIdentifierNotPresentFedexAccountNumberPresent()
    {
        $input = [
            'cart_id' => "rSEo2yEJJToSqURrjWsQ2XsZuUIdhvU6",
            'contact_information' => [
                'retail_customer_id' => "",
                'firstname' =>  "John Test",
                'lastname' => "Doe",
                'email' => "john.doe@mail.com",
                'telephone' => "(123) 456-7890",
                'organization' => 'test123',
                'ext' => "1234",
                'fedex_account_number' => 'test123',
                'fedex_ship_account_number' => 'test123',
                'alternate_contact' => [
                    'firstname' => "Mary",
                    'lastname' => "Doe",
                    'email' => "mary.doe@mail.com",
                    'telephone' => "(098) 765-4321",
                    'ext' => "4321",
                ]
            ]
        ];

        // Set up mocks and expectations
        $this->config->method('isAddOrUpdateFedexAccountNumberEnabled')->willReturn(true);
        $this->config->method('isSupportLteIdentifierEnabled')->willReturn(true);

        $cartId = $input['cart_id'];
        $contactInformation = $input['contact_information'];
        $this->cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['setLteIdentifier'])
            ->onlyMethods(['getShippingAddress','getBillingAddress'])
            ->getMockForAbstractClass();
        $this->cartMock->method('getShippingAddress')->willReturn(null);
        $this->cartMock->method('getBillingAddress')->willReturn(null);
        $this->cartModelMock->expects($this->once())
            ->method('getCart')
            ->with($cartId, $this->contextMock)
            ->willReturn($this->cartMock);

        $this->cartMock->expects($this->once())
            ->method('setLteIdentifier')
            ->with(null);

        $this->setFedexAccountNumberMock->expects($this->once())
            ->method('setFedexAccountNumber')
            ->with(
                $contactInformation['fedex_account_number'],
                $contactInformation['fedex_ship_account_number'],
                $this->cartMock
            );

        $this->fxoRateQuoteMock->expects($this->once())
            ->method('getFXORateQuote')
            ->with($this->cartMock);

        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($this->cartIntegrationMock);
        $this->cartIntegrationMock->method('getDeliveryData')->willReturn(null);
        $this->cartIntegrationMock->method('getPickupLocationId')->willReturn(null);
        $this->cartRepositoryMock->method('save')->willReturnSelf();

        $this->cartModelMock->method('setCustomerCartData')->willReturnSelf();
        $this->saveRetailCustomerIdMock->method('execute');

        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getArgs')->willReturn(['input' => $input]);

        $headerArray = [];

        // Call the proceed method
        $this->updateGuestCartContactInformation
            ->proceed(
                $this->contextMock,
                $this->field,
                [$requestMock],
                $headerArray
            );
    }

    /**
     * Test if the proceed method works correctly when lte_identifier is Disabled
     * @throws GraphQlInputException
     */
    public function testToggleOffForLteIdentifierSupport(): void
    {
        $input = $this->getMockData();
        // Set up mocks and expectations
        $this->config->method('isAddOrUpdateFedexAccountNumberEnabled')->willReturn(true);
        $this->config->method('isSupportLteIdentifierEnabled')->willReturn(false);

        $cartId = $input['cart_id'];
        $contactInformation = $input['contact_information'];
        $this->cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['setLteIdentifier'])
            ->onlyMethods(['getShippingAddress','getBillingAddress'])
            ->getMockForAbstractClass();

        $this->cartMock->method('getShippingAddress')->willReturn(null);
        $this->cartMock->method('getBillingAddress')->willReturn(null);

        $this->cartModelMock->expects($this->once())
            ->method('getCart')
            ->with($cartId, $this->contextMock)
            ->willReturn($this->cartMock);

        $this->cartMock->expects($this->once())
            ->method('setLteIdentifier')
            ->with(null);

        $this->setFedexAccountNumberMock->expects($this->once())
            ->method('setFedexAccountNumber')
            ->with(
                $contactInformation['fedex_account_number'],
                $contactInformation['fedex_ship_account_number'],
                $this->cartMock
            );

        $this->fxoRateQuoteMock->expects($this->once())
            ->method('getFXORateQuote')
            ->with($this->cartMock);

        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($this->cartIntegrationMock);
        $this->cartIntegrationMock->method('getDeliveryData')->willReturn(null);
        $this->cartIntegrationMock->method('getPickupLocationId')->willReturn(null);
        $this->cartRepositoryMock->method('save')->willReturnSelf();

        $this->cartModelMock->method('setCustomerCartData')->willReturnSelf();
        $this->saveRetailCustomerIdMock->method('execute');

        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getArgs')->willReturn(['input' => $input]);

        $headerArray = [];

        // Call the proceed method
        $this->updateGuestCartContactInformation
            ->proceed(
                $this->contextMock,
                $this->field,
                [$requestMock],
                $headerArray
            );
    }

    /**
     * function to return the array args to the test
     * @return array
     */
    private function getMockData(): array
    {
        return [
            'cart_id' => "rSEo2yEJJToSqURrjWsQ2XsZuUIdhvU6",
            'contact_information' => [
                'retail_customer_id' => "",
                'firstname' =>  "John Test",
                'lastname' => "Doe",
                'email' => "john.doe@mail.com",
                'telephone' => "(123) 456-7890",
                'organization' => 'test123',
                'ext' => "1234",
                'fedex_account_number' => 'test123',
                'fedex_ship_account_number' => 'test123',
                'lte_identifier' => 'test123',
                'alternate_contact' => [
                    'firstname' => "Mary",
                    'lastname' => "Doe",
                    'email' => "mary.doe@mail.com",
                    'telephone' => "(098) 765-4321",
                    'ext' => "4321",
                ]
            ]
        ];
    }
}
