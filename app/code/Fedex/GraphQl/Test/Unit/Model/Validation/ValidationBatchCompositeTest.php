<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */

declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Model\Validation;

use Fedex\GraphQl\Api\GraphQlBatchValidationInterface;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use PHPUnit\Framework\TestCase;

class ValidationBatchCompositeTest extends TestCase
{
    private ValidationBatchComposite $validationBatchComposite;

    protected function setUp(): void
    {
        $this->validationBatchComposite = new ValidationBatchComposite();
    }

    public function testAddValidation(): void
    {
        $mockValidation = $this->createMock(GraphQlBatchValidationInterface::class);

        $this->validationBatchComposite->add($mockValidation);

        $reflection = new \ReflectionClass($this->validationBatchComposite);
        $property = $reflection->getProperty('validations');
        $property->setAccessible(true);
        $validations = $property->getValue($this->validationBatchComposite);

        $this->assertCount(1, $validations);
        $this->assertSame($mockValidation, $validations[0]);
    }

    public function testValidateCallsChildValidations(): void
    {
        $mockValidation1 = $this->createMock(GraphQlBatchValidationInterface::class);
        $mockValidation2 = $this->createMock(GraphQlBatchValidationInterface::class);
        $mockRequestCommand = $this->createMock(GraphQlBatchRequestCommand::class);

        $mockValidation1->expects($this->once())
            ->method('validate')
            ->with($mockRequestCommand);

        $mockValidation2->expects($this->once())
            ->method('validate')
            ->with($mockRequestCommand);

        $this->validationBatchComposite->add($mockValidation1);
        $this->validationBatchComposite->add($mockValidation2);

        $this->validationBatchComposite->validate($mockRequestCommand);
    }
}
