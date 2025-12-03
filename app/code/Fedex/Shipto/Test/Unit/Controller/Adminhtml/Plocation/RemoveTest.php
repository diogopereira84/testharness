<?php

namespace Fedex\Company\Test\Unit\Controller\Adminhtml\Plocation;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\View\Result\PageFactory;
use Fedex\Shipto\Model\ProductionLocationFactory;
use Fedex\Shipto\Model\ProductionLocation;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\Shipto\Controller\Adminhtml\Plocation\Remove;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Customer\Model\Session;

/**
 * Unit tests for adminhtml company save controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RemoveTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var (\Magento\Framework\View\Result\PageFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $pageFactoryMock;
    protected $productionLocationFactoryMock;
    protected $productionLocationMock;
    protected $jsonFactoryMock;
    protected $jsonMock;
    /**
     * @var (\Magento\Framework\Serialize\SerializerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $serializer;
    /**
     * @var (\Magento\Customer\Model\Session & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $customerSession;
    protected $requestMock;
    protected $messageManager;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $removeMock;
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productionLocationFactoryMock = $this->getMockBuilder(ProductionLocationFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->productionLocationMock = $this->getMockBuilder(ProductionLocation::class)
            ->disableOriginalConstructor()
            ->setMethods(['load','delete','loadByIncrementId'])
            ->getMock();

        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllLocations'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->removeMock = $this->objectManager->getObject(
            Remove::class,
            [
                'logger' => $this->loggerMock,
                'pageFactory' => $this->pageFactoryMock,
                'productionLocationFactory' => $this->productionLocationFactoryMock,
                'jsonFactory' => $this->jsonFactoryMock,
                'serializer' => $this->serializer,
                'customerSession' => $this->customerSession,
                '_request' => $this->requestMock,
                'messageManager' => $this->messageManager,
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecutewithIsNotRecommended()
    {
        // Data for is_recommended_store is not passed
        $dataRestricted['location_id'] = [3, 4];
        $this->requestMock->expects($this->any())->method('getParams')->willReturn($dataRestricted);
        $this->productionLocationFactoryMock->expects($this->any())->method('create')
                ->willReturn($this->productionLocationMock);
        $this->productionLocationMock->expects($this->any())->method('loadByIncrementId')->willReturnSelf();
        $this->productionLocationMock->expects($this->any())->method('delete')->willReturnSelf();
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->messageManager->expects($this->any())
                ->method('addSuccess')
                ->with('Restricted Store Remove successfully')
                ->willReturnSelf();

        $this->assertEquals($this->jsonMock, $this->removeMock->execute());
    }
    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecutewithIsRecommended()
    {
        //Data for is_recommended_store is true
        $dataRecommended['location_id'] = [3, 4];
        $dataRecommended['is_recommended_store'] = true;

        $this->requestMock->expects($this->any())->method('getParams')->willReturn($dataRecommended);
        $this->productionLocationFactoryMock->expects($this->any())->method('create')
                ->willReturn($this->productionLocationMock);
        $this->productionLocationMock->expects($this->any())->method('loadByIncrementId')->willReturnSelf();
        $this->productionLocationMock->expects($this->any())->method('delete')->willReturnSelf();

        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->messageManager->expects($this->any())
            ->method('addSuccess')
            ->with('Recommended Store Removed successfully')
            ->willReturnSelf();

        $this->assertEquals($this->jsonMock, $this->removeMock->execute());
    }

    public function testExecuteWithoutLocationId()
    {

        $data['location_id'] = [];
        $this->requestMock->expects($this->any())->method('getParams')->willReturn($data);
        $this->productionLocationFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->productionLocationMock);
        $this->productionLocationMock->expects($this->any())->method('loadByIncrementId')->willReturnSelf();
        $this->productionLocationMock->expects($this->any())->method('delete')->willReturnSelf();

        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonMock, $this->removeMock->execute());
    }

    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Something went wrong. Please try again later.'));
        $exception = new \Exception($phrase);
        $data['location_id'] = [3,4];
        $this->requestMock->expects($this->any())->method('getParams')->willReturn($data);
        $this->productionLocationFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->productionLocationMock);
        $this->productionLocationMock->expects($this->any())->method('loadByIncrementId')->willReturnSelf();
        $this->productionLocationMock->expects($this->any())->method('delete')->willThrowException($exception);

        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonMock, $this->removeMock->execute());
    }
}
