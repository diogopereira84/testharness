<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Test\Model\Xml;

use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Fedex\MarketplacePunchout\Model\Xml\Builder\Header;
use Fedex\MarketplacePunchout\Model\Xml\PunchoutBuilder\Request;
use Fedex\MarketplacePunchout\Model\Xml\PunchoutBuilder\Request\Create;
use Fedex\MarketplacePunchout\Model\Xml\PunchoutBuilder\Request\Edit;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Simplexml\Element;
use Magento\Framework\Simplexml\ElementFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Fedex\MarketplacePunchout\Model\Xml\Builder;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Exception\NoSuchEntityException;

class BuilderTest extends TestCase
{

    protected $xml;
    /** @var ElementFactory|Mock */
    protected Mock|ElementFactory $xmlFactory;

    /**
     * @var Header|Mock
     */
    protected Mock|Header $header;

    /**
     * @var Request|Mock
     */
    protected Mock|Request $request;

    /**
     * @var DateTime|Mock
     */
    protected Mock|DateTime $dateTime;

    /**
     * @var Builder
     */
    protected Builder $builder;

    /**
     * @var Create|(Create&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $create;

    /**
     * @var Edit|(Edit&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $edit;

    /**
     * @var RequestInterface|(RequestInterface&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $httpRequest;

    /**
     * @var MarketplacePunchoutConfig|(MarketplaceConfig&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $config;

    /**
     * @var ElementFactory|(ElementFactory&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $headerXmlFactory;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $string = <<<XML
<Header/>
XML;
        # Builder
        $this->xmlFactory = $this->getMockBuilder(ElementFactory::class)
            ->setMethods(['addAttribute', 'create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->xml = simplexml_load_string($string, Element::class);
        $this->request = $this->createMock(Request::class);
        $this->dateTime = $this->createMock(DateTime::class);

        $this->xmlFactory->method('create')
            ->willReturn($this->xml);

        $this->dateTime->method('gmtDate')
            ->willReturn('03/02/2022 03:02:00 AM');


        # Header
        $headerxml = simplexml_load_string($string, Element::class);
        $this->headerXmlFactory = $this->createMock(ElementFactory::class);
        $this->config = $this->createMock(MarketplaceConfig::class);
        $this->config->method('getFromId')->willReturn('from_id');
        $this->config->method('getToId')->willReturn('to_id');
        $this->config->method('getSenderIdentity')->willReturn('sender_identity');
        $this->config->method('getSenderSharedSecret')->willReturn('shared_secret');
        $this->headerXmlFactory->method('create')
            ->willReturn($headerxml);

        # Request
        $stringCreate = <<<XML
<Header></Header>
XML;
        $stringEdit = <<<XML
<Header></Header>
XML;
        $this->httpRequest = $this->createMock(RequestInterface::class);
        $this->create = $this->createMock(Create::class);
        $this->edit = $this->createMock(Edit::class);
        $xmlCreate = simplexml_load_string($stringCreate, Element::class);
        $xmlEdit = simplexml_load_string($stringEdit, Element::class);
        $this->create->method('build')
            ->willReturn($xmlCreate);
        $this->edit->method('build')
            ->willReturn($xmlEdit);


        $this->header = new Header(
            $this->headerXmlFactory,
            $this->config
        );

        $this->request = new Request(
            $this->httpRequest,
            $this->create,
            $this->edit
        );

        $this->builder = new Builder(
            $this->xmlFactory,
            $this->dateTime,
            $this->header,
            $this->request
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
        $this->assertInstanceOf('SimpleXMLElement', $this->builder->build());
    }

    /**
     * @throws NoSuchEntityException
     */
    public function testBuildReturnsElement(): void
    {
        $string = <<<XML
<Header/>
XML;
        $productSku = 'ABC123';

        $this->xml = simplexml_load_string($string, Element::class);
        $this->xmlFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->xml);

        $this->dateTime->expects($this->once())
            ->method('gmtDate')
            ->willReturn('2023-01-01 12:00:00');



        $result = $this->builder->build($productSku);

        $this->assertInstanceOf('SimpleXMLElement', $result);
    }
}
