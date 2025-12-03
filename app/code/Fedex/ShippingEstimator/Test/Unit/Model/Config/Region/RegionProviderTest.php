<?php
/**
 * @category    Fedex
 * @package     Fedex_ShippingEstimator
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\ShippingEstimator\Test\Unit\Model\Config\Region;

use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Fedex\ShippingEstimator\Model\Config\Region\RegionProvider;
use Magento\Directory\Api\Data\CountryInformationInterface;
use Magento\Directory\Api\Data\RegionInformationInterface;
use PHPUnit\Framework\TestCase;

class RegionProviderTest extends TestCase
{
    protected $countryInformationAcquirerInterfaceMock;
    protected $countryInformationInterfaceMock;
    protected $regionInformationInterfaceMock;
    protected $regionInformationProvider;
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->countryInformationAcquirerInterfaceMock = $this->getMockBuilder(CountryInformationAcquirerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->countryInformationInterfaceMock = $this->getMockBuilder(CountryInformationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->regionInformationInterfaceMock = $this->getMockBuilder(RegionInformationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->regionInformationProvider = $this->getMockForAbstractClass(
            RegionProvider::class,
            [
                'countryInformationAcquirer' => $this->countryInformationAcquirerInterfaceMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testToOptionArray():void
    {
        $this->regionInformationInterfaceMock->expects($this->any())->method('getId')->willReturn(1);
        $this->regionInformationInterfaceMock->expects($this->any())->method('getName')->willReturn('Alabama');
        $regionsIterator = new \ArrayIterator([$this->regionInformationInterfaceMock]);

        $this->countryInformationInterfaceMock->expects($this->any())->method('getId')->willReturn('US');
        $this->countryInformationInterfaceMock->expects($this->any())->method('getFullNameLocale')
            ->willReturn('United States');
        $this->countryInformationInterfaceMock->expects($this->any())->method('getAvailableRegions')
            ->willReturn($regionsIterator);
        $countriesIterator = new \ArrayIterator([$this->countryInformationInterfaceMock]);

        $this->countryInformationAcquirerInterfaceMock->expects($this->any())->method('getCountriesInfo')
            ->willReturn($countriesIterator);
        $response = [
            [
                'value' => 1,
                'label' => 'Alabama'
            ]
        ];
        $this->assertEquals($response, $this->regionInformationProvider->toOptionArray());
    }
}
