<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Model\Validation\Validate;

use Fedex\GraphQl\Model\GraphQlRequestCommand;
use Fedex\GraphQl\Model\Validation\Validate\ValidateInput;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ValidateInputTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var ValidateInput
     */
    protected ValidateInput $validateInput;

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

        $this->validateInput = new ValidateInput($this->loggerMock);
    }

    public function testValidate()
    {
        $this->graphQlRequestCommandMock->expects($this->exactly(2))->method('getArgs')
            ->willReturn(['input' => ['someInput']]);
        $this->validateInput->validate($this->graphQlRequestCommandMock);
    }

    public function testValidateException()
    {
        $this->graphQlRequestCommandMock->expects($this->once())
            ->method('getArgs')->willReturn([]);
        $this->expectExceptionMessage('"input" value should be specified');
        $this->expectException(GraphQlInputException::class);
        $this->validateInput->validate($this->graphQlRequestCommandMock);
    }
}
