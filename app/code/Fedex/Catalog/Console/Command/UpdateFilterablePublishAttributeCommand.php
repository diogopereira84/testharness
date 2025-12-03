<?php
declare(strict_types=1);

namespace Fedex\Catalog\Console\Command;

use Magento\Framework\App\State;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product;

class UpdateFilterablePublishAttributeCommand extends Command
{
    /**
     * Constructor.
     *
     * @param EavConfig $eavConfig EAV configuration model.
     * @param State $appState Application state instance.
     * @param LoggerInterface $logger
     */
    public function __construct(
        private EavConfig $eavConfig,
        private State $appState,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * Configure command
     */
    protected function configure(): void
    {
        $this->setName('fedex:catalog:update-publish-filter')
            ->setDescription('Update "published" attribute to be filterable in product grid.');
        parent::configure();
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode('adminhtml');

            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, 'published');

            if (!$attribute || !$attribute->getId()) {
                 $this->logger->error('The published attribute was not found while executing the update-publish-filter console command.');
                $output->writeln('<error>Attribute "published" not found!</error>');
                return Cli::RETURN_FAILURE;
            }

            $attribute->setData('source_model', Boolean::class);
            $attribute->setData('is_used_in_grid', 1);
            $attribute->setData('is_visible_in_grid', 1);
            $attribute->setData('is_filterable_in_grid', 1);
            $attribute->save();

            $output->writeln('<info>Successfully updated "published" attribute to be filterable.</info>');
            return Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
             $this->logger->error('An error occurred while updating the published attribute: '  . $e->getMessage());
            return Cli::RETURN_FAILURE;
        }
    }
}
