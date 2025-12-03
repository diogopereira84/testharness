<?php

namespace Fedex\SelfReg\Test\Unit\Plugin\Model\Email;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\SelfReg\Plugin\Model\Email\Sender;
use Magento\Company\Model\Email\Sender as CoreSender;
use Magento\Customer\Api\Data\CustomerInterface;

class SenderTest extends TestCase
{

    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfig;
    protected $subject;
    protected $customer;
    protected $selfReg;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $pluginObj;
    /**
     * Setup for Test Case
     */
    protected function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = $this->getMockBuilder(CoreSender::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->selfReg = $this->getMockBuilder(SelfReg::class)
            ->setMethods(['isSelfRegCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->pluginObj = $this->objectManager->getObject(
            Sender::class,
            [
                'toggleConfig' => $this->toggleConfig,
                'selfReg' => $this->selfReg
            ]
        );
    }

    /**
     * @test testAroundSendUserStatusChangeNotificationEmail
     */
    public function testAroundSendUserStatusChangeNotificationEmail()
    {
        $this->selfReg->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $subject = $this->subject;
        $proceed = function () use ($subject) {
            return $subject;
        };
        $result = $this->pluginObj->aroundSendUserStatusChangeNotificationEmail($subject, $proceed, $this->customer , 1);
        $this->assertInstanceOf(\Magento\Company\Model\Email\Sender::class, $result);
    }

    /**
     * @test testAroundSendUserStatusChangeNotificationEmailWithoutSelf
     */
    public function testAroundSendUserStatusChangeNotificationEmailWithoutSelf()
    {
        $this->selfReg->expects($this->any())->method('isSelfRegCustomer')->willReturn(false);
        $subject = $this->subject;
        $proceed = function () use ($subject) {
            return $subject;
        };
        $result = $this->pluginObj->aroundSendUserStatusChangeNotificationEmail($subject, $proceed, $this->customer , 1);
        $this->assertInstanceOf(\Magento\Company\Model\Email\Sender::class, $result);
    }
}
