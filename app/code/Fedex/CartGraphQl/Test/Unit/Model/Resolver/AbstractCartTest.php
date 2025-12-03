<?php

/**
 * @category    Fedex
 * @package     Fedex_CartGraphQl
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Olimjon Akhmedov <oakhmedov@mcfadyen.com>
 */

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Resolver;

use Fedex\CartGraphQl\Model\Validation\Validate\BatchValidateCartIdForCreateOrder as ValidateCartId;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand as RequestCommand;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Validation\Validate\BatchValidateInput as ValidateInput;
use Fedex\GraphQl\Model\Validation\ValidationBatchCompositeFactory as ValidationComposite;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use Fedex\CartGraphQl\Model\Resolver\AbstractCart;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\GraphQl\Model\Query\Context;
use Magento\GraphQl\Model\Query\ContextExtensionInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\GraphQl\Config\Element\Field;

class AbstractCartTest extends TestCase
{
    /**
     * @var (\Magento\Quote\Api\CartRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cartRepositoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     *
     * Mock object for the store.
     */
    protected $storeMock;

    /**
     * Mock object for the context extension used in unit tests.
     *
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextExtensionMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     *
     * Mock object for validation composite used in unit tests.
     */
    protected $validationCompositeMock;

    /**
     * Mock object for input validation used in unit tests.
     *
     * @var (\Fedex\GraphQl\Model\Validation\Validate\BatchValidateInput
     *      & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $validateInputMock;

    /**
     * Mock object for the cart ID validation functionality.
     *
     * @var (\Fedex\CartGraphQl\Model\Validation\Validate\BatchValidateCartIdForCreateOrder
     *      & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $validateCartIdMock;

    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;

    /**
     * @var mixed
     *
     * Reference to the AbstractCart instance used for testing.
     */
    protected $abstractCart;

    /**
     * @var GetCartForUser|\PHPUnit\Framework\MockObject\MockObject
     */
    private $getCartForUserMock;

    /**
     * @var Address|\PHPUnit\Framework\MockObject\MockObject
     */
    private $addressMock;

    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;

    /**
     * @var Quote|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cartMock;

    /**
     * @var RequestCommandFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestCommandFactoryMock;

    /**
     * @var Field|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $field;

    /**
     * Initialize
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->getCartForUserMock = $this->getMockBuilder(GetCartForUser::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartRepositoryMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartMock = $this->getMockBuilder(Quote::class)
            ->addMethods(['getCustomerTelephone','getGtn'])
            ->onlyMethods(['isSaveAllowed', 'getStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextExtensionMock = $this->getMockBuilder(ContextExtensionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->contextExtensionMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->context->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->contextExtensionMock);
        $this->requestCommandFactoryMock = $this->getMockBuilder(RequestCommandFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $requestCommandMock = $this->getMockBuilder(RequestCommand::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requestCommandFactoryMock->method('create')->willReturn($requestCommandMock);
        $this->validationCompositeMock = $this->getMockBuilder(ValidationComposite::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $validationCompositeMock = $this->getMockBuilder(ValidationBatchComposite::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->validationCompositeMock->method('create')->willReturn($validationCompositeMock);
        $this->validateInputMock = $this->getMockBuilder(ValidateInput::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->validateCartIdMock = $this->getMockBuilder(ValidateCartId::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->field = $this->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->abstractCart = $this->getMockForAbstractClass(
            AbstractCart::class,
            [
                $this->getCartForUserMock,
                $this->cartRepositoryMock,
                $this->addressMock,
                $this->requestCommandFactoryMock,
                $this->validationCompositeMock,
                $this->validateCartIdMock,
                $this->loggerMock
            ]
        );
    }

    /**
     * Check that returned instance is correct
     *
     * @return void
     */
    public function testGetRequestCommand()
    {
        $requestCommand = $this->requestCommandFactoryMock->create();
        $this->assertInstanceOf(RequestCommand::class, $requestCommand);
    }

    /**
     * Check that returned instance is correct
     *
     * @return void
     */
    public function testGetCart()
    {
        $cart = $this->getCart();
        $this->assertInstanceOf(Quote::class, $cart);
    }

    /**
     * Check that value can be set
     *
     * @return void
     */
    public function testSetContactInfo()
    {
        $firstname = 'John';
        $shippingContact = ['firstname' => $firstname];
        $this->addressMock->expects($this->any())
            ->method('getFirstname')
            ->willReturn($firstname);
        $this->abstractCart->setContactInfo($this->addressMock, $shippingContact);
        $this->assertEquals($firstname, $this->addressMock->getFirstname());
    }

    /**
     * Check that value can be set
     *
     * @return void
     */
    public function testSetCustomerCartData()
    {
        $customerTelephone = '7894565454';
        $shippingContact = ['telephone' => $customerTelephone];
        $this->cartMock->expects($this->any())
            ->method('getCustomerTelephone')
            ->willReturn('7894565454');
        $this->abstractCart->setCustomerCartData($this->cartMock, $shippingContact, []);
        $this->assertEquals($customerTelephone, $this->cartMock->getCustomerTelephone());
    }

    /**
     * Get cart
     *
     * @return Quote
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException
     */
    protected function getCart(): Quote
    {
        $this->cartMock->expects($this->any())
            ->method('isSaveAllowed')
            ->willReturn(true);
        $this->getCartForUserMock->expects($this->any())
            ->method('execute')
            ->willReturn($this->cartMock);
        return $this->abstractCart->getCart('1', $this->context);
    }

    /**
     * Tests that the getRequestCommand method returns the expected command.
     *
     * @return void
     */
    public function testGetRequestCommandReturnsCommand()
    {
        $context = $this->createMock(ContextInterface::class);
        $field = $this->createMock(Field::class);
        $requests = [['foo' => 'bar']];

        $expectedCommand = $this->createMock(GraphQlBatchRequestCommand::class);

        $requestCommandFactory = (new \ReflectionClass($this->abstractCart))->getProperty('requestCommandFactory');
        $requestCommandFactory->setAccessible(true);
        $mockFactory = $this->createMock(GraphQlBatchRequestCommandFactory::class);
        $mockFactory->expects($this->once())
            ->method('create')
            ->with([
                'field' => $field,
                'context' => $context,
                'requests' => $requests
            ])
            ->willReturn($expectedCommand);
        $requestCommandFactory->setValue($this->abstractCart, $mockFactory);

        $result = $this->abstractCart->getRequestCommand($context, $field, $requests);

        $this->assertSame($expectedCommand, $result);
    }

    /**
     * Tests that the getCart method throws an exception when saving is not allowed.
     *
     * @return void
     */
    public function testGetCartThrowsExceptionWhenSaveNotAllowed()
    {
        $cartId = '1';

        $cartMock = $this->getMockBuilder(Quote::class)
            ->addMethods(['getGtn'])
            ->onlyMethods(['isSaveAllowed'])
            ->disableOriginalConstructor()
            ->getMock();

        $cartMock->expects($this->once())
            ->method('isSaveAllowed')
            ->willReturn(false);

        $cartMock->expects($this->once())
            ->method('getGtn')
            ->willReturn('GTN123');

        $this->getCartForUserMock->expects($this->once())
            ->method('execute')
            ->with($cartId, null, 1)
            ->willReturn($cartMock);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Customer contact is not allowed to save into cart. GTN: GTN123'));

        $this->expectException(\Magento\Framework\GraphQl\Exception\GraphQlInputException::class);
        $this->abstractCart->getCart($cartId, $this->context);
    }
}
