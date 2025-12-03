<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Controller\Adminhtml\Manual;

use Fedex\SharedCatalogCustomization\Controller\Adminhtml\Manual\CatalogSync;
use Fedex\SharedCatalogCustomization\Helper\Data;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Backend\Model\View\Result\RedirectFactory as BackendRedirectFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test controller for Adminhtml\Munal\CatalogSync.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CatalogSyncTest extends TestCase
{
    /**
     * Sample Name
     * @var string
     */
    const NAME = 'Test';

    /**
     * Sample Customer Group ID
     * @var int
     */
    const CUSTOMER_GROUP_ID = 1;

    /**
     * Sample Legacy Catalog Root Folder Id
     * @var string
     */
    const LEGACY_CATALOG_ROOT_FOLDER_ID = 'Legacyfolder1312';

    /**
     * @var CatalogSync|MockObject
     */
    private $catalogSyncMock;

    /**
     * @var Data|MockObject
     */
    private $catalogSyncQueueHelperMock;

    /**
     * @var ToggleConfig|MockObject
     */
    private $toggleConfigMock;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var Session|MockObject
     */
    private $authSessionMock;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory|MockObject
     */
    private $resultRedirectFactoryMock;

    /**
     * @var Http|MockObject
     */
    private $requestMock;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var User|MockObject
     */
    private $userMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->catalogSyncMock = $this->createMock(CatalogSync::class);

        $this->catalogSyncQueueHelperMock = $this
            ->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->contextMock = $this->createMock(Context::class);

        $this->authSessionMock = $this->getMockBuilder(Session::class)
            ->addMethods(['getUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultRedirectFactoryMock = $this
            ->getMockBuilder(BackendRedirectFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->createMock(Http::class);
        
        $this->userMock = $this->createMock(User::class);

        $this->objectManager = new ObjectManager($this);
    }

    /**
     * Test for method Execute.
     *
     * @return void
     */
    public function testExecute()
    {
        $sampleRedirectResult = 'shared_catalog/sharedCatalog/index';
        $firstNameSample = 'Test';
        $LastNameSample = 'Singh';

        $this->userMock->expects($this->any())
            ->method('getFirstname')
            ->willReturn($firstNameSample);
        $this->userMock->expects($this->any())
            ->method('getLastname')
            ->willReturn($LastNameSample);

        $this->authSessionMock->expects($this->any())
            ->method('getUser')
            ->willReturn($this->userMock);

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['name', null, static::NAME],
                    ['customer_group_id', null, static::CUSTOMER_GROUP_ID],
                    ['legacy_catalog_root_folder_id', null, static::LEGACY_CATALOG_ROOT_FOLDER_ID],
                ]
            );

        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(0);

        $redirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $redirect->expects($this->once())->method('setPath')->with($sampleRedirectResult)
            ->willReturnSelf();

        $this->resultRedirectFactoryMock->expects($this->once())->method('create')->willReturn($redirect);

        $this->catalogSyncMock = $this->objectManager->getObject(
            CatalogSync::class,
            [
                'context' => $this->contextMock,
                'redirectFactory' => $this->resultRedirectFactoryMock,
                'authSession' => $this->authSessionMock,
                'catalogSyncQueueHelper' => $this->catalogSyncQueueHelperMock,
                'toggleConfig' => $this->toggleConfigMock
            ]
        );

        $result = $this->catalogSyncMock->execute();

        $this->assertInstanceOf(get_class($redirect), $result);
    }

    /**
     * Test for method Execute with toggle enable
     *
     * @return void
     */
    public function testExecuteWithToggle()
    {
        $sampleRedirectResult = 'shared_catalog/sharedCatalog/index';
        $firstNameSample = 'Test';
        $LastNameSample = 'Singh';

        $this->userMock->expects($this->any())
            ->method('getFirstname')
            ->willReturn($firstNameSample);
        $this->userMock->expects($this->any())
            ->method('getLastname')
            ->willReturn($LastNameSample);

        $this->authSessionMock->expects($this->any())
            ->method('getUser')
            ->willReturn($this->userMock);

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['name', null, static::NAME],
                    ['customer_group_id', null, static::CUSTOMER_GROUP_ID],
                    ['legacy_catalog_root_folder_id', null, static::LEGACY_CATALOG_ROOT_FOLDER_ID],
                ]
            );

        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);

        $redirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $redirect->expects($this->once())->method('setPath')->with($sampleRedirectResult)
            ->willReturnSelf();

        $this->resultRedirectFactoryMock->expects($this->once())->method('create')->willReturn($redirect);

        $this->catalogSyncMock = $this->objectManager->getObject(
            CatalogSync::class,
            [
                'context' => $this->contextMock,
                'redirectFactory' => $this->resultRedirectFactoryMock,
                'authSession' => $this->authSessionMock,
                'catalogSyncQueueHelper' => $this->catalogSyncQueueHelperMock,
                'toggleConfig' => $this->toggleConfigMock
            ]
        );

        $result = $this->catalogSyncMock->execute();

        $this->assertInstanceOf(get_class($redirect), $result);
    }
}
