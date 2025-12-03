<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Model;

use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\GraphQl\Model\Query\Context;
use PHPUnit\Framework\TestCase;

class GraphQlBatchRequestCommandTest extends TestCase
{
    /**
     * @var GraphQlBatchRequestCommand
     */
    protected GraphQlBatchRequestCommand $graphQlRequestCommand;
    /**
     * @var Field|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $fieldMock;
    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextMock;

    protected function setUp(): void
    {
        $this->fieldMock = $this->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->graphQlRequestCommand = new GraphQlBatchRequestCommand(
            $this->fieldMock,
            $this->contextMock,
            ['someRequests'],
            ['result']
        );
    }

    public function testGets()
    {
        $this->assertEquals($this->fieldMock, $this->graphQlRequestCommand->getField());
        $this->assertEquals($this->contextMock, $this->graphQlRequestCommand->getContext());
        $this->assertEquals(['someRequests'], $this->graphQlRequestCommand->getRequests());
        $this->assertEquals(['result'], $this->graphQlRequestCommand->getResult());
    }
}
