<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Test\Unit\Service\Address;

use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceAdmin\Service\Address\MiraklShippingAddressFormatter;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Directory\Api\Data\CountryInformationInterface;

class FormatterTest extends TestCase
{
    /**
     * @return void
     */
    public function testFormatBasic(): void
    {
        $regionMock = $this->createMock(Region::class);
        $regionMock->method('getId')->willReturn(10);
        $regionMock->method('getName')->willReturn('StateName');

        $regionFactoryMock = $this->createMock(RegionFactory::class);
        $regionFactoryMock->method('create')->willReturn($regionMock);
        $regionMock->method('load')->willReturn($regionMock);

        $countryMock = $this->createMock(CountryInformationInterface::class);
        $countryMock->method('getFullNameLocale')->willReturn('United States');

        $countryInfoMock = $this->createMock(CountryInformationAcquirerInterface::class);
        $countryInfoMock->method('getCountryInfo')->willReturn($countryMock);

        $formatter = new MiraklShippingAddressFormatter($regionFactoryMock, $countryInfoMock);

        $data = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'company' => 'Acme Inc.',
            'street' => ['Street 1', 'Street 2'],
            'city' => 'Los Angeles',
            'regionId' => 10,
            'regionCode' => 'CA',
            'postcode' => '90001',
            'countryId' => 'US',
            'telephone' => '123-456-7890'
        ];

        $formatted = $formatter->format($data);

        $this->assertStringContainsString('John Doe', $formatted);
        $this->assertStringContainsString('Acme Inc.', $formatted);
        $this->assertStringContainsString('Street 1', $formatted);
        $this->assertStringContainsString('Los Angeles, StateName, 90001', $formatted);
        $this->assertStringContainsString('United States', $formatted);
        $this->assertStringContainsString('tel:123-456-7890', $formatted);
    }
}
