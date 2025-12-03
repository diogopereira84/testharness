<?php
/**
 * @category    Fedex
 * @package     Fedex_ShippingEstimator
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\ShippingEstimator\Test\Unit\Block;

use Fedex\ShippingEstimator\Model\Config\ShippingEstimatorConfig;
use \Magento\Directory\Api\CountryInformationAcquirerInterface;
use Fedex\ShippingEstimator\Block\States;
use Magento\Directory\Api\Data\CountryInformationInterface;
use Magento\Directory\Api\Data\RegionInformationInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class StatesTest extends TestCase
{

    protected $countryInformationAcquirerInterface;
    protected $countryInformationInterface;
    protected $regionInformationInterface;
    protected $shippingEstimatorConfig;
    protected $stateConfigProvider;
    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->countryInformationAcquirerInterface = $this->getMockBuilder(CountryInformationAcquirerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCountriesInfo', 'getAvailableRegions', 'getName', 'getCode','getId'])
            ->getMockForAbstractClass();

        $this->countryInformationInterface = $this->getMockBuilder(CountryInformationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->regionInformationInterface = $this->getMockBuilder(RegionInformationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->shippingEstimatorConfig = $this->createMock(
            ShippingEstimatorConfig::class
        );

        $ObjectManagerHelper = new ObjectManager($this);
        $this->stateConfigProvider = $ObjectManagerHelper->getObject(
            States::class,
            [
                'countryInformationAcquirer' => $this->countryInformationAcquirerInterface,
                'ShippingEstimatorConfig' => $this->shippingEstimatorConfig,
            ]
        );
    }

    /**
     * @return void
     */
    public function testGetRegionsOfCountry():void
    {
        $regions[]=[
            'id'=>12,
            'code'=>"TX",
            'name'=>'Texas'
        ];

        $this->countryInformationAcquirerInterface->expects($this->once())->method('getCountryInfo')
            ->willReturn($this->countryInformationInterface);
        $this->countryInformationInterface->expects($this->any())->method('getAvailableRegions')
            ->willReturn([$this->regionInformationInterface]);
        $this->shippingEstimatorConfig->expects($this->any())->method('getExcludedStates')->willReturn([]);
        $this->regionInformationInterface->expects($this->any())->method('getName')->willReturn("Texas");
        $this->regionInformationInterface->expects($this->any())->method('getCode')->willReturn("TX");
        $this->regionInformationInterface->expects($this->any())->method('getId')->willReturn(12);
        $this->assertEquals($regions, $this->stateConfigProvider->getRegionsOfCountry('US'));
    }
}
