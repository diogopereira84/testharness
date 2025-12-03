<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Model;

use Fedex\CoreApi\Gateway\Http\Client;
use Fedex\Canva\Gateway\Response\HandlerInterface;
use Fedex\Canva\Gateway\Request\BuilderInterface;
use Magento\Customer\Model\Session;
use Fedex\CoreApi\Gateway\Http\TransferFactory;
use Fedex\CoreApi\Gateway\Http\TransferInterface;
use Fedex\Canva\Api\Data\ConfigInterface as ModuleConfig;

class CanvaCredentials
{
    public function __construct(
        private BuilderInterface $builder,
        private ModuleConfig $moduleConfig,
        private TransferFactory $transferFactory,
        private Client $client,
        private HandlerInterface $handler,
        private Session $session
    )
    {
    }

    public function fetchSectionData(): void
    {
        if (!$this->session->getCanvaAccessToken() && !$this->session->getClientId()) {
            $this->fetch();
        }
    }

    public function fetch(): void
    {
        $request =  $this->client->request(
            $this->transferFactory->create(['data' => [
                TransferInterface::METHOD => 'POST',
                TransferInterface::URI => $this->moduleConfig->getUserTokenApiUrl(),
                TransferInterface::PARAMS => $this->builder->build()
            ]])
        );

        $userToken = $this->handler->handle($request);

        if ($userToken->getStatus()) {
            $this->session->setCanvaAccessToken($userToken->getAccessToken());
            $this->session->setClientId($userToken->getClientId());
        }
    }
}
