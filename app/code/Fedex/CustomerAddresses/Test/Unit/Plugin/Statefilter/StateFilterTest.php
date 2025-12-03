<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CoreApi\Test\Unit\Plugin\Statefilter;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\CustomerAddresses\Plugin\Statefilter\StateFilter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Directory\Api\Data\CountryInformationInterface;
use Magento\Directory\Api\Data\RegionInformationInterface;
use Magento\Directory\Api\CountryInformationAcquirerInterface;

/**
 * Test class for AbstractConfig
 */
class StateFilterTest extends TestCase
{
    protected $countryInformationInterface;
    protected $regionInformationInterface;
    /**
     * @var StateFilter|MockObject
     */
    protected $stateFilter;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigInterface;

    /**
     * @var CountryInformationAcquirerInterface|MockObject
     */
    protected $countryInformationAcquirerInterface;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->countryInformationAcquirerInterface = $this->getMockBuilder(CountryInformationAcquirerInterface::class)
                                                    ->disableOriginalConstructor()
                                                    ->getMockForAbstractClass();
                                                
        $this->countryInformationInterface = $this->getMockBuilder(CountryInformationInterface::class)
                                                    ->disableOriginalConstructor()
                                                    ->getMockForAbstractClass();
                                                
        $this->regionInformationInterface = $this->getMockBuilder(RegionInformationInterface::class)
                                                    ->disableOriginalConstructor()
                                                    ->getMockForAbstractClass();

        $this->scopeConfigInterface = $this->getMockBuilder(ScopeConfigInterface::class)
                                                    ->disableOriginalConstructor()
                                                    ->getMockForAbstractClass();

        $this->stateFilter = $this->getMockForAbstractClass(
            StateFilter::class,
            [
                'scopeConfig' => $this->scopeConfigInterface,
                'countryInformationAcquirer' => $this->countryInformationAcquirerInterface
            ]
        );
    }

    /**
     * Test testAfterToOptionArray function
     *
     * @return void
     */
    public function testAfterToOptionArray()
    {
        $this->scopeConfigInterface->expects($this->any())->method('getValue')->willReturn('Alabama');
        $this->regionInformationInterface->expects($this->any())->method('getId')->willReturn(1);
        $this->regionInformationInterface->expects($this->any())->method('getName')->willReturn('Alabama');
        $regionsIterator = new \ArrayIterator([$this->regionInformationInterface]);

        $this->countryInformationInterface->expects($this->any())->method('getId')->willReturn('US');
        $this->countryInformationInterface->expects($this->any())->method('getFullNameLocale')
                                            ->willReturn('United States');
        $this->countryInformationInterface->expects($this->any())->method('getAvailableRegions')
                                            ->willReturn($regionsIterator);
        $countriesIterator = new \ArrayIterator([$this->countryInformationInterface]);

        $this->countryInformationAcquirerInterface->expects($this->any())->method('getCountriesInfo')
                                                    ->willReturn($countriesIterator);
        $option = ['label' => 'Alabama', 'code' => 'AL', 'value' => 'AL', 'title' => 'Alabama', 'country_id' => 'US'];
        $response = [['value' => 'AL', 'title' => 'Alabama', 'country_id' => 'US', 'label' => '']];
        $this->assertEquals($response, $this->stateFilter->afterToOptionArray("UnitTesting", [$option]));
    }
}
