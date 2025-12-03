<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PatchData\Console\Command;

use Magento\Framework\Exception\FileSystemException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Fedex\PatchData\Model\PatchFactory;
use Fedex\PatchData\Model\ResourceModel\Patch as PatchResource;
use Fedex\PatchData\Model\PatchValidator;

class RunPatch extends Command
{
    /**
     * @param LoggerInterface $logger
     * @param PatchFactory $patchFactory
     * @param PatchResource $patchResource
     * @param PatchValidator $patchValidator
     */
    public function __construct(
        private LoggerInterface $logger,
        private PatchFactory $patchFactory,
        private PatchResource $patchResource,
        private PatchValidator $patchValidator
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    public function configure(): void
    {
        $this->setName('fedex:patch:data:run')
            ->setDescription('Executes a data patch script')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to the data patch script')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit the number of records', 100);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws FileSystemException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        $patchClass = $this->patchValidator->convertPathToClass($path);
        $patchName =  $this->patchValidator->getPatchNameSpace($path);

        $patchEntry = $this->patchFactory->create(\Fedex\PatchData\Model\Patch::class);
        $this->patchResource->load($patchEntry, $patchName, 'patch_name');

        if ($this->patchValidator->IsPatchExistsAndExecuted($patchName)) {
            $output->writeln("<error>Patch '$patchName' has already been executed or removed.</error>");
            return Command::FAILURE;
        }

        $limit = (int) $input->getOption('limit');

        if (!$this->patchValidator->isPatchPathExists($path)) {
            $output->writeln("<error>The path '$patchName' does not exist.</error>");
            $this->logger->error("Patch execution failed: path '$patchName' does not exist.");
            return Command::FAILURE;
        }

        try {
            $patchInstance = $this->patchFactory->create($patchClass);
            if (!method_exists($patchInstance, 'apply')) {
                throw new LocalizedException(__('The patch class must define an apply() method.'));
            }

            if (property_exists($patchInstance, 'limit')) {
                $patchInstance->limit = $limit;
            }

            $possibleReturn = $patchInstance->apply();

            if(!empty($possibleReturn)) {
                foreach ($possibleReturn as $messageReturn) {
                    $output->writeln($messageReturn);
                }
            }

            if (!$patchEntry->getId()) {
                $patchEntry->setData([
                    'patch_name' => $patchName,
                    'patch_status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            } else {
                $patchEntry->setData('patch_status', 1);
                $patchEntry->setData('updated_at', date('Y-m-d H:i:s'));
            }

            $this->patchResource->save($patchEntry);
            $output->writeln("<info>Patch '$patchName' executed successfully.</info>");
        } catch (\Exception $e) {
            $output->writeln("<error>Error executing patch: " . $e->getMessage() . "</error>");
            $this->logger->error("Error executing patch '$patchName': " . $e->getMessage());
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}

