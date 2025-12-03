<?php

declare(strict_types=1);

namespace Fedex\Catalog\Test\Unit\Console\Command;

use Fedex\Catalog\Console\Command\UpdateFilterablePublishAttributeCommand;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

class UpdateFilterablePublishAttributeCommandTest extends TestCase
{
    /** @var EavConfig|MockObject */
    private $eavConfigMock;

    /** @var State|MockObject */
    private $stateMock;

    /** @var UpdateFilterablePublishAttributeCommand */
    private $command;

    /** @var InputInterface|MockObject */
    private $inputMock;

    /** @var OutputInterface|MockObject */
    private $outputMock;

    /** @var LoggerInterface|MockObject */
    private $loggerMock;

    protected function setUp(): void
    {
        $this->eavConfigMock = $this->createMock(EavConfig::class);
        $this->stateMock = $this->createMock(State::class);
        $this->inputMock = $this->createMock(InputInterface::class);
        $this->outputMock = $this->createMock(OutputInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->command = new UpdateFilterablePublishAttributeCommand(
            $this->eavConfigMock,
            $this->stateMock,
            $this->loggerMock
        );
    }

    /**
     * Summary of testExecuteSuccessfullyUpdatesAttribute
     * @return void
     */
    public function testExecuteSuccessfullyUpdatesAttribute(): void
    {
        $attributeMock = $this->getMockBuilder(\Magento\Eav\Model\Entity\Attribute::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'setData', 'save'])
            ->getMock();

        $attributeMock->method('getId')->willReturn(123);

        // Expect setData() calls for all required keys
        $attributeMock->expects($this->exactly(4))
            ->method('setData')
            ->withConsecutive(
                ['source_model', 'Magento\Eav\Model\Entity\Attribute\Source\Boolean'],
                ['is_used_in_grid', 1],
                ['is_visible_in_grid', 1],
                ['is_filterable_in_grid', 1]
            )
            ->willReturnSelf();

        $attributeMock->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $this->stateMock->expects($this->once())
            ->method('setAreaCode')
            ->with('adminhtml');

        $this->eavConfigMock->expects($this->once())
            ->method('getAttribute')
            ->with(\Magento\Catalog\Model\Product::ENTITY, 'published')
            ->willReturn($attributeMock);

        $this->outputMock->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('Successfully updated'));

        $result = $this->command->run($this->inputMock, $this->outputMock);

        $this->assertEquals(Cli::RETURN_SUCCESS, $result);
        $this->assertSame(Cli::RETURN_SUCCESS, $result);

    }

    /**
     * Summary of testExecuteWhenAttributeNotFound
     * @return void
     */
    public function testExecuteWhenAttributeNotFound(): void
    {
        $this->stateMock->expects($this->once())
            ->method('setAreaCode')
            ->with('adminhtml');

        $this->eavConfigMock->expects($this->once())
            ->method('getAttribute')
            ->willReturn(null);

        $this->outputMock->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('Attribute "published" not found!'));

        $result = $this->command->run($this->inputMock, $this->outputMock);

        $this->assertEquals(Cli::RETURN_FAILURE, $result);
        $this->assertSame(Cli::RETURN_FAILURE, $result);
    }

    /**
     * Summary of testExecuteHandlesException
     * @return void
     */
    public function testExecuteHandlesException(): void
    {
        $this->stateMock->expects($this->once())
            ->method('setAreaCode')
            ->with('adminhtml');

        $this->eavConfigMock->expects($this->once())
            ->method('getAttribute')
            ->willThrowException(new \Exception('Test exception'));

        $this->outputMock->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('Test exception'));

        $result = $this->command->run($this->inputMock, $this->outputMock);

        $this->assertEquals(Cli::RETURN_FAILURE, $result);
        $this->assertSame(Cli::RETURN_FAILURE, $result);
    }
}
