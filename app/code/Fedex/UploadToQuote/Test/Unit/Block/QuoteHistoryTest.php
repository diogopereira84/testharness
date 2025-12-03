<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Block;

use Fedex\UploadToQuote\Block\QuoteHistory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Fedex\UploadToQuote\Model\QuoteHistory\GetAllQuotes;
use Magento\Theme\Block\Html\Pager;
use Magento\Framework\DataObject;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Element\BlockInterface;

/**
 * Test class for QuoteHistory Block
 */
class QuoteHistoryTest extends TestCase
{
    protected $layout;
    protected $historyBlockMock;
    protected $quoteHistoryMock;
    /**
     * @var RequestInterface $requestMock
     */
    protected $requestMock;

    /**
     * @var GetAllQuotes $getAllQuotesMock
     */
    protected $getAllQuotesMock;

    /**
     * Set up method.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();

        $this->getAllQuotesMock = $this->getMockBuilder(GetAllQuotes::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllNegotiableQuote'])
            ->getMock();

        $this->layout = $this->getMockBuilder(LayoutInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getLayout',
                'createBlock',
                'setCollection',
                'setChild',
                'getUrl'])
            ->getMockForAbstractClass();

        $this->historyBlockMock = $this->getMockBuilder(BlockInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setCollection',
                    'setChild'
                ]
            )
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->quoteHistoryMock = $objectManagerHelper->getObject(
            QuoteHistory::class,
            [
                'request' => $this->requestMock,
                'getAllQuotes' => $this->getAllQuotesMock,
                '_layout' => $this->layout
            ]
        );
    }

    /**
     * Assert _prepareLayout.
     *
     * @return string
     */
    public function testPrepareLayout()
    {
        $data['items'] = [
            0 => [
                ['key' => 'value'],
                ['quote_name' => 'name_1']
            ],
            1 => [
                ['key' => 'value'],
                ['quote_name' => 'name_1']
            ],
            2 => [
                ['key' => 'value'],
                ['quote_name' => 'name_1']
            ],
        ];
        
        $this->layout->expects($this->any())->method('getLayout')->willReturnSelf();
        $this->layout->expects($this->any())
            ->method('createBlock')
            ->willReturn($this->historyBlockMock);
        $this->getAllQuotesMock->method('getAllNegotiableQuote')->willReturn($data);

        $this->layout->expects($this->any())->method('setChild')->willReturnSelf();

        $testMethod = new \ReflectionMethod(
            QuoteHistory::class,
            '_prepareLayout'
        );
        $expectedResult = $testMethod->invoke($this->quoteHistoryMock);
        $this->assertNotEquals('', $expectedResult);
    }

    /**
     * Test method for getCurrentUrl
     *
     * @return void
     */
    public function testGetCurrentUrl()
    {
        $this->layout->expects($this->any())
        ->method('getUrl')
        ->willReturn('www.test.com');

        $this->assertNotEquals('ww.test1.com', $this->quoteHistoryMock->getCurrentUrl());
    }

    /**
     * Test isNegotiableQuotesCreated
     *
     * @return void
     */
    public function testIsNegotiableQuotesCreated()
    {
        $arrData = [0 => 'item'];
        $this->getAllQuotesMock->expects($this->once())->method('getAllNegotiableQuote')->willReturn($arrData);
        
        $this->assertTrue($this->quoteHistoryMock->isNegotiableQuotesCreated());
    }
}
