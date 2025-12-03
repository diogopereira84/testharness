<?php
declare(strict_types=1);

namespace Fedex\Company\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Fedex\Catalog\Api\AttributeHandlerInterface;
use Magento\Framework\Phrase;

class UpdateAttributeOptionsTest extends TestCase
{
    protected $attributeHandlerInterfaceMock;
    protected $inputInterfaceMock;
    protected $outputInterfaceMock;
    protected $command;

    protected function setUp(): void
    {
        // Mock dependencies
        $this->attributeHandlerInterfaceMock = $this->getMockBuilder(AttributeHandlerInterface::class)
            ->getMock();
        $this->inputInterfaceMock = $this->getMockBuilder(InputInterface::class)
            ->getMock();
        $this->outputInterfaceMock = $this->getMockBuilder(OutputInterface::class)
            ->getMock();

        $this->command = new class ($this->attributeHandlerInterfaceMock) extends UpdateAttributeOptions {
            public function callExecute(InputInterface $input, OutputInterface $output)
            {
                return $this->execute($input, $output);
            }
        };
    }

    /**
     * test executeSuccess
     *
     * @return void
     */
    public function testExecuteSuccess(): void
    {
        $this->attributeHandlerInterfaceMock->expects($this->once())
            ->method('addAttributeOption');

        $this->outputInterfaceMock->expects($this->once())
            ->method('writeln')
            ->with('<info>Attribute options updated successfully.</info>');

        $result = $this->command->callExecute($this->inputInterfaceMock, $this->outputInterfaceMock);

        $this->assertEquals(Cli::RETURN_SUCCESS, $result);
    }

    /**
     * test executeFailure
     *
     * @return void
     */
   public function testExecuteFailure(): void
    {
        $exceptionMessage = 'Error message';
        $exceptionPhrase = new Phrase($exceptionMessage);

        $this->attributeHandlerInterfaceMock->expects($this->any())
            ->method('addAttributeOption')
            ->willThrowException(new LocalizedException($exceptionPhrase));

        $this->outputInterfaceMock->expects($this->once())
            ->method('writeln')
            ->with('<error> Fedex\Company\Console\Command\UpdateAttributeOptions::execute:57 Error message</error>');

        $result = $this->command->callExecute($this->inputInterfaceMock, $this->outputInterfaceMock);

        $this->assertEquals(Cli::RETURN_FAILURE, $result);
    }
}
