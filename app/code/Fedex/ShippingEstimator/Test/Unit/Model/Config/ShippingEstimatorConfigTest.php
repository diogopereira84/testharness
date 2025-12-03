<?php
/**
 * @category    Fedex
 * @package     Fedex_ShippingEstimator
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\ShippingEstimator\Test\Unit\Model\Config;

use PHPUnit\Framework\TestCase;
use \Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\ShippingEstimator\Model\Config\ShippingEstimatorConfig;
use Exception;

class ShippingEstimatorConfigTest extends TestCase
{
    /**
     * @var LoggerInterface $logger
     */
    protected LoggerInterface $loggerMock;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfigMock;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManagerMock;
    /**
     * @var ShippingEstimatorConfig
     */
    private ShippingEstimatorConfig $config;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock = $this->getMockBuilder('Magento\Store\Api\Data\StoreInterface')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMockForAbstractClass();
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')->willReturn($storeMock);

        $this->config = new ShippingEstimatorConfig(
            $this->loggerMock,
            $this->scopeConfigMock,
            $this->storeManagerMock
        );
    }
    /**
     * @return void
     */
    public function testGetExcludedStates(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                ShippingEstimatorConfig::XPATH_US_STATE_FILTER,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn("TX,VA,TA");

        $this->assertEquals(['TX','VA','TA'], $this->config->getExcludedStates());
    }

    /**
     * @return void
     */
    public function testGetExcludedStatesWithException(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                ShippingEstimatorConfig::XPATH_US_STATE_FILTER,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn([]);
        $this->scopeConfigMock->method('getValue')
            ->willThrowException(new Exception('error message'));

        $this->assertEquals([], $this->config->getExcludedStates());
    }

    /**
     * @return void
     */
    public function testGetExcludedStatesWithEmpty(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                ShippingEstimatorConfig::XPATH_US_STATE_FILTER,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn([]);
        $this->scopeConfigMock->method('getValue')
            ->willThrowException(new Exception('error message'));

        $this->assertEquals([], $this->config->getExcludedStates());
    }
}
