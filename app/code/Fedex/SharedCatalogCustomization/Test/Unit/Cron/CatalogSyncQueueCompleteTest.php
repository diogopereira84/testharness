<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Cron;

use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue\CollectionFactory;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue\Collection;
use Fedex\SharedCatalogCustomization\Helper\EmailData;
use Magento\Framework\DataObject;
use Fedex\SharedCatalogCustomization\Helper\ManageCatalogItems;
use Fedex\SharedCatalogCustomization\Cron\CatalogSyncQueueComplete;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CatalogSyncQueueCompleteTest extends TestCase
{
    protected $collectionMock;
    protected $toggleConfigMock;
    protected $catalogSyncQueueCompleteMock;
    private const TOGGLE_FEATURE_KEY = 'explorers_catalog_migration';
    /**
     * @var CollectionFactory|MockObject
     */
    private $collectionFactoryMock;

    /**
     * @var EmailData|MockObject
     */
    private $emailHelperDataMock;

    /**
     * @var ManageCatalogItems|MockObject
     */
    private $manageCatalogItemsMock;

    /**
     * @var ObjectManager|MockObject
     */
    private $objectManager;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->collectionMock = $this->createPartialMock(Collection::class, ['load', 'addFieldToFilter', 'getSize']);

        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->emailHelperDataMock = $this->getMockBuilder(EmailData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->manageCatalogItemsMock = $this->getMockBuilder(ManageCatalogItems::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->catalogSyncQueueCompleteMock = $this->objectManager->getObject(
            CatalogSyncQueueComplete::class,
            [
                'catalogSyncQueueCollectionFactory' => $this->collectionFactoryMock,
                'emailHelperData' => $this->emailHelperDataMock,
                'manageCatalogItems' => $this->manageCatalogItemsMock,
                'toggleConfig' => $this->toggleConfigMock
            ]
        );
    }

    /**
     * Test execute method
     *
     * @return void
     */
    public function testExecute()
    {
        $id = 2;
        $companyId = 2;
        $legacyCatalogRootFolderId = 6;
        $sharedCatalogId = 47;
        $status = 'processing';
        $emailSent = 0;
        $createdBy = 'Brajmohan Rajput';
        $emailId = 'brajmohan.rajput@infogain.com';

        $this->collectionFactoryMock->expects($this->atLeastOnce())->method('create')
            ->willReturn($this->collectionMock);

        $item = new DataObject(
            [
                'id' => $id,
                'company_id' => $companyId,
                'legacy_catalog_root_folder_id' => $legacyCatalogRootFolderId,
                'shared_catalog_id' => $sharedCatalogId,
                'status' => $status,
                'email_sent' => $emailSent,
                'created_by' => $createdBy,
                'email_id' => $emailId
            ]
        );
        $this->collectionMock->addItem($item);
                                    
        $this->collectionMock->expects($this->atLeastOnce())->method('addFieldToFilter')
            ->with('status', ['eq' => $status])
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->once())->method('getSize')->willReturn(1);

        $this->catalogSyncQueueCompleteMock->execute();
    }

    /**
     * Test execute IsImport
     *
     * @return void
     */
    public function testExecuteIsImport()
    {
        $id = 2;
        $companyId = 2;
        $legacyCatalogRootFolderId = 6;
        $sharedCatalogId = 47;
        $status = 'processing';
        $emailSent = 0;
        $createdBy = 'Brajmohan Rajput';
        $emailId = 'brajmohan.rajput@infogain.com';
        $isImport = 1;

        $this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')
            ->with(self::TOGGLE_FEATURE_KEY)->willReturn(true);

        $this->collectionFactoryMock->expects($this->atLeastOnce())->method('create')
            ->willReturn($this->collectionMock);

        $item = new DataObject(
            [
                'id' => $id,
                'company_id' => $companyId,
                'legacy_catalog_root_folder_id' => $legacyCatalogRootFolderId,
                'shared_catalog_id' => $sharedCatalogId,
                'status' => $status,
                'email_sent' => $emailSent,
                'created_by' => $createdBy,
                'email_id' => $emailId,
                'is_import'=> $isImport
            ]
        );
        $this->collectionMock->addItem($item);
                                    
        $this->collectionMock->expects($this->atLeastOnce())->method('addFieldToFilter')
            ->with('status', ['eq' => $status])
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->once())->method('getSize')->willReturn(1);

        $this->catalogSyncQueueCompleteMock->execute();
    }
    /**
     * Test execute method
     *
     * @return void
     */
    public function testExecuteWithToggle()
    {
        $id = 2;
        $companyId = 2;
        $legacyCatalogRootFolderId = 6;
        $sharedCatalogId = 47;
        $status = 'processing';
        $emailSent = 0;
        $createdBy = 'Brajmohan Rajput';
        $emailId = 'brajmohan.rajput@infogain.com';

        $this->collectionFactoryMock->expects($this->atLeastOnce())->method('create')
            ->willReturn($this->collectionMock);

        $item = new DataObject(
            [
                'id' => $id,
                'company_id' => $companyId,
                'legacy_catalog_root_folder_id' => $legacyCatalogRootFolderId,
                'shared_catalog_id' => $sharedCatalogId,
                'status' => $status,
                'email_sent' => $emailSent,
                'created_by' => $createdBy,
                'email_id' => $emailId
            ]
        );
        $this->collectionMock->addItem($item);
                                    
        $this->collectionMock->expects($this->atLeastOnce())->method('addFieldToFilter')
            ->with('status', ['eq' => $status])
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->once())->method('getSize')->willReturn(1);

        $this->catalogSyncQueueCompleteMock->execute();
    }
}
