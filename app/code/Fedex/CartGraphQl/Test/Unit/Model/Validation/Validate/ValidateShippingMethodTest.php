<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Validation\Validate;

use Fedex\GraphQl\Model\GraphQlRequestCommand;
use Fedex\CartGraphQl\Model\Validation\Validate\ValidateShippingMethod;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ValidateShippingMethodTest extends TestCase
{
    protected $graphQlRequestCommandMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $validateShippingMethod;
    protected function setUp(): void
    {
        $this->graphQlRequestCommandMock = $this->getMockBuilder(GraphQlRequestCommand::class)
            ->onlyMethods(['getArgs'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->validateShippingMethod = new ValidateShippingMethod($this->loggerMock);
    }

    public function testValidateWithMissingParameters()
    {
        $this->graphQlRequestCommandMock->expects($this->once())
            ->method('getArgs')->willReturn([]);
        $this->expectExceptionMessage('Required parameter pickup_data or shipping_data is missing.');
        $this->expectException(GraphQlInputException::class);
        $this->validateShippingMethod->validate($this->graphQlRequestCommandMock);
    }

    public function testValidateWithBothParameters()
    {
        $this->graphQlRequestCommandMock->expects($this->once())->method('getArgs')
            ->willReturn(['input' => ['pickup_data' => 'some_pickup_data', 'shipping_data' => 'some_shipping_data']]);
        $this->expectExceptionMessage('You should provide just pickup_data or shipping_data parameter.');
        $this->expectException(GraphQlInputException::class);
        $this->validateShippingMethod->validate($this->graphQlRequestCommandMock);
    }

    public function testValidateWithOnlyPickupData()
    {
        $this->graphQlRequestCommandMock->expects($this->once())->method('getArgs')
            ->willReturn(['input' => ['pickup_data' => 'some_pickup_data']]);
        $this->validateShippingMethod->validate($this->graphQlRequestCommandMock);
    }

    public function testValidateWithOnlyShippingData()
    {
        $this->graphQlRequestCommandMock->expects($this->once())->method('getArgs')
            ->willReturn(['input' => ['shipping_data' => 'some_shipping_data']]);
        $this->validateShippingMethod->validate($this->graphQlRequestCommandMock);
    }
}
