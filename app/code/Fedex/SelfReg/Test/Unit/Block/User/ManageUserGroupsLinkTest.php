<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Test\Unit\Block\User;

use Fedex\Delivery\Helper\Data;
use Fedex\SelfReg\Block\User\ManageUserGroupsLink;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Block\Account\SortLinkInterface;

class ManageUserGroupsLinkTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    /**
     * @var (\Magento\Framework\UrlInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $urlInterfaceMock;
    /**
     * @var (\Magento\Framework\Escaper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $escaperMock;
    /**
     * @var (\Fedex\Delivery\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $deliveryDataHelper;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $customBlock;
    /**
     * @var \Fedex\Delivery\Helper\Data $helperDataMock
     */
    protected $helperDataMock;

    const SORT_ORDER = 95;

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

        $this->deliveryDataHelper = $this->getMockBuilder(Data::class)
        ->setMethods(['getCustomer','getCustomAttribute','getValue','getToggleConfigurationValue','checkPermission','isCustomerAdminUser'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->customBlock = $this->objectManager->getObject(
            ManageUserGroupsLink::class,
            [
                'context' => $this->context,
                '_urlBuilder' => $this->urlInterfaceMock,
                'urlBuilder' => $this->urlInterfaceMock,
                '_escaper' => $this->escaperMock,
                'helperData' =>$this->deliveryDataHelper,
            ]
        );
    }

    /**
     * Test Case getSortOrder()
     */
    public function testGetSortOrder()
    {
        $this->customBlock->getSortOrder();
    }
}