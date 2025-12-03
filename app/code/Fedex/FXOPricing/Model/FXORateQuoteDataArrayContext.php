<?php
/**
 * @category    Fedex
 * @package     Fedex_FXOPricing
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\InStoreConfigurations\Api\ConfigInterface;
use Fedex\InStoreConfigurations\Model\Organization;

class FXORateQuoteDataArrayContext
{

    /**
     * Constructor
     *
     * @param ToggleConfig $toggleConfig
     * @param ConfigInterface $configInterface
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param RequestQueryValidator $requestQueryValidator
     * @param Organization $organization
     */
    public function __construct(
        private ToggleConfig $toggleConfig,
        private ConfigInterface $configInterface,
        private CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private RequestQueryValidator $requestQueryValidator,
        private Organization $organization
    ) {
    }

    /**
     * Returns ToggleConfig
     *
     * @return ToggleConfig
     */
    public function getToggleConfig(): ToggleConfig
    {
        return $this->toggleConfig;
    }

    /**
     * Returns ConfigInterface
     *
     * @return ConfigInterface
     */
    public function getConfigInterface(): ConfigInterface
    {
        return $this->configInterface;
    }

    /**
     * Returns CartIntegrationRepositoryInterface
     *
     * @return CartIntegrationRepositoryInterface
     */
    public function getCartIntegrationRepositoryInterface(): CartIntegrationRepositoryInterface
    {
        return $this->cartIntegrationRepository;
    }

    /**
     * Returns RequestQueryValidator
     *
     * @return RequestQueryValidator
     */
    public function getRequestQueryValidator(): RequestQueryValidator
    {
        return $this->requestQueryValidator;
    }

    /**
     * Returns Organization
     *
     * @return Organization
     */
    public function getOrganization(): Organization
    {
        return $this->organization;
    }

}
