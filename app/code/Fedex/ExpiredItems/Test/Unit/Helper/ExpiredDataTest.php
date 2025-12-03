<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpiredItems\Test\Unit\Helper;

use Fedex\MarketplacePunchout\Model\Config\Marketplace;
use Fedex\MarketplacePunchout\Model\ExpiredProducts;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\ExpiredItems\Helper\ExpiredData;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\Base\Helper\Auth;

/**
 * Test class ExpiredDataTest
 */
class ExpiredDataTest extends TestCase
{
    /**
     * @var CustomerSession $customerSession
     */
    protected $customerSession;

    /**
     * @var MockBuilder
     */
    protected $expiredProducts;

    /**
     * @var ExpiredData $expiredDataMock
     */
    private $expiredDataMock;

    /**
     * @var Marketplace $config
     */
    private Marketplace $config;
    protected Auth|MockObject $baseAuthMock;

    /**
     * Set up method
     */
    protected function setUp(): void
    {
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getExpiredItemIds',
                'isLoggedIn',
                'unsExpiredItemIds',
                'unsExpiredItemTransactionId',
                'getValidateContentApiExpired',
                'setExpiredItemIds'
                ])
            ->getMock();
        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();
        $this->expiredProducts = $this->getMockBuilder(ExpiredProducts::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMock();
        $this->config = $this->createMock(Marketplace::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->expiredDataMock = $objectManagerHelper->getObject(
            ExpiredData::class,
            [
                'customerSession' => $this->customerSession,
                'expiredProducts' => $this->expiredProducts,
                'config' => $this->config,
                'authHelper' => $this->baseAuthMock
            ]
        );
    }

    /**
     * Test unSetExpiredItemids
     *
     * @return void
     */
    public function testUnSetExpiredItemids()
    {
        $rateApiOutputdata = json_decode(
            '{"errors":[{"code":"PRODUCTS.CATALOGREFERENCE.INVALID",
            "message":"one or more products are invalid or expired with instance ids : 1234"}]}',
            true
        );
        $this->customerSession->expects($this->any())->method('getExpiredItemIds')->willReturn(['1235']);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->customerSession->expects($this->any())->method('setExpiredItemIds')->willReturn(['1234', '1235']);

        $this->assertNotEquals([0,1234], $this->expiredDataMock->unSetExpiredItemids($rateApiOutputdata));
    }

    /**
     * Test unSetExpiredItemids with no errors
     *
     * @return void
     */
    public function testUnSetExpiredItemidsWithNoErrors()
    {
        $rateApiOutputdata = [];
        $this->customerSession->expects($this->any())->method('getExpiredItemIds')->willReturn(['1234']);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->customerSession->expects($this->any())->method('getValidateContentApiExpired')->willReturn(0);
        $this->customerSession->expects($this->any())->method('unsExpiredItemIds')->willReturn(1);
        $this->customerSession->expects($this->any())->method('unsExpiredItemTransactionId')->willReturn(1);

        $this->assertNotEquals([0,1234], $this->expiredDataMock->unSetExpiredItemids($rateApiOutputdata));
    }

    /**
     * Test exludeExpiredProductFromRateRequest
     *
     * @return void
     */
    public function testExludeExpiredProductFromRateRequest()
    {
        $rateApiData = $this->getRateApiData();
        $this->customerSession->expects($this->any())->method('getExpiredItemIds')->willReturn([1236, 0]);

        $this->assertNotEquals([0,1234], $this->expiredDataMock->exludeExpiredProductFromRateRequest($rateApiData));
    }

    /**
     * Test exludeExpiredProductFromRateRequest with unset expired ids
     *
     * @return void
     */
    public function testExludeExpiredProductFromRateRequestWithUnsetExpiredIds()
    {
        $rateApiData = $this->getRateApiData();
        $this->customerSession->expects($this->any())->method('getExpiredItemIds')->willReturn([0, 1236]);

        $this->assertNotEquals([0,1234], $this->expiredDataMock->exludeExpiredProductFromRateRequest($rateApiData));
    }

    /**
     * Test exludeExpiredProductFromRateRequest
     *
     * @return void
     */
    public function testExludeExpiredProductFromRateQuoteRequest()
    {
        $rateApiData = [
            [
                'instanceId' => 0
            ],
            [
                'instanceId' => 1235
            ],
            [
                'instanceId' => 1236
            ]
        ];
        $this->customerSession->expects($this->any())->method('getExpiredItemIds')->willReturn([1236, 0]);

        $this->assertNotEquals(1, $this->expiredDataMock->exludeExpiredProductFromRateQuoteRequest($rateApiData));
    }

     /**
     * get rate api data
     *
     * @return array
     */
    public function getRateApiData() {
        return ['rateRequest' => [
                'products' => [
                    [
                        'instanceId' => 0
                    ],
                    [
                        'instanceId' => 1235
                    ],
                    [
                        'instanceId' => 1236
                    ]
                ]
            ]
        ];
    }

    public function testRebuildOrUnsetExpiredIntanceIdWith3pProduct() {
        $this->expiredProducts->expects($this->once())
            ->method('execute')->willReturn([]);
        $this->customerSession->expects($this->once())->method('unsExpiredItemIds');
        $this->expiredDataMock->rebuildOrUnsetExpiredIntanceId([], []);
    }
}
