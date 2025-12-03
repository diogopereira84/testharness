<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\UploadToQuote\Test\Unit\ViewModel;

use Fedex\UploadToQuote\ViewModel\QuoteHistory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\Quote;
use Fedex\Delivery\Helper\Data as DeliveryDataHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;

class QuoteHistoryTest extends TestCase
{
    protected $quote;
    protected $quoteHistory;
    /**
     * @var PriceHelper $priceHelper
     */
    protected $priceHelper;

    /**
     * @var AdminConfigHelper $adminConfigHelper
     */
    protected AdminConfigHelper $adminConfigHelper;

    /**
     * @var QuoteFactory $quoteFactory
     */
    protected $quoteFactory;

    /**
     * @var DeliveryDataHelper $deliveryDataHelper
     */
    protected $deliveryDataHelper;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var CustomerSession $customerSession
     */
    protected $customerSession;

    /**
     * Setup method
     */
    public function setUp(): void
    {
        $this->priceHelper = $this->getMockBuilder(PriceHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['currency'])
            ->getMock();

        $this->adminConfigHelper = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getExpiryDate',
                'getFormattedDate',
                'getFormattedPrice',
                'getStatusLabel',
                'getNegotiableQuoteStatus',
                'checkoutQuotePriceisDashable',
                'getQuoteStatusLabel',
                'isUploadToQuoteEnable',
                'isToggleD206707Enabled'
            ])
            ->getMock();

        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['load'])
            ->getMock();

        $this->deliveryDataHelper = $this->getMockBuilder(DeliveryDataHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isEproCustomer'])
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore', 'getId', 'getBaseUrl'])
            ->getMockForAbstractClass();

        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOndemandCompanyInfo'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->quoteHistory = $objectManagerHelper->getObject(
            QuoteHistory::class,
            [
                'priceHelper' => $this->priceHelper,
                'adminConfigHelper' => $this->adminConfigHelper,
                'quoteFactory' => $this->quoteFactory,
                'deliveryDataHelper' => $this->deliveryDataHelper,
                'storeManager' => $this->storeManager,
                'customerSession' => $this->customerSession
            ]
        );
    }

    /**
     * Test getExpiryDate
     *
     * @return void
     */
    public function testGetExpiryDate()
    {
        $date = '2023-10-30';
        $returnValue = '10/30/2023';

        $this->adminConfigHelper->expects($this->once())
        ->method('getExpiryDate')
        ->with($date, 'm/d/Y')
        ->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteHistory->getExpiryDate($date, 'm/d/Y'));
    }

    /**
     * Test getFormattedDate
     *
     * @return void
     */
    public function testGetFormattedDate()
    {
        $date = '2023-10-29';
        $returnValue = '10/29/2023';

        $this->adminConfigHelper->expects($this->once())
        ->method('getFormattedDate')
        ->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteHistory->getFormattedDate($date));
    }

    /**
     * Test getFormattedPrice
     *
     * @return void
     */
    public function testGetFormattedPrice()
    {
        $price = '0.6100';
        $returnValue = '$0.61';

        $this->priceHelper->expects($this->once())
        ->method('currency')
        ->with($price, true, false)
        ->willReturn($returnValue);

        $this->quoteFactory->expects($this->once())
        ->method('create')
        ->willReturn($this->quote);

        $this->quote->expects($this->once())
        ->method('load')
        ->willReturnSelf();

        $this->adminConfigHelper->expects($this->once())
        ->method('checkoutQuotePriceisDashable')
        ->willReturn(false);

        $this->adminConfigHelper->expects($this->once())
        ->method('isToggleD206707Enabled')
        ->willReturn(false);

        $this->assertEquals($returnValue, $this->quoteHistory->getFormattedPrice($price, 1234));
    }

    /**
     * Test getStatusLabel
     *
     * @return void
     */
    public function testGetStatusLabel()
    {
        $status = 'created';
        $returnValue = 'Dummy Status';

        $this->adminConfigHelper->expects($this->once())
        ->method('getNegotiableQuoteStatus')
        ->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->quoteHistory->getStatusLabel($status));
    }

    /**
     * Test getDataByStatusLebel
     *
     * @return void
     */
    public function testGetDataByStatusLebel()
    {
        $status = 'Expired';
        $returnValue = ['dotIconClass' => '', 'linkText' => 'View'];

        $this->assertEquals($returnValue, $this->quoteHistory->getDataByStatusLebel($status));
    }

    /**
     * Test getDataByStatusLebel with set to expire
     *
     * @return void
     */
    public function testGetDataByStatusLebelWithSetToExpire()
    {
        $status = 'Set To Expire';
        $returnValue = ['dotIconClass' => 'set-to-expire', 'linkText' => 'Review'];

        $this->assertEquals($returnValue, $this->quoteHistory->getDataByStatusLebel($status));
    }

    /**
     * Test getDataByStatusLebel with ready for review
     *
     * @return void
     */
    public function testGetDataByStatusLebelWithReadyForReview()
    {
        $status = 'Ready for Review';
        $returnValue = ['dotIconClass' => 'ready-for-review', 'linkText' => 'Review'];

        $this->assertEquals($returnValue, $this->quoteHistory->getDataByStatusLebel($status));
    }

    /**
     * Test getQuoteStatusLabel
     *
     * @return void
     */
    public function testGetQuoteStatusLabel()
    {
        $status = 'Ready for Review';
        $this->adminConfigHelper->expects($this->once())
        ->method('getQuoteStatusLabel')
        ->willReturn($status);

        $this->assertEquals($status, $this->quoteHistory->getQuoteStatusLabel('processing_by_admin', '2024-03-06'));
    }

    /**
     * Test isEproCustomer
     *
     * @return void
     */
    public function testIsEproCustomer()
    {
        $this->deliveryDataHelper->expects($this->once())->method('isEproCustomer')->willReturn(true);

        $this->assertTrue($this->quoteHistory->isEproCustomer());
    }

    /**
     * Test method for myQuotesAccountNavigationUrl
     *
     * @return void
     */
    public function testMyQuotesAccountNavigationUrl()
    {
        $this->customerSession->expects($this->once())->method('getOndemandCompanyInfo')->willReturn(71);
        $this->storeManager->expects($this->once())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->once())->method('getId')->willReturn(2);
        $this->adminConfigHelper->expects($this->once())
        ->method('isUploadToQuoteEnable')->willReturn(true);

        $this->assertEquals(
            "uploadtoquote/index/quotehistory",
            $this->quoteHistory->myQuotesAccountNavigationUrl()
        );
    }

    /**
     * Test method for myQuotesAccountNavigationUrl negative case
     *
     * @return void
     */
    public function testMyQuotesAccountNavigationUrlFalse()
    {
        $this->customerSession->expects($this->once())->method('getOndemandCompanyInfo')->willReturn(71);
        $this->storeManager->expects($this->once())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->once())->method('getId')->willReturn(2);
        $this->adminConfigHelper->expects($this->once())
        ->method('isUploadToQuoteEnable')->willReturn(false);

        $this->assertEquals(
            "negotiable_quote/quote",
            $this->quoteHistory->myQuotesAccountNavigationUrl()
        );
    }
}
