<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplacePunchout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Test\Model;

use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Fedex\MarketplacePunchout\Model\Authorization;
use Fedex\MarketplacePunchout\Model\Xml\Builder;
use Fedex\MarketplacePunchout\Model\Xml\Builder\Header;
use Fedex\MarketplacePunchout\Model\Xml\PunchoutBuilder\Request;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Simplexml\Element;
use Magento\Framework\Simplexml\ElementFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Exception;
use PHPUnit\Framework\TestCase;

class AuthorizationTest extends TestCase
{
    protected $xmlFactory;
    protected $header;
    protected $request;
    protected $dateTime;
    /**
     * @var MarketplaceConfig
     */
    private MarketplaceConfig $config;

    /**
     * @var CurlFactory
     */
    private CurlFactory $curlFactory;

    /**
     * @var Builder
     */
    private Builder $builder;

    /**
     * @var Element
     */
    private Element $xml;

    /**
     * @var Authorization
     */
    private Authorization $authorization;


    /**
     * @var \Psr\Log\LoggerInterface
     */
    private \Psr\Log\LoggerInterface $logger;

    const PRODUCT_SKU = 'ABC123';

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        $string = <<<XML
<xml>
                <Response>
                    <PunchOutSetupResponse>
                        <StartPage>
                           <URL>http://www.navitorurl.test</URL>
                        </StartPage>
                    </PunchOutSetupResponse>
                </Response>
            </xml>
XML;

        $this->config = $this->createMock(MarketplaceConfig::class);
        $this->curlFactory = $this->createMock(CurlFactory::class);
        $this->logger = $this->createMock(\Psr\Log\LoggerInterface::class);


        $this->xml = new Element($string);

        $this->config->method('getNavitorUrl')
            ->willReturn('http://www.testurl.test');



        $this->xmlFactory = $this->createMock(ElementFactory::class);
        $this->header = $this->createMock(Header::class);
        $this->request = $this->createMock(Request::class);
        $this->dateTime = $this->createMock(DateTime::class);

        $stringBuilder = <<<XML
<Header/>
XML;
        $buildCreateXml =  new Element($stringBuilder);
        $buildHeaderXml = new Element($stringBuilder);
        $buildRequestXml = new Element($stringBuilder);

        $this->xmlFactory->method('create')
            ->willReturn($buildCreateXml);
        $this->header->method('build')
            ->willReturn($buildHeaderXml);
        $this->request->method('build')
            ->willReturn($buildRequestXml);


        $this->dateTime->method('gmtDate')
            ->willReturn('03/02/2022 03:02:00 AM');

        $this->builder = new Builder(
            $this->xmlFactory,
            $this->dateTime,
            $this->header,
            $this->request
        );

        $this->authorization = new Authorization(
            $this->config,
            $this->curlFactory,
            $this->builder,
            $this->logger
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testExecute(): void
    {
        $shopCustomAttributes = [
            'authorization-url' => 'https://example.com/authorization'
        ];

        $this->config->expects($this->once())
            ->method('getShopCustomAttributesByProductSku')
            ->with(static::PRODUCT_SKU)
            ->willReturn($shopCustomAttributes);

        $this->config->expects($this->once())
            ->method('isEnableShopsConnection')
            ->willReturn(true);

        $curl = $this->createMock(Curl::class);
        $curl->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(200);
        $curl->expects($this->once())
            ->method('getBody')
            ->willReturn(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE cXML SYSTEM "http://xml.cXML.org/schemas/cXML/1.2.026/cXML.dtd">
<cXML payloadID="642dd9a6caaeb" xml:lang="en-US" timestamp="04/05/2023 03:27 PM">
	<Response>
		<Status code="200" text="OK" />
		<AuthResponse>
			<Credential>
				<SharedSecret>SomeToken</SharedSecret>
			</Credential>
		</AuthResponse>
	</Response>
</cXML>
XML);
        $curl->expects($this->once())
            ->method('post');
        $curl->expects($this->exactly(2))
            ->method('getStatus');

        $this->curlFactory->expects($this->once())->method('create')->willReturn($curl);

        $this->logger->expects($this->any())
            ->method('info');

        $this->assertEquals('SomeToken', $this->authorization->execute(static::PRODUCT_SKU));;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testExecuteNoToken(): void
    {
        $curl = $this->createMock(Curl::class);
        $curl->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(200);
        $curl->expects($this->once())
            ->method('getBody')
            ->willReturn(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE cXML SYSTEM "http://xml.cXML.org/schemas/cXML/1.2.026/cXML.dtd">
<cXML payloadID="642dd9a6caaeb" xml:lang="en-US" timestamp="04/05/2023 03:27 PM">
	<Response>
		<Status code="401" text="Error" />
	</Response>
</cXML>
XML);
        $curl->expects($this->once())
            ->method('post');
        $curl->expects($this->exactly(2))
            ->method('getStatus');

        $this->curlFactory->expects($this->once())->method('create')->willReturn($curl);

        $this->logger->expects($this->any())
            ->method('info');

        $this->assertNull($this->authorization->execute(static::PRODUCT_SKU));
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testExecuteThrowException(): void
    {
        $curl = $this->createMock(Curl::class);
        $curl->expects($this->once())
            ->method('post');
        $curl->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(201);
        $curl->expects($this->once())
            ->method('getBody')->willReturn('some error');
        $this->curlFactory->expects($this->once())->method('create')->willReturn($curl);

        $this->expectException(Exception::class);
        $this->authorization->execute(static::PRODUCT_SKU);
    }
}
