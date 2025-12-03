<?php

namespace Fedex\SaaSCommon\Test\Unit\Console\Command;

use Fedex\SaaSCommon\Console\Command\UpdateAllowedCustomerGroupsOptionsCommand;
use Fedex\SaaSCommon\Api\CustomerGroupAttributeHandlerInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAllowedCustomerGroupsOptionsCommandTest extends TestCase
{
    public function testConfigureSetsNameAndDescription()
    {
        $handler = $this->createMock(CustomerGroupAttributeHandlerInterface::class);
        $command = new UpdateAllowedCustomerGroupsOptionsCommand($handler);
        $command->setName('test:name');
        $command->setDescription('desc');
        $this->assertEquals('test:name', $command->getName());
        $this->assertEquals('desc', $command->getDescription());
    }

    public function testExecuteSuccess()
    {
        $handler = $this->createMock(CustomerGroupAttributeHandlerInterface::class);
        $handler->expects($this->once())->method('updateAllAttributeOptions');

        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())->method('writeln')->with($this->stringContains('Attribute options updated successfully'));

        $command = new UpdateAllowedCustomerGroupsOptionsCommand($handler);
        $result = $command->run($input, $output);
        $this->assertEquals(Cli::RETURN_SUCCESS, $result);
    }

    public function testExecuteFailure()
    {
        $handler = $this->createMock(CustomerGroupAttributeHandlerInterface::class);
        $handler->expects($this->once())->method('updateAllAttributeOptions')->willThrowException(new LocalizedException(__('fail')));

        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())->method('writeln')->with($this->stringContains('fail'));

        $command = new UpdateAllowedCustomerGroupsOptionsCommand($handler);
        $result = $command->run($input, $output);
        $this->assertEquals(Cli::RETURN_FAILURE, $result);
    }
}

