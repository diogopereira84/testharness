<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PatchData\Console\Command;

use Fedex\PatchData\Model\ResourceModel\Patch as PatchResource;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\FileSystemException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Fedex\PatchData\Model\PatchFactory;
use Fedex\PatchData\Model\PatchValidator;

class RemovePatch extends Command
{
    /**
     * @param PatchFactory $patchFactory
     * @param PatchResource $patchResource
     * @param PatchValidator $patchValidator
     */
    public function __construct(
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
        $this->setName('fedex:patch:data:rm')
            ->setDescription('Update fxo_patch_list status 0')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to the data patch script');
    }

    /**
     * @throws AlreadyExistsException
     * @throws FileSystemException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $patchPath = $input->getArgument('path');
        $patchName =  $this->patchValidator->getPatchNameSpace($patchPath);

        if (!$this->patchValidator->isPatchPathExists($patchPath)) {
            $output->writeln("<error>The path '$patchPath' does not exist.</error>");
            return Command::FAILURE;
        }

        $patch = $this->patchFactory->create(\Fedex\PatchData\Model\Patch::class);
        $this->patchResource->load($patch, $patchName, 'patch_name');

        if (!$patch->getId()) {
            $output->writeln("<error>Patch '$patchName' not found.</error>");
            return Command::FAILURE;
        }

        $patch->setData('patch_status', 0);
        $patch->setData('updated_at', date('Y-m-d H:i:s'));
        $this->patchResource->save($patch);

        $output->writeln("<info>Patch '$patchName' marked as removed.</info>");
        return Command::SUCCESS;
    }
}

