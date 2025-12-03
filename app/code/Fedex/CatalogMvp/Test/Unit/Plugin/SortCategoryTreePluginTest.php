<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\Plugin;

use Fedex\CatalogMvp\Plugin\SortCategoryTreePlugin;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Adminhtml\Category\Tree;
use Magento\Catalog\Model\Category;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for SortCategoryTreePlugin.
 */
class SortCategoryTreePluginTest extends TestCase
{
    /**
     * URL key for the B2B root category.
     */
    private const B2B_ROOT_URL_KEY = 'b2b-root-category';

    /**
     * Logger mock.
     *
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * Category repository mock.
     *
     * @var CategoryRepositoryInterface|MockObject
     */
    private $categoryRepository;

    /**
     * JSON serializer mock.
     *
     * @var Json|MockObject
     */
    private $jsonSerializer;

    /**
     * Toggle config mock.
     *
     * @var ToggleConfig|MockObject
     */
    private $toggleConfig;

    /**
     * Category tree block mock.
     *
     * @var Tree|MockObject
     */
    private $treeBlock;

    /**
     * Plugin under test.
     *
     * @var SortCategoryTreePlugin
     */
    private SortCategoryTreePlugin $plugin;

    /**
     * Set up test dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $this->jsonSerializer = $this->createMock(Json::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->treeBlock = $this->createMock(Tree::class);

        $this->plugin = new SortCategoryTreePlugin(
            $this->logger,
            $this->categoryRepository,
            $this->jsonSerializer,
            $this->toggleConfig
        );
    }

    /**
     * Verify that when toggle is disabled, original JSON is returned unchanged.
     *
     * @return void
     */
    public function testAfterGetTreeJsonReturnsOriginalResultWhenToggleIsDisabled(): void
    {
        $originalJson = '{"some":"data"}';

        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('techtitans_D_241651_b2b_categories_alphabetical_sort')
            ->willReturn(false);

        $this->jsonSerializer->expects($this->never())
            ->method('unserialize');

        $result = $this->plugin->afterGetTreeJson($this->treeBlock, $originalJson);
        $this->assertSame(
            $originalJson,
            $result,
            'Result should be unchanged when toggle is disabled.'
        );
        $this->assertJson($result);

        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('some', $decoded);
        $this->assertSame('data', $decoded['some']);
    }


    /**
     * Verify that when unserialized tree is not an array, original JSON is returned unchanged.
     *
     * @return void
     */
    public function testAfterGetTreeJsonReturnsOriginalResultWhenTreeIsNotArray(): void
    {
        $originalJson = '{"invalid":"structure"}';

        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('techtitans_D_241651_b2b_categories_alphabetical_sort')
            ->willReturn(true);

        $this->jsonSerializer->expects($this->once())
            ->method('unserialize')
            ->with($originalJson)
            ->willReturn('not-an-array');

        $result = $this->plugin->afterGetTreeJson($this->treeBlock, $originalJson);
        $this->assertSame(
            $originalJson,
            $result,
            'Result should be unchanged when unserialized tree is not an array.'
        );

        $this->assertJson($result);

        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('invalid', $decoded);
        $this->assertSame('structure', $decoded['invalid']);
    }

    /**
     * Verify that children of B2B root category are sorted alphabetically.
     *
     * @return void
     */
    public function testAfterGetTreeJsonSortsChildrenOfB2bRootCategory(): void
    {
        $originalJson = 'original-json';

        $treeArray = [
            [
                'id' => 1,
                'text' => 'Root',
                'children' => [
                    [
                        'id' => 2,
                        'text' => 'B2B Root Category',
                        'children' => [
                            ['id' => 3, 'text' => 'Zeta', 'children' => []],
                            ['id' => 4, 'text' => 'Alpha', 'children' => []],
                        ],
                    ],
                ],
            ],
        ];

        $sortedTreeArrayExpectation = [
            [
                'id' => 1,
                'text' => 'Root',
                'children' => [
                    [
                        'id' => 2,
                        'text' => 'B2B Root Category',
                        'children' => [
                            ['id' => 4, 'text' => 'Alpha', 'children' => []],
                            ['id' => 3, 'text' => 'Zeta', 'children' => []],
                        ],
                    ],
                ],
            ],
        ];

        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('techtitans_D_241651_b2b_categories_alphabetical_sort')
            ->willReturn(true);

        $this->jsonSerializer->expects($this->once())
            ->method('unserialize')
            ->with($originalJson)
            ->willReturn($treeArray);

        /** @var Category|MockObject $rootCategory */
        $rootCategory = $this->createMock(Category::class);
        $rootCategory->expects($this->once())
            ->method('getUrlKey')
            ->willReturn('root-category');

        /** @var Category|MockObject $b2bRootCategory */
        $b2bRootCategory = $this->createMock(Category::class);
        $b2bRootCategory->expects($this->once())
            ->method('getUrlKey')
            ->willReturn(self::B2B_ROOT_URL_KEY);

        $this->categoryRepository->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive([1], [2])
            ->willReturnOnConsecutiveCalls($rootCategory, $b2bRootCategory);

        $this->jsonSerializer->expects($this->once())
            ->method('serialize')
            ->with($sortedTreeArrayExpectation)
            ->willReturn('sorted-json');

        $result = $this->plugin->afterGetTreeJson($this->treeBlock, $originalJson);

        // Main assertion: JSON string returned
        $this->assertSame('sorted-json', $result, 'Sorted JSON should be returned.');

        $this->assertCount(1, $sortedTreeArrayExpectation);
        $this->assertSame('Root', $sortedTreeArrayExpectation[0]['text']);

        $children = $sortedTreeArrayExpectation[0]['children'];
        $this->assertCount(1, $children);
        $this->assertSame('B2B Root Category', $children[0]['text']);

        $b2bChildren = $children[0]['children'];

        $this->assertContains(
            ['id' => 4, 'text' => 'Alpha', 'children' => []],
            $b2bChildren
        );
        $this->assertContains(
            ['id' => 3, 'text' => 'Zeta', 'children' => []],
            $b2bChildren
        );
        $this->assertSame('Alpha', $b2bChildren[0]['text']);
        $this->assertSame('Zeta', $b2bChildren[1]['text']);
    }

    /**
     * Verify that on exception in afterGetTreeJson, error is logged and original JSON is returned.
     *
     * @return void
     */
    public function testAfterGetTreeJsonLogsErrorAndReturnsOriginalOnException(): void
    {
        $originalJson = '{"some":"data"}';

        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->jsonSerializer->expects($this->once())
            ->method('unserialize')
            ->with($originalJson)
            ->willThrowException(new \RuntimeException('Boom'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Fedex: Error in afterGetTreeJson(): Boom'));

        $result = $this->plugin->afterGetTreeJson($this->treeBlock, $originalJson);

        $this->assertSame(
            $originalJson,
            $result,
            'On exception, original result should be returned.'
        );

        $this->assertJson($result);

        $resultArray = json_decode($result, true);
        $this->assertIsArray($resultArray);
        $this->assertArrayHasKey('some', $resultArray);
        $this->assertSame('data', $resultArray['some']);
    }

    /**
     * Verify that getUrlKeyById logs an error and returns null when repository throws an exception.
     *
     * @return void
     */
    public function testGetUrlKeyByIdReturnsNullAndLogsErrorOnException(): void
    {
        $categoryId = 999;

        $this->categoryRepository->expects($this->once())
            ->method('get')
            ->with($categoryId)
            ->willThrowException(new \Exception('Category load failed'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains(
                    'Fedex: Error loading category ' . $categoryId . ': Category load failed'
                )
            );

        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('getUrlKeyById');
        $method->setAccessible(true);

        /** @var string|null $result */
        $result = $method->invoke($this->plugin, $categoryId);

        $this->assertNull(
            $result,
            'getUrlKeyById() should return null when repository throws an exception.'
        );
    }

    /**
     * Verify that sortCategories sorts siblings and their children recursively.
     *
     * @return void
     */
    public function testSortCategoriesSortsRecursively(): void
    {
        $categories = [
            [
                'id' => 1,
                'text' => 'Parent',
                'children' => [
                    [
                        'id' => 2,
                        'text' => 'Zeta',
                        'children' => [],
                    ],
                    [
                        'id' => 3,
                        'text' => 'Alpha',
                        'children' => [
                            ['id' => 4, 'text' => 'Child Z', 'children' => []],
                            ['id' => 5, 'text' => 'Child A', 'children' => []],
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            [
                'id' => 1,
                'text' => 'Parent',
                'children' => [
                    [
                        'id' => 3,
                        'text' => 'Alpha',
                        'children' => [
                            ['id' => 5, 'text' => 'Child A', 'children' => []],
                            ['id' => 4, 'text' => 'Child Z', 'children' => []],
                        ],
                    ],
                    [
                        'id' => 2,
                        'text' => 'Zeta',
                        'children' => [],
                    ],
                ],
            ],
        ];

        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('sortCategories');
        $method->setAccessible(true);

        /** @var array $result */
        $result = $method->invoke($this->plugin, $categories);

        $this->assertSame($expected, $result);
        $this->assertContains(
            [
                'id' => 1,
                'text' => 'Parent',
                'children' => $result[0]['children'],
            ],
            $result
        );
    }
}
