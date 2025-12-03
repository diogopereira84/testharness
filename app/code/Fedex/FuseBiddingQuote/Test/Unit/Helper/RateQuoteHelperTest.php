<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Test\Unit\Helper;

use Fedex\FuseBiddingQuote\Helper\RateQuoteHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\Header\Helper\Data as HeaderDataHelper;
use Magento\Framework\HTTP\Client\Curl;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Test class for RateQuoteHelper
 */
class RateQuoteHelperTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $objRateQuoteHelper;
    /**
     * @var PunchoutHelper $punchoutHelper
     */
    protected $punchoutHelper;

    /**
     * @var HeaderDataHelper $headerDataHelper
     */
    protected $headerDataHelper;

    /**
     * @var Curl $curl
     */
    protected $curl;

    /**
     * @var CartDataHelper $cartDataHelper
     */
    protected $cartDataHelper;

    /**
     * Setup function
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->punchoutHelper = $this->getMockBuilder(PunchoutHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAuthGatewayToken', 'getTazToken'])
            ->getMock();

        $this->headerDataHelper = $this->getMockBuilder(HeaderDataHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAuthHeaderValue'])
            ->getMock();

        $this->cartDataHelper = $this->getMockBuilder(CartDataHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRateQuoteApiUrl'])
            ->getMock();
        
        $this->curl = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->setMethods(['setOptions', 'get', 'getBody'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->objRateQuoteHelper = $this->objectManagerHelper->getObject(
            RateQuoteHelper::class,
            [
                'punchoutHelper' => $this->punchoutHelper,
                'headerDataHelper' => $this->headerDataHelper,
                'cartDataHelper' => $this->cartDataHelper,
                'curl' => $this->curl
            ]
        );
    }

    /**
     * Test getRateQuoteDetails
     *
     * @return void
     */
    public function testGetRateQuoteDetails()
    {
        $apiResponse = [
            'errors' => [
                0 => 'err1',
                1 => 'err2'
            ]
        ];
        $this->punchoutHelper->expects($this->once())->method('getAuthGatewayToken')->willReturn('243423SDSD');
        $this->punchoutHelper->expects($this->once())->method('getTazToken')->willReturn('dsfd45446456');
        $this->headerDataHelper->expects($this->once())
        ->method('getAuthHeaderValue')->willReturn('client_id');
        $this->cartDataHelper->expects($this->once())
        ->method('getRateQuoteApiUrl')->willReturn('https://api.test.fedecom');
        $this->curl->expects($this->once())->method('setOptions')->willReturnSelf();
        $this->curl->expects($this->once())->method('get')->willReturnSelf();
        $this->curl->expects($this->once())->method('getBody')->willReturn(json_encode($apiResponse));

        $this->assertIsArray($this->objRateQuoteHelper->getRateQuoteDetails('24464hdsfsfs'));
    }

    /**
     * Test getRateQuoteDetails with Exception
     *
     * @return void
     */
    public function testGetRateQuoteDetailsWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->punchoutHelper->expects($this->once())->method('getAuthGatewayToken')->willThrowException($exception);

        $this->assertIsArray($this->objRateQuoteHelper->getRateQuoteDetails('24464hdsfsfs'));
    }
}
