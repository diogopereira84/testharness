<?php

declare(strict_types=1);

namespace Fedex\NewRelic\Console;

use Laminas\Http\Client\Adapter\Exception\RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Fedex\NewRelic\Api\ConfigProviderInterface;
use Magento\Framework\HTTP\LaminasClientFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Cache\TypeListInterface;

class CreateNewRelicDeploymentMarker extends Command
{
    private const RETURN_SUCCESS = 0;

    /**
     * @param ConfigProviderInterface $configProvider
     * @param LaminasClientFactory $clientFactory
     * @param TimezoneInterface $timezoneInterface
     * @param LoggerInterface $logger
     * @param TypeListInterface $cacheTypeList
     * @param string|null $name
     */
    public function __construct(
        private readonly ConfigProviderInterface $configProvider,
        private readonly LaminasClientFactory $clientFactory,
        private readonly TimezoneInterface $timezoneInterface,
        private readonly LoggerInterface $logger,
        private readonly TypeListInterface $cacheTypeList,
        string|null $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName("fedex:newrelic:create-deployment-marker");
        $this->setDescription("Creates a new New Relic Deployment Marker.");
        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('New Relic Deployment Marker - Start');

        $apiKey = $this->configProvider->getApiKey();
        $applicationIdentifier = $this->configProvider->getAppIdentifier();

        if ($this->configProvider->canPerformDeploymentMarker()) {
            $this->logger->info(
                'New Relic Deployment Marker - Application ID '
                . $applicationIdentifier
            );
            $apiUrl = str_replace(
                "{{app_id}}",
                $applicationIdentifier,
                $this->configProvider->getApiUrl()
            );
            $client = $this->clientFactory->create();
            $client->setUri($apiUrl);
            $client->setMethod('POST');
            $client->setHeaders([
                'x-api-key' => $apiKey,
                'Content-Type' => 'applicaton/json'
            ]);
            $params = [
                'deployment[revision]' => $this->timezoneInterface
                    ->date()
                    ->format('Y.m.d.H:i.s'),
                'deployment[description]' => $this->configProvider->getDescription(),
                'deployment[changelog]' => $this->configProvider->getChangeLog(),
                'deployment[user]' => $this->configProvider->getUser()
            ];
            $client->setParameterPost($params);

            try {
                $response = $client->send();
                $this->logger->info(
                    'New Relic Deployment Marker - Response '
                    . $response->toString()
                );
            } catch (RuntimeException|\Exception $e) {
                $this->logger->error(
                    __METHOD__ . ':'
                    . __LINE__ . ' '
                    . 'New Relic Deployment Marker - '
                    . $e->getMessage()
                );
            }

            $this->configProvider->resetFields();
            $this->cacheTypeList->cleanType('config');
        }
        $this->logger->info('New Relic Deployment Marker - End');

        return self::RETURN_SUCCESS;
    }
}
