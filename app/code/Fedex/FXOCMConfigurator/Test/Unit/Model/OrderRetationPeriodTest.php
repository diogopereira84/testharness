<?php
namespace Fedex\FXOCMConfigurator\Test\Unit\Model;

use Fedex\FXOCMConfigurator\Model\OrderRetationPeriod;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\FXOCMConfigurator\Model\OptimizeProductInstanceMessage;
use Fedex\FXOCMConfigurator\Model\ResourceModel\OrderRetationPeriod as OrderRetationPeriodResourceModel;
use Magento\Framework\Model\AbstractModel;

class OrderRetationPeriodTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $orderRetationPeriod;
    /**
     * @var string
     */
    protected $message;

    /**
     * Description Creating mock for the variables
     * {@inheritdoc}
     *
     * @return MockBuilder
     */

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);


        $this->orderRetationPeriod = $this->getMockBuilder(OrderRetationPeriod::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderRetationPeriod = $this->objectManager->getObject(
            OrderRetationPeriod::class,
            [ ]
        );
    }



    /**
     * @inheritDoc
     */
    public function testGetId()
    {
         $this->assertNull($this->orderRetationPeriod->getId());
    }

    /**
     * @inheritDoc
     */
    public function testSetId()
    {
        $this->assertIsObject($this->orderRetationPeriod->setId(2));
    }

    /**
     * @inheritDoc
     */
    public function testGetOrderItemId()
    {
        $this->assertNull($this->orderRetationPeriod->getOrderItemId());
    }

    /**
     * @inheritDoc
     */
    public function testSetOrderItemId()
    {
        $this->assertIsObject($this->orderRetationPeriod->setOrderItemId(22341));
    }

    /**
     * @inheritDoc
     */
    public function testGetDocumentId()
    {
        $this->assertNull($this->orderRetationPeriod->getDocumentId());
    }

    /**
     * @inheritDoc
     */
    public function testSetDocumentId()
    {
        $this->assertIsObject($this->orderRetationPeriod->setDocumentId('f1eca806-e5d6-11ee-baf7-f5c39bebd2ec'));
    }


        /**
     * @inheritDoc
     */
    public function testGetExtendedDate()
    {
        $this->assertNull($this->orderRetationPeriod->getExtendedDate());
    }

    /**
     * @inheritDoc
     */
    public function testSetExtendedDate()
    {
        $this->assertIsObject($this->orderRetationPeriod->setExtendedDate('2023-03-20'));
    }


    /**
     * @inheritDoc
     */
    public function testGetExtendedFlag()
    {
        $this->assertNull($this->orderRetationPeriod->getExtendedFlag());
    }

    /**
     * @inheritDoc
     */
    public function testSetExtendedFlag()
    {
        $this->assertIsObject($this->orderRetationPeriod->setExtendedFlag('3'));
    }
   

    /**
     * @inheritDoc
     */
    public function testGetCreatedAt()
    {
        $this->assertNull($this->orderRetationPeriod->getCreatedAt());
    }

    /**
     * @inheritDoc
     */
    public function testSetCreatedAt()
    {
         $this->assertIsObject($this->orderRetationPeriod->setCreatedAt('12/01/2023'));
    }

    /**
     * @inheritDoc
     */
    public function testGetUpdatedAt()
    {
         $this->assertNull($this->orderRetationPeriod->getUpdatedAt());
    }

    /**
     * @inheritDoc
     */
    public function testSetUpdatedAt()
    {
        $this->assertIsObject($this->orderRetationPeriod->setUpdatedAt('12/01/2023'));
    }
}
