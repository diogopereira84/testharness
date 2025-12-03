<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Pay\Test\Unit\Model;

use Fedex\Pay\Model\PayConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class PayConfigProviderTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var PayConfigProvider $payConfigProvider
     */
    protected $payConfigProvider;

    /**
     * @var ScopeConfigInterface $scopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var CountryInformationAcquirerInterface $countryInterface
     */
    protected $countryInterface;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var ObjectManager
     */
    protected $objectManagerHelper;

    /* Test setup */
    protected function setUp(): void
    {
        $this->scopeConfigInterface = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->countryInterface = $this->getMockBuilder(CountryInformationAcquirerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCountriesInfo', 'getAvailableRegions', 'getName', 'getCode'])
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['critical'])
            ->getMockForAbstractClass();

         $objectManagerHelper = new ObjectManager($this);
         $this->payConfigProvider = $objectManagerHelper->getObject(
             PayConfigProvider::class,
             [
                 'scopeConfig' => $this->scopeConfigInterface,
                 'countryInformationAcquirer' => $this->countryInterface,
                 'logger' => $this->loggerMock
             ]
         );
    }

    /**
     * Test getConfig function
     *
     * @return array
     */
    public function testGetConfig()
    {
        $this->assertNotEmpty($this->payConfigProvider->getConfig());
    }

    /**
     * Test getSelectedStates function
     *
     * @return array
     */
    public function testGetSelectedStates()
    {
        $this->scopeConfigInterface->expects($this->once())->method('getValue')->willReturn("test,check");
        $this->countryInterface->expects($this->once())->method('getCountriesInfo')
        ->willReturn([$this->countryInterface]);
        $this->countryInterface->expects($this->any())->method('getAvailableRegions')
        ->willReturn([$this->countryInterface]);
        $this->countryInterface->expects($this->any())->method('getName')->willReturn("Texas");
        $this->countryInterface->expects($this->any())->method('getCode')->willReturn("TX");
        $this->assertSame([], $this->payConfigProvider->getSelectedStates());
    }
}
