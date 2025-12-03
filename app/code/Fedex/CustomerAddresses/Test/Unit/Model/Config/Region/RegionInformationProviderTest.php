<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CoreApi\Test\Unit\Model\Config;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Directory\Api\Data\CountryInformationInterface;
use Magento\Directory\Api\Data\RegionInformationInterface;
use Fedex\CustomerAddresses\Model\Config\Region\RegionInformationProvider;

/**
 * Test class for AbstractConfig
 */
class RegionInformationProviderTest extends TestCase
{
    /**
     * @var CountryInformationAcquirerInterface|MockObject
     */
    protected $countryInformationAcquirerInterface;

    /**
     * @var CountryInformationInterface|MockObject
     */
    protected $countryInformationInterface;

    /**
     * @var RegionInformationInterface|MockObject
     */
    protected $regionInformationInterface;

    /**
     * @var RegionInformationProvider|MockObject
     */
    protected $regionInformationProvider;

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

        $this->regionInformationProvider = $this->getMockForAbstractClass(
            RegionInformationProvider::class,
            [
                'countryInformationAcquirer' => $this->countryInformationAcquirerInterface
            ]
        );
    }

    /**
     * Test testToOptionArray function
     *
     * @return string
     */
    public function testToOptionArray()
    {
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
        $response = [
                [
                    'value' => 'Alabama',
                    'label' => 'Alabama'
                ]
            ];
        $this->assertEquals($response, $this->regionInformationProvider->toOptionArray());
    }
}
