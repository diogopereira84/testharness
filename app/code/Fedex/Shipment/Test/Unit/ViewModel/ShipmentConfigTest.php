<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Shipment\Test\Unit\ViewModel;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Mail\TransportInterface;
use Fedex\Shipment\ViewModel\ShipmentConfig;

/*
 * Use class to send OMS new status order email
*/
class ShipmentConfigTest extends TestCase
{
    protected $transportInterface;
    protected $storeInterface;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;
    protected $shipmentConfigMock;
    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigInterface;

    /**
     * @var TransportBuilder|MockObject
     */
    protected $transportBuilder;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManager;

    /**
     * @var StateInterface|MockObject
     */
    protected $inlineTranslation;

    /**
     * Test setup
     */
    public function setUp(): void
    {
        $this->scopeConfigInterface = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->transportBuilder = $this->getMockBuilder(TransportBuilder::class)
            ->setMethods([
                'setTemplateIdentifier',
                'setTemplateOptions',
                'setTemplateVars',
                'setFrom',
                'addTo',
                'getTransport'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->transportInterface = $this->getMockBuilder(TransportInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendMessage'])
            ->getMockForAbstractClass();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->storeInterface = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();

        $this->inlineTranslation = $this->getMockBuilder(StateInterface::class)
            ->setMethods(["suspend", 'resume'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManager($this);

        $this->shipmentConfigMock = $this->objectManagerHelper->getObject(
            ShipmentConfig::class,
            [
                'scopeConfigInterface' => $this->scopeConfigInterface,
                'transportBuilder' => $this->transportBuilder,
                'storeManager' => $this->storeManager,
                'inlineTranslation' => $this->inlineTranslation
            ]
        );
    }

    /**
     * Use to get store configuration value
     */
    public function testGetConfigValue()
    {
        $path = 'config_path';
        $storeId = 1;
        $this->scopeConfigInterface->expects($this->any())->method('getValue')->willReturn(true);

        $this->assertEquals(true, $this->shipmentConfigMock->getConfigValue($path, $storeId));
    }

    /**
     * Use to generate template
     */
    public function testGenerateTemplate()
    {
        $emailTemplateVariables = [];
        $senderInfo = ['email'=>'fedex@gmail.com', 'name'=> 'fedex'];
        $receiverInfo = ['email'=>'test@gmail.com', 'name'=> 'test'];
        $templateId = 1;
        $this->transportBuilder->expects($this->any())->method('setTemplateIdentifier')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->expects($this->any())->method('getId')->willReturn(1);
        $this->transportBuilder->expects($this->any())->method('setTemplateOptions')->willReturnSelf();
        $this->transportBuilder->expects($this->any())->method('setTemplateVars')->willReturnSelf();
        $this->transportBuilder->expects($this->any())->method('setFrom')->willReturnSelf();
        $this->transportBuilder->expects($this->any())->method('addTo')->willReturnSelf();
        $this->assertNotNull(
            $this->shipmentConfigMock->generateTemplate(
                $emailTemplateVariables,
                $senderInfo,
                $receiverInfo,
                $templateId
            )
        );
    }

    /**
     * Use to send order status mail
     */
    public function testSendOrderStatusMail()
    {
        $emailTemplateVariables = [];
        $senderInfo = ['email'=>'fedex@gmail.com', 'name'=> 'fedex'];
        $receiverInfo = ['email'=>'test@gmail.com', 'name'=> 'test'];
        $templateId = 1;
        $this->inlineTranslation->expects($this->any())->method('suspend')->willReturnSelf();
        $this->testGenerateTemplate();
        $this->transportBuilder->expects($this->any())->method('getTransport')->willReturn($this->transportInterface);
        $this->transportInterface->expects($this->any())->method('sendMessage')->willReturnSelf();
        $this->inlineTranslation->expects($this->any())->method('resume')->willReturnSelf();
        $this->assertEquals(
            null,
            $this->shipmentConfigMock->sendOrderStatusMail(
                $emailTemplateVariables,
                $senderInfo,
                $receiverInfo,
                $templateId
            )
        );
    }
}
