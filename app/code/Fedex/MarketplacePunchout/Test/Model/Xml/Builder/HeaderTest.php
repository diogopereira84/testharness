<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Test\Model\Xml\Builder;

use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Fedex\MarketplacePunchout\Test\Model\Xml\Builder\Request\Mock;
use Magento\Customer\Model\Customer;
use Magento\Framework\Simplexml\Element;
use Magento\Framework\Simplexml\ElementFactory;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplacePunchout\Model\Xml\Builder\Header;

class HeaderTest extends TestCase
{
    /**
     * @var ElementFactory|Mock
     */
    protected Mock|ElementFactory $xmlFactory;

    /**
     * @var Element|Mock
     */
    private Mock|Element $xml;

    /**
     * @var MarketplaceConfig|Mock
     */
    protected Mock|MarketplaceConfig $config;

    /**
     * @var Customer|Mock
     */
    protected Mock|Customer $customer;

    /**
     * @var Header
     */
    private Header $header;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $string = <<<XML
<Header/>
XML;
        $this->xmlFactory = $this->createMock(ElementFactory::class);
        $this->xml = simplexml_load_string($string, Element::class);
        $this->config = $this->createMock(MarketplaceConfig::class);


        $this->header = new Header(
            $this->xmlFactory,
            $this->config,
        );
    }

    /**
     * @return void
     */
    public function testBuild()
    {
        $this->config->expects($this->once())
            ->method('getFromId');
        $this->config->expects($this->once())
            ->method('getToId');
        $this->config->expects($this->once())
            ->method('getSenderIdentity');
        $this->config->expects($this->once())
            ->method('getSenderSharedSecret');
        $this->xmlFactory->expects($this->once())->method('create')
            ->willReturn($this->xml);
        $this->assertInstanceOf(Element::class, $this->header->build());
    }
}
