<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model\RateQuoteRequestData;

use Fedex\SubmitOrderSidebar\Api\RateQuoteRequestDataInterface;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\TestCase;
use Fedex\SubmitOrderSidebar\Model\RateQuoteRequestData\ProductsMapper;
use PHPUnit\Framework\MockObject\MockObject;

class ProductMapperTest extends TestCase
{
    const PRODUCT_DATA = '{
        "external_prod":[{
            "catalogReference":["catalog_reference"],
            "preview_url":"https:\/\/example.com","fxo_product":true
        }]
    }';

    /**
     * @var ProductsMapper
     */
    private ProductsMapper $productsMapper;
    private MockObject|RateQuoteRequestDataInterface $rateQuoteRequest;

    protected function setUp(): void
    {
        $this->productsMapper = new ProductsMapper();
        $this->setData();
    }

    public function testPopulateWithArray()
    {
        $quoteItemMock = $this->createMock(\Magento\Quote\Model\Quote\Item::class);

        $optionMock = $this->getMockBuilder(Option::class)
            ->addMethods(['setValue'])
            ->onlyMethods(['getValue', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $optionMock->expects($this->once())->method('getValue')->willReturn(self::PRODUCT_DATA);
        $quoteItemMock->expects($this->any())->method('getId')->willReturn(1);
        $quoteItemMock->expects($this->once())->method('getOptionByCode')->willReturn($optionMock);

        $this->productsMapper->populateWithArray($this->rateQuoteRequest, [$quoteItemMock]);
    }

    private function setData()
    {
        $this->rateQuoteRequest = $this->getMockBuilder(RateQuoteRequestDataInterface::class)
            ->onlyMethods(['setProducts', 'setProductAssociations'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }
}
