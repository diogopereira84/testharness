<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Model\Validation;

use Fedex\GraphQl\Api\GraphQlValidationInterface;
use Fedex\GraphQl\Model\GraphQlRequestCommand;
use Fedex\GraphQl\Model\Validation\ValidationComposite;
use PHPUnit\Framework\TestCase;

class ValidationCompositeTest extends TestCase
{
    /**
     * @var ValidationComposite
     */
    private ValidationComposite $validationComposite;

    protected function setUp(): void
    {
        $this->validationComposite =  new ValidationComposite();
    }

    public function testValidationComposite()
    {
        $graphQlValidationInterfaceMock = $this->getMockBuilder(GraphQlValidationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->validationComposite->add($graphQlValidationInterfaceMock);

        $graphQlRequestCommandMock = $this->getMockBuilder(GraphQlRequestCommand::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertEquals(null, $this->validationComposite->validate($graphQlRequestCommandMock));
    }
}
