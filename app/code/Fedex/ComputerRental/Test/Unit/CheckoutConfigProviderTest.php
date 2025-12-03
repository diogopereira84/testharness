<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\ComputerRental\Test\Unit\Model;

use Fedex\ComputerRental\Model\CheckoutConfigProvider;
use Fedex\ComputerRental\Api\CRDataInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckoutConfigProviderTest extends TestCase
{
    /**
     * @var CheckoutConfigProvider
     */
    protected $provider;

    /**
     * @var MockObject|CRDataInterface
     */
    protected $crDataMock;

    protected function setUp(): void
    {
        $this->crDataMock = $this->createMock(CRDataInterface::class);
        $this->provider = new CheckoutConfigProvider($this->crDataMock);
    }

    public function testGetConfig()
    {
        $this->crDataMock->expects($this->once())
            ->method('getStoreCodeFromSession')
            ->willReturn('0798');

        $this->crDataMock->expects($this->once())
            ->method('getLocationCode')
            ->willReturn('DNEK');
        $this->crDataMock->expects($this->once())
            ->method('isRetailCustomer')
            ->willReturn('true');

        $expectedConfig = [
            'CRStoreCode' => '0798',
            'CRLocationCode' => 'DNEK',
            'isRetailCustomer' => 'true',
        ];

        $this->assertEquals($expectedConfig, $this->provider->getConfig());
    }
}
