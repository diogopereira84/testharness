<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Test\Model\Xml\PunchoutBuilder;

use Fedex\MarketplacePunchout\Model\Xml\PunchoutBuilder\Request;
use Fedex\MarketplacePunchout\Model\Xml\PunchoutBuilder\Request\Create;
use Fedex\MarketplacePunchout\Model\Xml\PunchoutBuilder\Request\Edit;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Simplexml\Element;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    /** @var
     * RequestInterface|MockObject
     */
    protected MockObject|RequestInterface $httpRequest;

    /** @var
     * Create|MockObject
     */
    private MockObject|Create $create;

    /**
     * @var
     * Edit|MockObject
     */
    protected MockObject|Edit $edit;

    /** @var
     * Request
     */
    private Request $request;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->httpRequest = $this->createMock(RequestInterface::class);
        $this->create = $this->getMockBuilder(Create::class)
            ->onlyMethods(['build'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->edit = $this->getMockBuilder(Edit::class)
            ->onlyMethods(['build'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = new Request(
            $this->httpRequest,
            $this->create,
            $this->edit
        );
    }

    /**
     * @return void
     */
    public function testBuildCreate()
    {
        $stringCreate = <<<XML
<Header></Header>
XML;
        $xmlCreate = simplexml_load_string($stringCreate, Element::class);

        $this->httpRequest->method('getParam')
            ->willReturn('');
        $this->create->expects($this->once())
            ->method('build')
            ->willReturn($xmlCreate);
        $this->edit->expects($this->never())
            ->method('build');
        $this->assertInstanceOf(
            'SimpleXMLElement',
            $this->request->build()
        );
    }

    /**
     * @return void
     */
    public function testBuildEdit()
    {
        $stringEdit = <<<XML
<Header></Header>
XML;

        $xmlEdit = simplexml_load_string($stringEdit, Element::class);

        $this->httpRequest->method('getParam')
            ->willReturn('test123');
        $this->edit->expects($this->once())
            ->method('build')
            ->willReturn($xmlEdit);
        $this->create->expects($this->never())
            ->method('build');
        $this->assertInstanceOf(
            'SimpleXMLElement',
            $this->request->build()
        );
    }
}
