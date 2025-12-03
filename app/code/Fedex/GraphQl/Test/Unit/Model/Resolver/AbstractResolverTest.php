<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Model\Resolver;

use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;
use Fedex\GraphQl\Model\Resolver\AbstractResolver;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\Validation\Validate\BatchValidateAccessToken;
use Fedex\GraphQl\Model\Validation\Validate\BatchValidateInput;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use PHPUnit\Framework\TestCase;
use Fedex\GraphQl\Model\NewRelicHeaders;

class AbstractResolverTest extends TestCase
{
    private $newRelicHeaders;
    private $requestCommandFactory;
    private $batchResponseFactory;
    private $loggerHelper;
    private $validationComposite;
    private $validations;
    private $abstractResolver;

    protected function setUp(): void
    {
        $this->newRelicHeaders = $this->createMock(NewRelicHeaders::class);
        $this->requestCommandFactory = $this->createMock(GraphQlBatchRequestCommandFactory::class);
        $this->batchResponseFactory = $this->createMock(BatchResponseFactory::class);
        $this->loggerHelper = $this->createMock(LoggerHelper::class);
        $this->validationComposite = $this->createMock(ValidationBatchComposite::class);
        $this->validations = [
            $this->createMock(BatchValidateInput::class),
            $this->createMock(BatchValidateAccessToken::class)
        ];

        $this->abstractResolver = $this->getMockForAbstractClass(
            AbstractResolver::class,
            [
                $this->requestCommandFactory,
                $this->batchResponseFactory,
                $this->loggerHelper,
                $this->validationComposite,
                $this->newRelicHeaders,
                $this->validations
            ]
        );
    }

    public function testResolve(): void
    {
        $context = $this->createMock(ContextInterface::class);
        $field = $this->createMock(Field::class);
        $requests = ['request1', 'request2'];
        $headerArray = ['header1' => 'value1', 'header2' => 'value2'];

        $batchResponseMock = $this->createMock(BatchResponse::class);

        $this->abstractResolver->expects($this->once())
            ->method('proceed')
            ->with($context, $field, $requests, $headerArray)
            ->willReturn($batchResponseMock);

        $requestCommandMock = $this->createMock(GraphQlBatchRequestCommand::class);
        $this->requestCommandFactory->expects($this->once())
            ->method('create')
            ->with([
                'context' => $context,
                'field' => $field,
                'requests' => $requests
            ])
            ->willReturn($requestCommandMock);

        $this->validationComposite->expects($this->exactly(count($this->validations)))
            ->method('add')
            ->withConsecutive(...array_map(fn($v) => [$v], $this->validations));

        $this->validationComposite->expects($this->once())
            ->method('validate')
            ->with($requestCommandMock);

        $this->newRelicHeaders->expects($this->once())
            ->method('getHeadersForMutation')
            ->willReturn($headerArray);

        $result = $this->abstractResolver->resolve($context, $field, $requests);

        $this->assertSame($batchResponseMock, $result);
    }
}
