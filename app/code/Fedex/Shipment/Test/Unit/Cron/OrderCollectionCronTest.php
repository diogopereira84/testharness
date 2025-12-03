<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Shipment\Test\Unit\Cron;

use Fedex\Shipment\Cron\OrderCollectionCron;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Fedex\Shipment\ViewModel\ShipmentConfig;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Test class for OrderCollectionCron
 */
class OrderCollectionCronTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    const RECEIVER_EMAIL = "oms_email_configuration/config/email_id";

    const SENDER_EMAIL = "trans_email/ident_general/email";

    const EMAIL_TEMPLATE_ID = "oms_email_configuration/config/email_template";

    const OMS_EMAIL_CRON_TOGGLE = "environment_toggle_configuration/environment_toggle/enable_oms_new_status_cron";

    /**
     * @var DateTime $dateTime
     */
    protected $dateTime;

    /**
     * @var CollectionFactory $orderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var ShipmentConfig $shipmentConfig
     */
    protected $shipmentConfig;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var OrderCollectionCron $orderCollectionCronConfig
     */
    protected $orderCollectionCronConfig;

    /**
     * Test setup
     */
    public function setUp(): void
    {
        $this->dateTime = $this->getMockBuilder(DateTime::class)
                                        ->disableOriginalConstructor()
                                        ->setMethods([
                                                'date'
                                            ])
                                        ->getMock();

        $this->orderCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
                                        ->disableOriginalConstructor()
                                        ->setMethods([
                                                'create',
                                                'addFieldToSelect',
                                                'addFieldToFilter',
                                                'getSelect',
                                                'where'
                                            ])
                                        ->getMock();

        $this->shipmentConfig = $this->getMockBuilder(ShipmentConfig::class)
                                    ->disableOriginalConstructor()
                                    ->setMethods([
                                            'getConfigValue',
                                            'sendOrderStatusMail'
                                        ])
                                    ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
                                    ->disableOriginalConstructor()
                                    ->setMethods(['getStore', 'getId'])
                                    ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->orderCollectionCronConfig = $this->objectManager->getObject(
            OrderCollectionCron::class,
            [
                'dateTime' => $this->dateTime,
                'orderCollectionFactory' => $this->orderCollectionFactory,
                'shipmentConfig' => $this->shipmentConfig,
                'storeManager' => $this->storeManager
            ]
        );
    }

    /**
     * Test getNewStatusOrderCollection function
     *
     * @return void
     */
    public function testGetNewStatusOrderCollection()
    {
        $storeId = 1;

        $this->storeManager->expects($this->any())
                            ->method('getStore')
                            ->willReturn($this->storeManager);
        $this->storeManager->expects($this->any())
                            ->method('getId')
                            ->willReturn($storeId);
        $this->shipmentConfig->expects($this->any())
                            ->method('getConfigValue')
                            ->will($this->returnValue(true));
        $this->orderCollectionFactory->expects($this->any())
                            ->method('create')
                            ->willReturn($this->orderCollectionFactory);
        $this->orderCollectionFactory->expects($this->any())
                            ->method('addFieldToSelect')
                            ->willReturn($this->orderCollectionFactory);
        $this->orderCollectionFactory->expects($this->any())
                            ->method('addFieldToFilter')
                            ->willReturn($this->orderCollectionFactory);
        $this->orderCollectionFactory->expects($this->any())
                            ->method("getSelect")
                            ->willReturn($this->orderCollectionFactory);
        $this->orderCollectionFactory->expects($this->any())
                            ->method("where")
                            ->willReturn($this->orderCollectionFactory);

        $this->assertNotEquals(null, $this->orderCollectionCronConfig->getNewStatusOrderCollection());
    }

    /**
     * Test getNewStatusOrderCollection function with exception
     *
     * @return void
     */
    public function testGetNewStatusOrderCollectionWithException()
    {
        $storeId = 1;
        $this->storeManager->expects($this->any())
                            ->method('getStore')
                            ->willReturn($this->storeManager);
        $this->storeManager->expects($this->any())
                            ->method('getId')
                            ->willReturn($storeId);
        $this->shipmentConfig->expects($this->any())
                            ->method('getConfigValue')
                            ->with(self::OMS_EMAIL_CRON_TOGGLE, 1)
                            ->will($this->returnValue(true));
        $this->orderCollectionFactory->expects($this->any())->method('create')->willThrowException(new \Exception());

        $this->assertNotEquals(null, $this->orderCollectionCronConfig->getNewStatusOrderCollection());
    }
}
