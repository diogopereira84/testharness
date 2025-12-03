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
use Fedex\CartGraphQl\Model\Validation\Validate\BatchValidateStoreId as ValidateStoreId;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Psr\Log\LoggerInterface;

class BatchValidateStoreIdTest extends TestCase
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
     * @var ValidateStoreId
     */
    protected ValidateStoreId $validateLocationId;

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
        $this->validateLocationId = $this->objectManager->getObject(
            ValidateStoreId::class,
            [
                'logger' => $this->loggerMock
            ]
        );
    }

    public function testValidate()
    {
        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getArgs')->willReturn(["input" => ['store_id' => 'some_store_id']]);

        $requestCommandMock = $this->createMock(GraphQlBatchRequestCommand::class);
        $requestCommandMock->method('getRequests')->willReturn([$requestMock]);
        $this->validateLocationId->validate($requestCommandMock);
    }

    public function testValidateException()
    {
        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getArgs')->willReturn([]);

        $requestCommandMock = $this->createMock(GraphQlBatchRequestCommand::class);
        $requestCommandMock->method('getRequests')->willReturn([$requestMock]);
        $this->expectExceptionMessage('Required parameter "store_id" is missing.');
        $this->expectException(GraphQlInputException::class);
        $this->validateLocationId->validate($requestCommandMock);
    }
}
