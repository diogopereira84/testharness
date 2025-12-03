<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Validation\Validate;

use Fedex\GraphQl\Model\GraphQlRequestCommand;
use Fedex\CartGraphQl\Model\Validation\Validate\ValidateCartItems;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Psr\Log\LoggerInterface;

class ValidateCartItemsTest extends TestCase
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
     * @var ValidateCartItems
     */
    protected ValidateCartItems $validateCartItems;

    /**
     * @var GraphQlRequestCommand|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $graphQlRequestCommandMock;

    protected function setUp(): void
    {
        $this->graphQlRequestCommandMock = $this->getMockBuilder(GraphQlRequestCommand::class)
            ->onlyMethods(['getArgs'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
			->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->validateCartItems = $this->objectManager->getObject(
            ValidateCartItems::class,
            [
                'logger' => $this->loggerMock
            ]
        );
    }

    public function testValidate()
    {
        $this->graphQlRequestCommandMock->expects($this->exactly(2))->method('getArgs')
            ->willReturn(['cartItems' => ['some_cart_items']]);
        $this->validateCartItems->validate($this->graphQlRequestCommandMock);
    }

    public function testValidateException()
    {
        $this->graphQlRequestCommandMock->expects($this->once())
            ->method('getArgs')->willReturn([]);
        $this->expectExceptionMessage('Required parameter "cartItems" is missing.');
        $this->expectException(GraphQlInputException::class);
        $this->validateCartItems->validate($this->graphQlRequestCommandMock);
    }
}
