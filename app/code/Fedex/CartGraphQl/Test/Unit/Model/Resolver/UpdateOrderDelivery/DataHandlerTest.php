<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Resolver\UpdateOrderDelivery;

use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\DataHandler;
use Magento\Quote\Model\Quote;
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\Data\PickupData;
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\Data\ShippingData;

class DataHandlerTest extends TestCase
{
    protected $pickupDataMock;
    protected $shippingDataMock;
    protected $dataHandler;
    protected function setUp(): void
    {
        $this->pickupDataMock = $this->createMock(PickupData::class);
        $this->shippingDataMock = $this->createMock(ShippingData::class);

        $this->dataHandler = new DataHandler([$this->pickupDataMock, $this->shippingDataMock]);
    }

    public function testExecute()
    {
        $cart = $this->createMock(Quote::class);
        $data = ['key' => 'value'];

        $this->pickupDataMock->expects($this->once())
            ->method('setData')
            ->with($cart, $data);

        $this->shippingDataMock->expects($this->once())
            ->method('setData')
            ->with($cart, $data);

        $this->dataHandler->execute($cart, $data);
    }
}
