<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Model;

use Fedex\GraphQl\Model\GraphQlRequestCommand;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\Context;
use PHPUnit\Framework\TestCase;

class GraphQlRequestCommandTest extends TestCase
{
    /**
     * @var GraphQlRequestCommand
     */
    protected GraphQlRequestCommand $graphQlRequestCommand;
    /**
     * @var Field|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $fieldMock;
    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextMock;
    /**
     * @var ResolveInfo|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $resolveInfoMock;

    protected function setUp(): void
    {
        $this->fieldMock = $this->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resolveInfoMock = $this->getMockBuilder(ResolveInfo::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->graphQlRequestCommand = new GraphQlRequestCommand(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            ['someValue'],
            ['someArg'],
            ['result']
        );
    }

    public function testGets()
    {
        $this->assertEquals($this->fieldMock, $this->graphQlRequestCommand->getField());
        $this->assertEquals($this->contextMock, $this->graphQlRequestCommand->getContext());
        $this->assertEquals($this->resolveInfoMock, $this->graphQlRequestCommand->getInfo());
        $this->assertEquals(['someValue'], $this->graphQlRequestCommand->getValue());
        $this->assertEquals(['someArg'], $this->graphQlRequestCommand->getArgs());
        $this->assertEquals(['result'], $this->graphQlRequestCommand->getResult());
    }
}
