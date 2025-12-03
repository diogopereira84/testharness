<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomerExportEmail\Test\Unit\Plugin\Controller\Adminhtml\Export;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\CustomerExportEmail\Plugin\Controller\Adminhtml\Export\GridToCsvPlugin;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Backend\Model\Auth\Session;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Message\ManagerInterface;
use Fedex\CIDPSG\Helper\Email;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Controller\Adminhtml\Export\GridToCsv;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\CustomerExportEmail\Model\Export\ExportInfoFactory;
use Fedex\CustomerExportEmail\Model\Component\MassAction\Filter;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Fedex\CustomerExportEmail\Helper\Data;
use Psr\Log\LoggerInterface;

/**
 * Test class for AbstractConfig
 */
class GridToCsvPluginTest extends TestCase
{
    /**
     * @var (\Magento\Backend\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $sessionMock;
    protected $toggleConfig;
    protected $emailMock;
    protected $storeManagerMock;
    /**
     * @var (\Magento\Framework\MessageQueue\PublisherInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $publisherMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var (\Fedex\CustomerExportEmail\Model\Export\ExportInfoFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $exportInfoFactoryMock;
    protected $filterMock;
    protected $customerCollectionMock;
    protected $collectionFactoryMock;
    /**
     * @var (\Fedex\CustomerExportEmail\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $helperDataMock;
    /**
     * @var (\Magento\Framework\Message\ManagerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $messageManagerInterface;
    /**
     * @var (\Magento\Framework\App\Config\ScopeConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $scopeConfigInterface;
    protected $subject;
    protected $request;
    /**
     * @var GridToCsvPlugin|MockObject
     */
    protected $gridToCsvPlugin;

    /** @var RedirectFactory|MockObject */
    protected $resultRedirectFactory;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {

        $this->contextMock = $this->getMockBuilder(Context::class)
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->sessionMock = $this->getMockBuilder(Session::class)
                                ->setMethods(['getUser','getEmail'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
                                ->setMethods(['getToggleConfigValue'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->emailMock = $this->getMockBuilder(Email::class)
                                ->setMethods(['callGenericEmailApi','loadEmailTemplate'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
                                    ->setMethods(['getStore','getId'])
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();

        $this->publisherMock = $this->getMockBuilder(PublisherInterface::class)
                                    ->setMethods(['publish'])
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
                                    ->setMethods(['critical'])
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();


        $this->resultRedirectFactory = $this->getMockBuilder(RedirectFactory::class)
                                            ->setMethods(['create', 'setPath'])
                                            ->disableOriginalConstructor()
                                            ->getMock();

        $this->exportInfoFactoryMock = $this->getMockBuilder(ExportInfoFactory::class)
                                            ->setMethods(['create'])
                                            ->disableOriginalConstructor()
                                            ->getMock();

        $this->filterMock = $this->getMockBuilder(Filter::class)
                                            ->setMethods(['getCollection'])
                                            ->disableOriginalConstructor()
                                            ->getMock();

        $this->customerCollectionMock =  $this->getMockBuilder(Collection::class)
                                            ->disableOriginalConstructor()
                                            ->getMock();

        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
                                            ->setMethods(['create'])
                                            ->disableOriginalConstructor()
                                            ->getMock();

        $this->helperDataMock = $this->getMockBuilder(Data::class)
                                            ->setMethods(['getInActiveColumns'])
                                            ->disableOriginalConstructor()
                                            ->getMock();

        $this->messageManagerInterface = $this->getMockBuilder(ManagerInterface::class)
                                                    ->disableOriginalConstructor()
                                                    ->getMockForAbstractClass();

        $this->scopeConfigInterface = $this->getMockBuilder(ScopeConfigInterface::class)
                                                    ->disableOriginalConstructor()
                                                    ->getMockForAbstractClass();



        $this->subject = $this->getMockBuilder(GridToCsv::class)
                                ->setMethods(['getRequest','getParams'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->request = $this->getMockBuilder(Http::class)
                            ->disableOriginalConstructor()
                            ->setMethods(['getParams'])
                            ->getMock();

        $this->gridToCsvPlugin = $this->getMockForAbstractClass(
            GridToCsvPlugin::class,
            [
                'adminSession' => $this->sessionMock,
                'toggleConfig' => $this->toggleConfig,
                'messageManager' => $this->messageManagerInterface,
                'email' => $this->emailMock,
                'storeManager' => $this->storeManagerMock,
                'configInterface' => $this->scopeConfigInterface,
                'request' => $this->request,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'messagePublisher' => $this->publisherMock,
                'exportInfoFactory' => $this->exportInfoFactoryMock,
                'logger' => $this->loggerMock,
                'filter' => $this->filterMock,
                'collectionFactory' => $this->collectionFactoryMock,
                'helperData' => $this->helperDataMock
            ]
        );
    }

    /**
     * Test testAroundExecute function
     *
     * @return void
     */
    public function testAroundExecute()
    {
        $proceed = function () {
            $this->subject->execute();
        };

        $this->request->expects($this->any())->method('getParams')->willReturn(['namespace' => 'customer_listing']);

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturnSelf();

        $this->sessionMock->expects($this->once())->method('getUser')->willReturnSelf();

        $this->sessionMock->expects($this->once())->method('getEmail')->willReturn('test@test.com');

        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturnSelf();

        $this->storeManagerMock->expects($this->any())->method('getId')->willReturn(1);

        $this->emailMock->expects($this->any())->method('loadEmailTemplate')->willReturn('Template Data');

        $this->filterMock->expects($this->once())->method('getCollection')->with($this->customerCollectionMock)->willReturnArgument(0);

        $this->collectionFactoryMock->expects($this->once())->method('create')->willReturn($this->customerCollectionMock);

        $this->resultRedirectFactory->expects($this->once())->method('setPath')->willReturnSelf("customer/index/index");

        $this->assertEquals($this->resultRedirectFactory, $this->gridToCsvPlugin
        ->aroundExecute($this->subject, $proceed));
    }

    /**
     * Test testAroundExecute with Exception function
     *
     * @return void
     */
    public function testAroundExecutewithException()
    {
        $proceed = function () {
            $this->subject->execute();
        };

        $this->request->expects($this->any())->method('getParams')->willReturn(['namespace' => 'customer_listing']);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturnSelf();

        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->sessionMock->expects($this->once())->method('getUser')->willThrowException($exception);

        $this->resultRedirectFactory->expects($this->once())->method('setPath')->willReturnSelf("customer/index/index");


        $this->assertEquals($this->resultRedirectFactory, $this->gridToCsvPlugin
        ->aroundExecute($this->subject, $proceed));
    }
}
