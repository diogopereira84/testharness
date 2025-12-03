<?php
declare(strict_types=1);
namespace Fedex\Company\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Fedex\Catalog\Api\AttributeHandlerInterface;

/**
 * UpdateAttributeOptions class
 */
class UpdateAttributeOptions extends Command
{
    /**
     * Construct.
     *
     * @param AttributeHandlerInterface $attributeHandlerInterface
     * @param string|null $name
     */
    public function __construct(
        private AttributeHandlerInterface $attributeHandlerInterface,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * Command configure
     */
    protected function configure(): void
    {
        $this->setName('fedex:company:update-attribute-options')
            ->setDescription('Create new option for a multi-select shared_catalogs attribute');
        parent::configure();
    }

    /**
     * Executes the command to update attribute options.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): mixed
    {
        try {
            // Add option first compare shared catalog all ids and those missing add an option
            $this->attributeHandlerInterface->addAttributeOption();

            $output->writeln('<info>Attribute options updated successfully.</info>');
            return Cli::RETURN_SUCCESS;
        } catch (LocalizedException $e) {
            $output->writeln('<error> ' . __METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }
    }
}
