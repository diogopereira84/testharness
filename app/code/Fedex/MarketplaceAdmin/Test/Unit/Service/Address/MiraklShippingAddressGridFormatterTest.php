<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Test\Unit\Service\Address;

use Fedex\MarketplaceAdmin\Service\Address\MiraklShippingAddressGridFormatter;
use Fedex\MarketplaceAdmin\Service\Address\RegionNameResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MiraklShippingAddressGridFormatterTest extends TestCase
{
    /** @var RegionNameResolver&MockObject */
    private RegionNameResolver $resolver;

    private MiraklShippingAddressGridFormatter $formatter;

    protected function setUp(): void
    {
        $this->resolver  = $this->createMock(RegionNameResolver::class);
        $this->formatter = new MiraklShippingAddressGridFormatter($this->resolver);
    }

    /**
     * @dataProvider provideAddresses
     */
    public function testFormatInline(array $input, ?array $regionStub, string $expected): void
    {
        if (!empty($input['regionId'])) {
            $regionId = (int)$input['regionId'];

            if ($regionStub && ($regionStub['hasId'] ?? false)) {
                $this->resolver->method('nameById')
                    ->with($regionId)
                    ->willReturn((string)($regionStub['name'] ?? ''));
            } else {
                $this->resolver->method('nameById')
                    ->with($regionId)
                    ->willReturn(null);
            }
        } else {
            $this->resolver->method('nameById')->willReturn(null);
        }

        $this->assertSame($expected, $this->formatter->formatInline($input));
    }

    /**
     * @return array[]
     */
    public static function provideAddresses(): array
    {
        return [
            'regionId resolves to region name, multi-line street' => [
                'input' => [
                    'street'    => ['7900 Legacy Dr', '', 'Suite 100'],
                    'city'      => 'Plano',
                    'regionId'  => 57,
                    'regionCode'=> 'TX',
                    'postcode'  => '75024',
                ],
                'regionStub' => ['hasId' => true, 'name' => 'Texas'],
                'expected'   => '7900 Legacy Dr, Suite 100,Plano,Texas,75024',
            ],
            'fallback to regionCode when no regionId' => [
                'input' => [
                    'street'    => ['189 8th Avenue'],
                    'city'      => 'New York',
                    'regionCode'=> 'NY',
                    'postcode'  => '10011-1602',
                ],
                'regionStub' => ['hasId' => false, 'name' => null],
                'expected'   => '189 8th Avenue,New York,10011-1602',
            ],
            'trims and drops empty parts' => [
                'input' => [
                    'street'    => ['  123  Main  ', ''],
                    'city'      => '  Springfield ',
                    'regionCode'=> '',
                    'postcode'  => '  12345 ',
                ],
                'regionStub' => ['hasId' => false, 'name' => null],
                'expected'   => '123  Main,Springfield,12345',
            ],
            'all parts empty yields empty string' => [
                'input' => [
                    'street'    => [],
                    'city'      => '',
                    'regionCode'=> '',
                    'postcode'  => '',
                ],
                'regionStub' => ['hasId' => false, 'name' => null],
                'expected'   => '',
            ],
        ];
    }
}
