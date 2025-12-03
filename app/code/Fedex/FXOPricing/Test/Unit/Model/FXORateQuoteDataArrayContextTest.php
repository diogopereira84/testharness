<?php
/**
 * @category    Fedex
 * @package     Fedex_FXOPricing
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model;

use Fedex\FXOPricing\Model\FXORateQuoteDataArrayContext;
use PHPUnit\Framework\TestCase;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\InStoreConfigurations\Api\ConfigInterface;
use Fedex\InStoreConfigurations\Model\Organization;

/**
 * @covers \Fedex\FXOPricing\Model\FXORateQuoteDataArrayContext
 */
class FXORateQuoteDataArrayContextTest extends TestCase
{
    /**
     * @var ToggleConfig
     */
    private ToggleConfig $toggleConfig;

    /**
     * @var ConfigInterface
     */
    private ConfigInterface $configInterface;

    /**
     * @var CartIntegrationRepositoryInterface
     */
    private CartIntegrationRepositoryInterface $cartIntegrationRepository;

    /**
     * @var RequestQueryValidator
     */
    private RequestQueryValidator $requestQueryValidator;

    /**
     * @var Organization
     */
    private Organization $organization;

    /**
     * @var FXORateQuoteDataArrayContext
     */
    private FXORateQuoteDataArrayContext $context;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->toggleConfig = $this->createStub(ToggleConfig::class);
        $this->configInterface = $this->createStub(ConfigInterface::class);
        $this->cartIntegrationRepository = $this->createStub(CartIntegrationRepositoryInterface::class);
        $this->requestQueryValidator = $this->createStub(RequestQueryValidator::class);
        $this->organization = $this->createStub(Organization::class);

        $this->context = new FXORateQuoteDataArrayContext(
            $this->toggleConfig,
            $this->configInterface,
            $this->cartIntegrationRepository,
            $this->requestQueryValidator,
            $this->organization,
        );
    }

    /**
     * @return void
     */
    public function testGetToggleConfig()
    {
        $this->assertInstanceOf(ToggleConfig::class, $this->context->getToggleConfig());
    }

    /**
     * @return void
     */
    public function testGetConfigInterface()
    {
        $this->assertInstanceOf(ConfigInterface::class, $this->context->getConfigInterface());
    }

    /**
     * @return void
     */
    public function testGetCartIntegrationRepositoryInterface()
    {
        $this->assertInstanceOf(CartIntegrationRepositoryInterface::class,
            $this->context->getCartIntegrationRepositoryInterface());
    }

    /**
     * @return void
     */
    public function testGetRequestQueryValidator()
    {
        $this->assertInstanceOf(RequestQueryValidator::class, $this->context->getRequestQueryValidator());
    }

    /**
     * @return void
     */
    public function testGetOrganization()
    {
        $this->assertInstanceOf(Organization::class, $this->context->getOrganization());
    }
}
