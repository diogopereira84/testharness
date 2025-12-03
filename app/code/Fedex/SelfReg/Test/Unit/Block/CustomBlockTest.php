<?php
namespace Fedex\SelfReg\Test\Unit\Block;
use Fedex\Delivery\Helper\Data;
use Fedex\SelfReg\Block\CustomBlock;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Fedex\Commercial\Helper\CommercialHelper;

class CustomBlockTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    protected $commercialHelperMock;
    protected $urlInterfaceMock;
    /**
     * @var (\Magento\Framework\Escaper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $escaperMock;
    protected $deliveryDataHelper;
    protected $DataObject;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $customBlock;
    /**
     * @var \Fedex\Delivery\Helper\Data $helperDataMock
     */
    protected $helperDataMock;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helperDataMock = $this
            ->getMockBuilder(Data::class)
            ->setMethods(['isSelfRegCustomerAdminUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->commercialHelperMock = $this
            ->getMockBuilder(CommercialHelper::class)
            ->setMethods(['isRolePermissionToggleEnable','isCompanySettingsToggleEnable'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlInterfaceMock = $this
            ->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->setMethods(['getCurrentUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->escaperMock = $this
            ->getMockBuilder(\Magento\Framework\Escaper::class)
            ->setMethods(['escapeHtml'])
            ->disableOriginalConstructor()
            ->getMock();

            $this->deliveryDataHelper = $this->getMockBuilder(Data::class)
            ->setMethods(
                [
                    'getCustomer',
                    'getCustomAttribute',
                    'getValue',
                    'getToggleConfigurationValue',
                    'isCompanyAdminUser',
                    'isSelfRegCustomerAdminUser',
                    'checkPermission',
                    'isSdeCustomer'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->DataObject = $this->getMockBuilder( \Magento\Framework\DataObject::class)->setMethods(['getDataUsingMethod'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->customBlock = $this->objectManager->getObject(
            CustomBlock::class,
            [
                'context' => $this->context,
                'urlBuilder' => $this->urlInterfaceMock,
                '_urlBuilder' => $this->urlInterfaceMock,
                'helperData' => $this->deliveryDataHelper,
                '_escaper' => $this->escaperMock,
                'commercialHelper' => $this->commercialHelperMock
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
            \Fedex\SelfReg\Block\CustomBlock::class,
            '_toHtml',
        );
        $this->deliveryDataHelper->expects($this->any())->method('isSelfRegCustomerAdminUser')->willReturn(true);
        $this->deliveryDataHelper->method('getToggleConfigurationValue')->willReturn(true);
        $this->deliveryDataHelper->method('checkPermission')->willReturn(true);
        $customAttributeMock = $this->getMockBuilder(\Magento\Framework\Api\AttributeInterface::class)
        ->getMock();
         $this->urlInterfaceMock->method('getCurrentUrl')->willReturn('https://staging3.office.fedex.com/ondemand/mgs/customer/account/sharedcreditcards/');
         $this->deliveryDataHelper->expects($this->any())->method('isSelfRegCustomerAdminUser')->willReturn(true);
         $this->DataObject->method('getDataUsingMethod')->willReturn('a/b/c');
         $testMethod->setAccessible(true);
	     $this->commercialHelperMock->expects($this->any())->method('isRolePermissionToggleEnable')->willReturn(true);
         $this->commercialHelperMock->expects($this->any())->method('isCompanySettingsToggleEnable')->willReturn(true);
         $expectedResult = $testMethod->invoke($this->customBlock);
    }

    /**
     * Assert _toHtml in Negative case
     *
     * @return ''
     */
    public function testToHtmlWhenModuleDisable()
    {
        $testMethod = new \ReflectionMethod(
            \Fedex\SelfReg\Block\CustomBlock::class,
            '_toHtml',
        );
        $this->deliveryDataHelper->expects($this->any())->method('isSdeCustomer')->willReturn(false);
        $this->deliveryDataHelper->expects($this->any())->method('isSelfRegCustomerAdminUser')->willReturn(false);
        $testMethod->setAccessible(true);
        $this->commercialHelperMock->expects($this->any())->method('isRolePermissionToggleEnable')->willReturn(false);
        $this->commercialHelperMock->expects($this->any())->method('isCompanySettingsToggleEnable')->willReturn(false);
        $expectedResult = $testMethod->invoke($this->customBlock);
        $this->assertNull($expectedResult);
    }

        /**
     * test sort order
     *
     * @return ''
     */
    public function testGetSortOrder()
    {
        $this->customBlock->getSortOrder();
    }
}
