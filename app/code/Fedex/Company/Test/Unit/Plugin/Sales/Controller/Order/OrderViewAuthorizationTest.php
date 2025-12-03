<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Company\Test\Unit\Plugin\Sales\Controller\Order;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Sales\Model\Order;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\Data\CustomerInterface;
use Fedex\Delivery\Helper\Data as DeliveryDataHelper;
use Fedex\Company\Plugin\Sales\Controller\Order\OrderViewAuthorization;
use Magento\Company\Controller\Order\OrderViewAuthorization as ParentOrderViewAuthorization;
use Fedex\OrderApprovalB2b\ViewModel\ReviewOrderViewModel;

/**
 * Test for OrderViewAuthorization
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderViewAuthorizationTest extends TestCase
{
    protected $customerInterfaceMock;
    protected $orderMock;
    protected $parentOrderViewAuthorizationMock;
    /**
     * @var (\Fedex\OrderApprovalB2b\ViewModel\ReviewOrderViewModel & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $reviewOrderViewModel;
    protected $orderViewAuthorization;
    /**
     * @var Session|MockObject
     */
    protected $customerSession;

    /**
     * @var DeliveryDataHelper|MockObject
     */
    protected $deliveryDataHelper;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->deliveryDataHelper = $this->getMockBuilder(DeliveryDataHelper::class)
            ->setMethods(['isCompanyAdminUser','getToggleConfigurationValue','checkPermission'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomer'])
            ->getMock();

        $this->customerInterfaceMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGroupId'])
            ->getMockForAbstractClass();

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerGroupId'])
            ->getMock();

        $this->parentOrderViewAuthorizationMock = $this->getMockBuilder(ParentOrderViewAuthorization::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->reviewOrderViewModel = $this->getMockBuilder(ReviewOrderViewModel::class)
            ->setMethods(['isOrderApprovalB2bEnabled', 'checkIfUserHasReviewOrderPermission'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderViewAuthorization = new OrderViewAuthorization(
            $this->deliveryDataHelper,
            $this->customerSession,
            $this->reviewOrderViewModel
        );
    }

    /**
     * When order customer group id is equal to customer group id, canView returns true
     */
    public function testAfterCanView()
    {
        $customerGroupId = 90;
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturn($this->customerInterfaceMock);
        $this->customerInterfaceMock->expects($this->any())->method('getGroupId')->willReturn($customerGroupId);
        $this->deliveryDataHelper->expects($this->any())->method('isCompanyAdminUser')->willReturn(true);
        $this->orderMock->expects($this->any())->method('getCustomerGroupId')->willReturn($customerGroupId);

        $this->assertTrue($this->orderViewAuthorization->afterCanView(
            $this->parentOrderViewAuthorizationMock,
            '',
            $this->orderMock
        ));
    }

    /**
     * When order customer group id is equal to customer group id, canView returns true
     */
    public function testAfterCanViewWithResults()
    {
        $customerGroupId = 90;
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturn($this->customerInterfaceMock);
        $this->customerInterfaceMock->expects($this->any())->method('getGroupId')->willReturn($customerGroupId);
        $this->deliveryDataHelper->expects($this->any())->method('isCompanyAdminUser')->willReturn(false);
        $this->orderMock->expects($this->any())->method('getCustomerGroupId')->willReturn($customerGroupId);

        $this->assertEquals(
            '',
            $this->orderViewAuthorization->afterCanView($this->parentOrderViewAuthorizationMock, '', $this->orderMock)
        );
    }
}
