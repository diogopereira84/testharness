<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CategoryLayout\Test\Unit\Model;

use Fedex\CatalogMigration\Helper\CatalogMigrationHelper;
use Fedex\CategoryLayout\Model\CategoryUpdateSubscriber;
use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Fedex\SharedCatalogCustomization\Api\SubscriberInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CategoryUpdateSubscriberTest extends TestCase
{
    protected $messageInterfaceMock;
    /**
     * @var (\Fedex\CategoryLayout\Test\Unit\Model\CategoryProcessor & \PHPUnit\Framework\MockObject\MockObject)
     */
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
     * @var ObjectManager|MockObject
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->messageInterfaceMock = $this->getMockBuilder(MessageInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMessage'])
            ->getMockForAbstractClass();

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
                'getData',
                'setCustomAttributes',
                'getCustomAttributes',
                'save'
            ])->getMock();

        $this->categoryRepositoryInterface = $this
            ->getMockBuilder(CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->subscriberMock = $this->objectManager->getObject(
            CategoryUpdateSubscriber::class,
            [
                'logger'                      => $this->loggerInterfaceMock,
                'categoryFactory'             => $this->categoryFactory,
                'categoryRepositoryInterface' => $this->categoryRepositoryInterface
            ]
        );
    }

    /**
     * test processMessage
     */
    public function testProcessMessage()
    {
        $this->setCategoryData();

        $this->assertNull($this->subscriberMock->processMessage($this->messageInterfaceMock));
    }

    /**
     * test processMessage with exception
     */
    public function testProcessMessageWithException()
    {
        $this->setCategoryData();
        
        $this->categoryRepositoryInterface->expects($this->any())
            ->method('save')->with($this->category)->willThrowException(new \Exception());

        $this->assertNull($this->subscriberMock->processMessage($this->messageInterfaceMock));
    }

    /**
     * setCategoryData 
     */
    public function setCategoryData() {
        $mockData = json_encode([
            'categoryId'        => 20,
            'browseCatalogCatId' => 30
        ]);
        $this->messageInterfaceMock->expects($this->any())->method('getMessage')->willReturn($mockData);
       
        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->category);
        $this->category->expects($this->any())->method('load')->willReturn($this->category);
        $this->category->expects($this->any())->method('getData')->with('is_anchor')->willReturn(true);
        $this->category->expects($this->any())->method('setCustomAttributes')->willReturnSelf();
    }
}
