<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Test\Unit\Helper;

use Fedex\OrderApprovalB2b\Helper\RevieworderHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Controller\Result\JsonFactory;

class RevieworderHelperTest extends TestCase
{
    /**
     * @var PriceHelper $priceHelper
     */
    protected $priceHelper;

    /**
     * @var TimezoneInterface $timezoneInterface
     */
    protected $timezoneInterface;

    /**
     * @var RevieworderHelper $revieworderHelper
     */
    protected $revieworderHelper;

    /**
     * @var CustomerSession $customerSession
     */
    protected $customerSession;

    /**
     * @var Data $deliveryDataHelper|MockObject
     */
    protected $deliveryDataHelperMock;

    /**
     * @var JsonFactory $resultJsonMock
     */
    protected $resultJsonMock;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->priceHelper = $this->getMockBuilder(PriceHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['currency'])
            ->getMock();

        $this->timezoneInterface = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['date','format'])
            ->getMockForAbstractClass();

        $this->deliveryDataHelperMock = $this->getMockBuilder(DeliveryHelper::class)
            ->setMethods(['getToggleConfigurationValue','isSelfRegCustomerAdminUser','checkPermission'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->setMethods(['getOndemandCompanyInfo','setSuccessErrorData','getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->resultJsonMock = $this->getMockBuilder(JsonFactory::Class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->revieworderHelper = $objectManagerHelper->getObject(
            RevieworderHelper::class,
            [
              'priceHelper' => $this->priceHelper,
              'timezoneInterface' => $this->timezoneInterface,
              'deliveryDataHelper' => $this->deliveryDataHelperMock,
              'customerSession' => $this->customerSession
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
        $returnValue = '$5.0';
        $this->priceHelper->expects($this->once())->method('currency')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->revieworderHelper->getFormattedPrice(5));
    }

    /**
     * Test getFormattedDate
     *
     * @return void
     */
    public function testGetFormattedDate()
    {
        $returnValue = '13/05/2024';
        $this->timezoneInterface->expects($this->once())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->once())->method('format')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->revieworderHelper->getFormattedDate('2024-05-13', 'd/m/Y'));
    }

    /**
     * Test checkIfUserHasReviewOrderPermission
     *
     * @return void
     */
    public function testCheckIfUserHasReviewOrderPermission()
    {
        $returnValue = 1;
        $this->deliveryDataHelperMock->expects($this->any())->method('getToggleConfigurationValue')->willReturn(1);
        $this->deliveryDataHelperMock->expects($this->any())->method('isSelfRegCustomerAdminUser')->willReturn(1);
        $this->deliveryDataHelperMock->expects($this->any())->method('checkPermission')->willReturn(1);

        $this->assertEquals($returnValue, $this->revieworderHelper->checkIfUserHasReviewOrderPermission());
    }

    /**
     * Test checkIfUserHasReviewOrderPermission
     *
     * @return void
     */
    public function testCheckIfUserHasReviewOrderPermissionForDisableToggle()
    {
        $returnValue = false;
        $this->deliveryDataHelperMock->expects($this->any())->method('getToggleConfigurationValue')->willReturn(0);
       
        $this->assertEquals($returnValue, $this->revieworderHelper->checkIfUserHasReviewOrderPermission());
    }

    /**
     * Test checkIfUserHasReviewOrderPermission
     *
     * @return void
     */
    public function testCheckIfUserHasReviewOrderPermissionForNormalUser()
    {
        $returnValue = false;
        $this->deliveryDataHelperMock->expects($this->any())->method('getToggleConfigurationValue')->willReturn(1);
        $this->deliveryDataHelperMock->expects($this->any())->method('isSelfRegCustomerAdminUser')->willReturn(0);

        $this->assertEquals($returnValue, $this->revieworderHelper->checkIfUserHasReviewOrderPermission());
    }

    /**
     * Test getCompanyId
     *
     * @return void
     */
    public function testGetCompanyId()
    {
        $arrData = [
            'company_id' => 1
        ];
        $this->customerSession->expects($this->any())->method('getOndemandCompanyInfo')->willReturn($arrData);

        $this->assertEquals(1, $this->revieworderHelper->getCompanyId());
    }

    /**
     * Test SendResponseData
     *
     * @return void
     */
    public function testSendResponseData()
    {
        $resData = ['key' => 'value'];
        $this->customerSession->expects($this->once())
            ->method('setSuccessErrorData')
            ->with($resData);
        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with($resData)
            ->willReturnSelf();
 
        $response = $this->revieworderHelper->sendResponseData($resData, $this->resultJsonMock);
 
        $this->assertSame($this->resultJsonMock, $response);
    }

    /**
     * Test getCustomerId
     *
     * @return void
     */
    public function testGetCustomerId()
    {
        $this->customerSession->expects($this->once())
            ->method('getCustomerId')
            ->willReturn('123');
 
        $this->assertEquals('123', $this->revieworderHelper->getCustomerId());
    }
}
