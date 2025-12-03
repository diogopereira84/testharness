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
use Fedex\CartGraphQl\Model\Validation\Validate\ValidateLocationId;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Psr\Log\LoggerInterface;

class ValidateLocationIdTest extends TestCase
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
     * @var ValidateLocationId
     */
    protected ValidateLocationId $validateLocationId;

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
        $this->validateLocationId = $this->objectManager->getObject(
            ValidateLocationId::class,
            [
                'logger' => $this->loggerMock
            ]
        );
    }

    public function testValidate()
    {
        $this->graphQlRequestCommandMock->expects($this->exactly(1))->method('getArgs')
            ->willReturn(['input' => ['location_id' => 'some_location_id']]);
        $this->validateLocationId->validate($this->graphQlRequestCommandMock);
    }

    public function testValidateException()
    {
        $this->graphQlRequestCommandMock->expects($this->once())
            ->method('getArgs')->willReturn([]);
        $this->expectExceptionMessage('Required parameter "location_id" is missing.');
        $this->expectException(GraphQlInputException::class);
        $this->validateLocationId->validate($this->graphQlRequestCommandMock);
    }
}
