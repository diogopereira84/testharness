<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\OrderApprovalB2b\Test\Unit\ViewModel;

use Fedex\OrderApprovalB2b\ViewModel\ReviewOrderViewModel;
use Fedex\OrderApprovalB2b\Helper\RevieworderHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper as OrderApprovalAdminConfigHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;

/**
 * Test class for ReviewOrderViewModel
 */
class ReviewOrderViewModelTest extends TestCase
{
    /**
     * @var (\Magento\Quote\Model\Quote & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteMock;
    /**
     * @var RevieworderHelper $revieworderHelper
     */
    protected $revieworderHelper;

    /**
     * @var  ReviewOrderViewModel $reviewOrderViewModel
     */
    protected $reviewOrderViewModel;

    /**
     * @var OrderApprovalAdminConfigHelper $orderApprovalAdminConfigHelper
     */
    protected $orderApprovalAdminConfigHelper;
    
    /**
     * @var CustomerSession $customerSession
     */
    protected $customerSession;

    /**
     * @var CartRepositoryInterface $quoteRepository
     */
    protected $quoteRepository;
    
    /**
     * @var Session $checkoutSession
     */
    protected $checkoutSession;

    /**
     * Setup method
     */
    protected function setUp(): void
    {
        $this->revieworderHelper = $this->getMockBuilder(RevieworderHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getFormattedPrice',
                'getFormattedDate',
                'checkIfUserHasReviewOrderPermission'
            ])
            ->getMock();

        $this->orderApprovalAdminConfigHelper = $this->getMockBuilder(OrderApprovalAdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isOrderApprovalB2bEnabled', 'checkIsReviewActionSet'])
            ->getMock();
        
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->setMethods([
                'getSuccessErrorData',
                'unsSuccessErrorData'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();
            
        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->setMethods([
                'getPendingOrderQuoteId',
                'unsPendingOrderQuoteId'
            ])
            ->disableOriginalConstructor()
            ->getMock();
       
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->reviewOrderViewModel = $objectManagerHelper->getObject(
            ReviewOrderViewModel::class,
            [
                'revieworderHelper' => $this->revieworderHelper,
                'orderApprovalAdminConfigHelper' => $this->orderApprovalAdminConfigHelper,
                'customerSession' => $this->customerSession,
                'checkoutSession' => $this->checkoutSession,
                'quoteRepository' => $this->quoteRepository
            ]
        );
    }

    /**
     * Test getFormattedPrice
     *
     * @return void
     */
    public function testGetFormattedPrice()
    {
        $returnValue = '$5.00';
        $this->revieworderHelper->expects($this->once())->method('getFormattedPrice')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->reviewOrderViewModel->getFormattedPrice(5));
    }

    /**
     * Test getFormattedDate
     *
     * @return void
     */
    public function testGetFormattedDate()
    {
        $returnValue = '13/05/2024';
        $this->revieworderHelper->expects($this->once())->method('getFormattedDate')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->reviewOrderViewModel->getFormattedDate('2024-05-13', 'd/m/Y'));
    }

    /**
     * Test isOrderApprovalB2bEnabled
     *
     * @return void
     */
    public function testIsOrderApprovalB2bEnabled()
    {
        $returnValue = true;
        $this->orderApprovalAdminConfigHelper->expects($this->once())
            ->method('isOrderApprovalB2bEnabled')
            ->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->reviewOrderViewModel->isOrderApprovalB2bEnabled());
    }

    /**
     * Test checkIsReviewActionSet
     *
     * @return void
     */
    public function testCheckIsReviewActionSet()
    {
        $returnValue = true;
        $this->orderApprovalAdminConfigHelper
            ->expects($this->once())
            ->method('checkIsReviewActionSet')
            ->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->reviewOrderViewModel->checkIsReviewActionSet());
    }

    /**
     * Test method for getSuccessErrorData
     *
     * @return void
     */
    public function testGetSuccessErrorData()
    {
        $arrData = [
            'success' => true,
            'msg' => 'success msg'
        ];
        $this->customerSession->expects($this->once())
            ->method('getSuccessErrorData')
            ->willReturn($arrData);

        $this->assertEquals($arrData, $this->reviewOrderViewModel->getSuccessErrorData());
    }

    /**
     * Test method for unsetSuccessErrorData
     *
     * @return void
     */
    public function testUnsetSuccessErrorData()
    {
        $this->customerSession->expects($this->once())
            ->method('unsSuccessErrorData')
            ->willReturnSelf();

        $this->assertNull($this->reviewOrderViewModel->unsetSuccessErrorData());
    }

    /**
     * Test method for checkIfUserHasReviewOrderPermission
     *
     * @return void
     */
    public function testCheckIfUserHasReviewOrderPermission()
    {
        $this->revieworderHelper->expects($this->once())
            ->method('checkIfUserHasReviewOrderPermission')
            ->willReturn(true);

        $this->assertEquals(true, $this->reviewOrderViewModel->checkIfUserHasReviewOrderPermission());
    }

    /**
     * Test method for getPendingOrderQuoteId
     *
     * @return void
     */
    public function testGetPendingOrderQuoteId()
    {
        $this->checkoutSession->expects($this->once())
            ->method('getPendingOrderQuoteId')
            ->willReturn('1234');

        $this->assertEquals('1234', $this->reviewOrderViewModel->getPendingOrderQuoteId());
    }

    /**
     * Test method for unsetSuccessErrorData
     *
     * @return void
     */
    public function testUnsetPendingOrderQuoteId()
    {
        $this->checkoutSession->expects($this->any())
            ->method('unsPendingOrderQuoteId')
            ->willReturnSelf();

        $this->assertNull($this->reviewOrderViewModel->unsetPendingOrderQuoteId());
    }

    /**
     * Test method for getQuoteObj
     *
     * @return void
     */
    public function testGetQuoteObj()
    {
        $this->quoteRepository->expects($this->once())
            ->method('get')
            ->willReturnSelf();

        $this->assertIsObject($this->reviewOrderViewModel->getQuoteObj('1234'));
    }
}
