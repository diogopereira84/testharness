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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Filesystem\Driver\File;
use Fedex\PatchData\Model\PatchValidator;

class ShowPatch extends Command
{
    /**
     * @param File $fileDriver
     * @param PatchValidator $patchValidator
     */
    public function __construct(
        private File $fileDriver,
        private PatchValidator $patchValidator
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    public function configure(): void
    {
        $this->setName('fedex:patch:data:show')
            ->setDescription('Displays the content of a data patch script')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to the data patch script');
    }

    /**
     * @throws FileSystemException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');

        if (!$this->patchValidator->isPatchPathExists($path)) {
            $output->writeln("<error>The path '$path' does not exist.</error>");
            return Command::FAILURE;
        }

        try {
            $content = $this->fileDriver->fileGetContents($path);
            $output->writeln("<info>Patch Content:</info>\n$content");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<error>Error reading patch: " . $e->getMessage() . "</error>");
            return Command::FAILURE;
        }
    }
}


