<?php
namespace Fedex\ReorderInstance\Test\Unit\Model;

use Fedex\ReorderInstance\Api\ReorderMessageInterface;
use Fedex\ReorderInstance\Model\ReorderMessage;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ReorderMessageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var (\Fedex\ReorderInstance\Api\ReorderMessageInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $reorderMessageInterface;
    protected $reorderMessage;
    /**
     * Description Creating mock for the variables
     * {@inheritdoc}
     *
     * @return MockBuilder
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->reorderMessageInterface = $this->getMockBuilder(ReorderMessageInterface::class)
            ->setMethods(['setMessage', 'getMessage'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->reorderMessage    = $this->objectManager->getObject(
            ReorderMessage::class,
            []
        );
    }

    /**
     * Assert getMessage.
     *
     * @return string
     */
    public function testGetMessage()
    {
        $this->assertEquals(null, $this->reorderMessage->getMessage());
    }

    /**
     * Assert setMessage.
     *
     * @return string
     */
    public function testSetMessage()
    {
        $this->assertEquals('message string', $this->reorderMessage->setMessage('message string'));
    }
}
