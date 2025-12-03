<?php

namespace Fedex\Shipto\Test\Unit\Plugin\Quote;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\Item as CoreItem;
use Fedex\Shipto\Plugin\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Customer\Model\Session as CustomerSession;

class ItemTest extends TestCase
{
    protected $itemMock;
    protected $jsonMock;
    protected $optionMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $pluginObj;
    /**
     * @var CustomerSession $customerSession
     */
    protected $customerSession;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;
    private \Closure $closureMock;

    /**
     * Setup for Test Case
     */
    protected function setUp(): void
    {
        $this->itemMock = $this->getMockBuilder(CoreItem::class)
            ->setMethods(['getOptionByCode', 'getQty'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['unserialize'])
            ->getMock();
        $this->optionMock = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompareItem'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->pluginObj = $this->objectManager->getObject(
            Item::class,
            [
                'serializer' => $this->jsonMock,
                'toggleConfig' => $this->toggleConfig,
                'customerSession' => $this->customerSession
            ]
        );
    }

    /**
     * The test itself, every test function must start with 'test'
     * @test testAroundGetBuyRequest
     */
    public function testAroundGetBuyRequest()
    {
        $exception = new \Exception();
        $subject = $this->itemMock;
        $this->closureMock = function () use ($subject) {
            return $subject;
        };
        $this->itemMock->expects($this->any())->method('getOptionByCode')->willReturn($this->optionMock);
        $this->optionMock->expects($this->any())->method('getValue')->willReturn(null);
        $this->jsonMock->expects($this->any())->method('unserialize')->willThrowException($exception);
        $this->itemMock->expects($this->any())->method('getQty')->willReturn(1);
        $result = $this->pluginObj->aroundGetBuyRequest($subject, $this->closureMock);
        $this->assertInstanceOf(\Magento\Framework\DataObject::class, $result);
    }

    /**
     * The test itself, every test function must start with 'test'
     * @test testAroundGetBuyRequest
     */
    public function testAroundGetBuyRequestWithoutException()
    {
        $subject = $this->itemMock;
        $this->closureMock = function () use ($subject) {
            return $subject;
        };
        $this->itemMock->expects($this->any())->method('getOptionByCode')->willReturn($this->optionMock);
        $this->optionMock->expects($this->any())->method('getValue')->willReturn('[]');
        $this->jsonMock->expects($this->any())->method('unserialize')->willReturn([]);
        $this->itemMock->expects($this->any())->method('getQty')->willReturn(1);
        $result = $this->pluginObj->aroundGetBuyRequest($subject, $this->closureMock);
        $this->assertInstanceOf(CoreItem::class, $result);
    }

    /**
     * Test afterCompare
     *
     * @return void
     */
    public function testAfterCompare()
    {
        $subject = $this->itemMock;
        $this->closureMock = function () use ($subject) {
            return $subject;
        };
        $returnValue = true;
        $this->customerSession->expects($this->any())->method('getCompareItem')->willReturn($returnValue);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn($returnValue);

        $this->assertFalse($this->pluginObj->afterCompare($subject, $this->closureMock));
    }
}
