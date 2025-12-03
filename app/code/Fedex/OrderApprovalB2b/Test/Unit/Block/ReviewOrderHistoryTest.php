<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Test\Unit\Block;

use Fedex\OrderApprovalB2b\Block\ReviewOrderHistory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Block\Account\SortLinkInterface;
use PHPUnit\Framework\TestCase;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper;
use Fedex\OrderApprovalB2b\Helper\RevieworderHelper;

class ReviewOrderHistoryTest extends TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    protected $urlInterfaceMock;
    /**
     * @var (\Magento\Framework\Escaper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $escaperMock;
    protected $adminConfigHelper;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $customBlock;
    /**
     * @var $sort_order
     */
    public const SORT_ORDER = 93;

    /**
     * @var RevieworderHelper|MockObject
     */
    protected $revieworderHelperMock;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlInterfaceMock = $this
            ->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->setMethods(['getUrl','getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->escaperMock = $this
            ->getMockBuilder(\Magento\Framework\Escaper::class)
            ->setMethods(['escapeHtml'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->adminConfigHelper = $this->getMockBuilder(AdminConfigHelper::class)
            ->setMethods(['isOrderApprovalB2bEnabled'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->revieworderHelperMock = $this->getMockBuilder(RevieworderHelper::class)
            ->setMethods(['checkIfUserHasReviewOrderPermission'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->customBlock = $this->objectManager->getObject(
            ReviewOrderHistory::class,
            [
                'context' => $this->context,
                '_urlBuilder' => $this->urlInterfaceMock,
                'urlBuilder' => $this->urlInterfaceMock,
                '_escaper' => $this->escaperMock,
                'adminConfigHelper' => $this->adminConfigHelper,
                'revieworderHelper'=> $this->revieworderHelperMock
            ]
        );
    }

    /**
     * Assert _toHtml.
     *
     * @return string
     */
    public function testToHtml()
    {
        $testMethod = new \ReflectionMethod(
            ReviewOrderHistory::class,
            '_toHtml',
        );
        $this->adminConfigHelper->expects($this->any())->method('isOrderApprovalB2bEnabled')->willReturn(true);
        $this->revieworderHelperMock->expects($this->any())
        ->method('checkIfUserHasReviewOrderPermission')->willReturn(true);
        $this->urlInterfaceMock->method('getCurrentUrl')
         ->willReturn('https://staging3.office.fedex.com/ondemand/mgs/orderb2b/revieworder/history');
        $testMethod->setAccessible(true);
        $expectedResult = $testMethod->invoke($this->customBlock);

         $this->assertIsString($expectedResult);
    }

    /**
     * Assert _toHtml in Negative case
     *
     * @return ''
     */
    public function testToHtmlWhenModuleDisable()
    {
        $testMethod = new \ReflectionMethod(
            ReviewOrderHistory::class,
            '_toHtml',
        );
        $this->adminConfigHelper->expects($this->any())->method('isOrderApprovalB2bEnabled')->willReturn(true);
        $this->revieworderHelperMock->expects($this->any())
        ->method('checkIfUserHasReviewOrderPermission')->willReturn(true);
        $testMethod->setAccessible(true);
        $expectedResult = $testMethod->invoke($this->customBlock);

        $this->assertIsString($expectedResult);
    }

    /**
     * Test Case getSortOrder()
     */
    public function testGetSortOrder()
    {
        $this->customBlock->getSortOrder();
    }
}
