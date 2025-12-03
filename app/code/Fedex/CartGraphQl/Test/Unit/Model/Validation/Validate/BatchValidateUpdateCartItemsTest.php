<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Validation\Validate;

use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand as GraphQlRequestCommand;
use Fedex\CartGraphQl\Model\Validation\Validate\BatchValidateUpdateCartItems as ValidateUpdateCartItems;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Psr\Log\LoggerInterface;

class BatchValidateUpdateCartItemsTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var ValidateUpdateCartItems
     */
    protected ValidateUpdateCartItems $validateUpdateCartItems;

    /**
     * @var GraphQlRequestCommand|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $graphQlRequestCommandMock;

    protected function setUp(): void
    {
        $this->graphQlRequestCommandMock = $this->getMockBuilder(GraphQlRequestCommand::class)
            ->onlyMethods(['getRequests'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->validateUpdateCartItems = $this->objectManager->getObject(
            ValidateUpdateCartItems::class,
            [
                'logger' => $this->loggerMock
            ]
        );
    }

    public function testValidate()
    {
        $mockItems['cartItems'] = [
            [
                'data' => 'escaped_json_data'
            ],
            [
                'cart_item_id' => '050',
                'quantity' => 0
            ]
        ];
        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getArgs')->willReturn($mockItems);

        $requestCommandMock = $this->createMock(GraphQlBatchRequestCommand::class);
        $requestCommandMock->method('getRequests')->willReturn([$requestMock]);
        $this->validateUpdateCartItems->validate($requestCommandMock);
    }

    public function testValidateException()
    {
        $mockItems['cartItems'] = [
            [
                'quantity' => 0
            ]
        ];

        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getArgs')->willReturn($mockItems);
        $field = $this->createMock(Field::class);
        $field->method('getName')->willReturn('updateCartItems');
        $requestMock->method('getField')->willReturn($field);
        $requestCommandMock = $this->createMock(GraphQlBatchRequestCommand::class);
        $requestCommandMock->method('getRequests')->willReturn([$requestMock]);

        $this->expectExceptionMessage(
            'Required parameter "data" or "quantity" and "cart_item_id" on "cartItems" are missing.'
        );
        $this->expectException(GraphQlInputException::class);
        $this->validateUpdateCartItems->validate($requestCommandMock);
    }
}
