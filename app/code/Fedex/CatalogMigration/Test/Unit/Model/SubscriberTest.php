<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CatalogMigration\Test\Unit\Model;

use Fedex\CatalogMigration\Helper\CatalogMigrationHelper;
use Fedex\CatalogMigration\Model\Subscriber;
use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Fedex\SharedCatalogCustomization\Api\SubscriberInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SubscriberTest extends TestCase
{
    protected $messageInterfaceMock;
    protected $toggleConfigMock;
    protected $categoryProcessorMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerInterfaceMock;
    protected $categoryFactory;
    protected $category;
    protected $categoryRepositoryInterface;
    protected $subscriberMock;
    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /**
     * @var SubscriberInterface|MockObject
     */
    protected $subscriberInterfaceMock;

    /**
     * @var CatalogMigrationHelper|MockObject
     */
    private $catalogMigrationHelperMock;

    /**
     * @var ObjectManager|MockObject
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->messageInterfaceMock = $this->getMockBuilder(MessageInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMessage'])
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogMigrationHelperMock = $this->getMockBuilder(CatalogMigrationHelper::class)
            ->setMethods(['updateCatalogMigrationQueueStatus', 'createProductCreateUpdateQueue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryProcessorMock = $this->getMockBuilder(CategoryProcessor::class)
            ->setMethods(['upsertCategories'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerInterfaceMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['error'])
            ->getMockForAbstractClass();

        $this->categoryFactory = $this
            ->getMockBuilder(CategoryFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'load',
                'setCustomAttributes',
                'save'
            ])->getMock();

        $this->categoryRepositoryInterface = $this
            ->getMockBuilder(CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->subscriberMock = $this->objectManager->getObject(
            Subscriber::class,
            [
                'catalogMigrationHelper'      => $this->catalogMigrationHelperMock,
                'categoryProcessor'           => $this->categoryProcessorMock,
                'logger'                      => $this->loggerInterfaceMock,
                'categoryFactory'             => $this->categoryFactory,
                'toggleConfig'                => $this->toggleConfigMock,
                'categoryRepositoryInterface' => $this->categoryRepositoryInterface
            ]
        );
    }

    /**
     * test processMessage
     */
    public function testProcessMessage()
    {
        $mockData = json_encode([
            'lastMigrationProcessId' => '123',
            'category_path' => 'category_path_data',
        ]);
        $this->messageInterfaceMock->expects($this->any())->method('getMessage')->willReturn($mockData);

        $this->catalogMigrationHelperMock->expects($this->any())
            ->method('updateCatalogMigrationQueueStatus')
            ->withConsecutive(
                ['123', static::STATUS_PROCESSING],
                ['123', static::STATUS_FAILED]
            );

        $this->categoryProcessorMock->expects($this->once())
            ->method('upsertCategories')
            ->with('category_path_data', ',');

        $this->assertNull($this->subscriberMock->processMessage($this->messageInterfaceMock));
    }

    /**
     * test updateCategory
     */
    public function testUpdateCategory()
    {
        $categoryIds = [0 => 12];
        $lastMigrationProcessId = 1;
        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->category);
        $this->category->expects($this->any())->method('load')->willReturn($this->category);
        $this->category->expects($this->any())->method('setCustomAttributes')->willReturnSelf();
        $this->categoryRepositoryInterface->expects($this->any())
            ->method('save')->with($this->category)->willReturn($this->category);
        $this->assertNull($this->subscriberMock->updateCategory($categoryIds, $lastMigrationProcessId));
    }
}
