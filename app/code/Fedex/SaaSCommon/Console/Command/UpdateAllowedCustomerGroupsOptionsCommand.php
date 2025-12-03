<?php
declare(strict_types=1);
namespace Fedex\SaaSCommon\Console\Command;

use Fedex\SaaSCommon\Api\CustomerGroupAttributeHandlerInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * UpdateAllowedCustomerGroupsOptionsCommand class
 */
class UpdateAllowedCustomerGroupsOptionsCommand extends Command
{
    /**
     * Construct.
     *
     * @param CustomerGroupAttributeHandlerInterface $attributeHandlerInterface
     * @param string|null $name
     */
    public function __construct(
        private CustomerGroupAttributeHandlerInterface $attributeHandlerInterface,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * Command configure
     */
    protected function configure(): void
    {
        $this->setName('fedex:allowed-customer-groups-options:update')
            ->setDescription('Create new option for a multi-select allowed_customer_groups attribute');
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
            $this->attributeHandlerInterface->updateAllAttributeOptions();

            $output->writeln('<info>Attribute options updated successfully.</info>');
            return Cli::RETURN_SUCCESS;
        } catch (LocalizedException $e) {
            $output->writeln('<error> ' . __METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }
    }
}
