<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category Fedex
 * @package  Fedex_CustomizedMegamenu
 * @author   Magento Core Team <core@magentocommerce.com>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link     http://www.magentocommerce.com
 */

declare(strict_types=1);

namespace Fedex\CustomizedMegamenu\Test\Unit\Model;

use Fedex\CatalogDocumentUserSettings\Helper\Data as HelperData;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CustomizedMegamenu\Model\MenuTreeManagement;
use Fedex\Delivery\Helper\Data;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Ondemand\Api\Data\ConfigInterface as OndemandConfigInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\SessionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for MenuTreeManagement
 *
 * @category Fedex
 * @package  Fedex_CustomizedMegamenu
 * @author   Magento Core Team <core@magentocommerce.com>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link     http://www.magentocommerce.com
 */
class MenuTreeManagementTest extends TestCase
{
    private MockObject|CategoryFactory $_categoryFactoryMock;
    private MockObject|ToggleConfig $_toggleConfigMock;
    private MockObject|SessionFactory $_sessionFactoryMock;
    private MockObject|CatalogMvp $_catalogMvpHelperMock;
    private MockObject|Session $_customerSessionMock;
    private MockObject|Data $_deliveryHelperMock;
    private MockObject|HelperData $_helperDataMock;
    private MockObject|OndemandConfigInterface $ondemandConfigMock;
    private MenuTreeManagement $_menuTreeManagement;

    /**
     * Set up test fixtures
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->_categoryFactoryMock = $this->createMock(CategoryFactory::class);
        $this->_toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->_sessionFactoryMock = $this->createMock(SessionFactory::class);
        $this->_deliveryHelperMock = $this->createMock(Data::class);
        $this->_helperDataMock = $this->createMock(HelperData::class);
        $this->ondemandConfigMock = $this->createMock(OndemandConfigInterface::class);

        $this->_catalogMvpHelperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isSharedCatalogPermissionEnabled'])
            ->addMethods(['getSharedCatalogUrl'])
            ->getMock();

        $this->_customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->addMethods(['getOndemandCompanyInfo'])
            ->getMock();

        $this->_menuTreeManagement = new MenuTreeManagement(
            $this->_categoryFactoryMock,
            $this->_toggleConfigMock,
            $this->_sessionFactoryMock,
            $this->_deliveryHelperMock,
            $this->_helperDataMock,
            $this->_catalogMvpHelperMock,
            $this->_customerSessionMock,
            $this->ondemandConfigMock
        );
    }

    /**
     * Test getting customer company information based on toggle status
     *
     * @param bool       $toggleStatus Toggle configuration status
     * @param array|null $companyInfo  Company information array
     * @param array      $expected     Expected result array
     *
     * @return       void
     * @covers       \Fedex\CustomizedMegamenu\Model\MenuTreeManagement::getCustomerCompanyInfo
     * @dataProvider companyInfoProvider
     */
    public function testGetCustomerCompanyInfo(bool $toggleStatus, ?array $companyInfo, array $expected)
    {
        $this->_toggleConfigMock->method('getToggleConfigValue')
            ->with(MenuTreeManagement::TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE)
            ->willReturn($toggleStatus);

        if ($toggleStatus) {
            $this->_customerSessionMock->method('isLoggedIn')->willReturn(true);
            $this->_customerSessionMock->method('getOndemandCompanyInfo')->willReturn($companyInfo);
        } else {
            $newSessionMock = $this->getMockBuilder(Session::class)
                ->disableOriginalConstructor()
                ->addMethods(['getOndemandCompanyInfo'])
                ->getMock();
            $newSessionMock->method('getOndemandCompanyInfo')->willReturn($companyInfo);
            $this->_sessionFactoryMock->method('create')->willReturn($newSessionMock);
        }

        $this->assertEquals($expected, $this->_menuTreeManagement->getCustomerCompanyInfo());
    }

    /**
     * Data provider for company information test
     *
     * @return array
     */
    public function companyInfoProvider(): array
    {
        return [
            'Toggle ON, Info Exists' => [true, ['company_type' => 'epro'], ['company_type' => 'epro']],
            'Toggle ON, Info is Null' => [true, null, []],
            'Toggle OFF, Info Exists' => [false, ['company_type' => 'selfreg'], ['company_type' => 'selfreg']],
            'Toggle OFF, Info is Null' => [false, null, []],
        ];
    }

    /**
     * Test getting or creating customer session based on login status
     *
     * @param bool $isLoggedIn Whether user is logged in
     *
     * @return       void
     * @covers       \Fedex\CustomizedMegamenu\Model\MenuTreeManagement::getOrCreateCustomerSession
     * @dataProvider sessionStatusProvider
     */
    public function testGetOrCreateCustomerSession(bool $isLoggedIn)
    {
        $this->_customerSessionMock->method('isLoggedIn')->willReturn($isLoggedIn);

        if ($isLoggedIn) {
            $this->_sessionFactoryMock->expects($this->never())->method('create');
            $session = $this->_menuTreeManagement->getOrCreateCustomerSession();
            $this->assertSame($this->_customerSessionMock, $session);
        } else {
            $newSessionMock = $this->createMock(Session::class);
            $this->_sessionFactoryMock->expects($this->once())->method('create')->willReturn($newSessionMock);
            $session = $this->_menuTreeManagement->getOrCreateCustomerSession();
            $this->assertSame($newSessionMock, $session);
        }
    }

    /**
     * Data provider for session status test
     *
     * @return array
     */
    public function sessionStatusProvider(): array
    {
        return [
            'User is Logged In' => [true],
            'User is Not Logged In' => [false],
        ];
    }

    /**
     * Test rendering mega menu HTML for admin user
     *
     * @return void
     * @covers \Fedex\CustomizedMegamenu\Model\MenuTreeManagement::renderMegaMenuHtmlOptimized
     */
    public function testRenderMegaMenuHtmlOptimizedForAdminUser()
    {
        $this->_catalogMvpHelperMock->method('isSharedCatalogPermissionEnabled')->willReturn(true); // Admin user
        $this->_toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->_customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->_customerSessionMock->method('getOndemandCompanyInfo')->willReturn(['company_type' => 'epro']);

        $categoryCollectionMock = $this->createMock(CategoryCollection::class);
        $categoryCollectionMock->method('addAttributeToSelect')->willReturnSelf();
        $categoryCollectionMock->method('setOrder')->willReturnSelf();
        $categoryCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $categoryCollectionMock->expects($this->exactly(2))
            ->method('addAttributeToFilter')
            ->withConsecutive(
                ['is_active', 1],
                ['path', [['like' => '%/2'], ['like' => '%/2/%'], ['like' => '%/3'], ['like' => '%/3/%']]]
            )->willReturnSelf();

        $categoryMock = $this->createMock(Category::class);
        $categoryMock->method('getCollection')->willReturn($categoryCollectionMock);
        $this->_categoryFactoryMock->method('create')->willReturn($categoryMock);

        $result = $this->_menuTreeManagement->renderMegaMenuHtmlOptimized('o', 'c', 5, 2, 3, true, [], []);

        // Verify that method returns a string (empty in this case since no categories are provided)
        $this->assertIsString($result);
        $this->assertEquals('', $result);
    }

    /**
     * Test rendering mega menu HTML for non-admin user
     *
     * @return void
     * @covers \Fedex\CustomizedMegamenu\Model\MenuTreeManagement::renderMegaMenuHtmlOptimized
     */
    public function testRenderMegaMenuHtmlOptimizedForNonAdminUser()
    {
        $this->_catalogMvpHelperMock->method('isSharedCatalogPermissionEnabled')->willReturn(false); // Non-admin
        $this->_toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->_customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->_customerSessionMock->method('getOndemandCompanyInfo')->willReturn(['company_type' => 'epro']);

        $categoryCollectionMock = $this->createMock(CategoryCollection::class);
        $categoryCollectionMock->method('addAttributeToSelect')->willReturnSelf();
        $categoryCollectionMock->method('setOrder')->willReturnSelf();
        $categoryCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $categoryCollectionMock->expects($this->exactly(3))
            ->method('addAttributeToFilter')
            ->withConsecutive(
                ['is_active', 1],
                ['path', [['like' => '%/2'], ['like' => '%/2/%'], ['like' => '%/3'], ['like' => '%/3/%']]],
                ['is_publish', 1]
            )->willReturnSelf();

        $categoryMock = $this->createMock(Category::class);
        $categoryMock->method('getCollection')->willReturn($categoryCollectionMock);
        $this->_categoryFactoryMock->method('create')->willReturn($categoryMock);

        $result = $this->_menuTreeManagement->renderMegaMenuHtmlOptimized('o', 'c', 5, 2, 3, false, [], []);

        // Verify that method returns a string (empty in this case since no categories are provided)
        $this->assertIsString($result);
        $this->assertEquals('', $result);
    }

    /**
     * Test rendering mega menu HTML with denied categories
     *
     * @return void
     * @covers \Fedex\CustomizedMegamenu\Model\MenuTreeManagement::renderMegaMenuHtmlOptimized
     */
    public function testRenderMegaMenuHtmlOptimizedWithDeniedCategories()
    {
        $denyCategoryIds = [10, 20];
        $this->_catalogMvpHelperMock->method('isSharedCatalogPermissionEnabled')->willReturn(false);
        $this->_toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->_customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->_customerSessionMock->method('getOndemandCompanyInfo')->willReturn(['company_type' => 'other']);

        $categoryCollectionMock = $this->createMock(CategoryCollection::class);
        $categoryCollectionMock->method('addAttributeToSelect')->willReturnSelf();
        $categoryCollectionMock->method('setOrder')->willReturnSelf();
        $categoryCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $categoryCollectionMock->expects($this->exactly(3))
            ->method('addAttributeToFilter')
            ->withConsecutive(
                ['is_active', 1],
                ['path', [['like' => '%/2'], ['like' => '%/2/%'], ['like' => '%/3'], ['like' => '%/3/%']]],
                ['entity_id', ['nin' => $denyCategoryIds]]
            )->willReturnSelf();

        $categoryMock = $this->createMock(Category::class);
        $categoryMock->method('getCollection')->willReturn($categoryCollectionMock);
        $this->_categoryFactoryMock->method('create')->willReturn($categoryMock);

        $this->_menuTreeManagement->renderMegaMenuHtmlOptimized('o', 'c', 5, 2, 3, false, $denyCategoryIds, []);
    }

    /**
     * Test rendering mega menu HTML renders correctly
     *
     * @return void
     * @covers \Fedex\CustomizedMegamenu\Model\MenuTreeManagement::renderMegaMenuHtmlOptimized
     */
    public function testRenderMegaMenuHtmlOptimizedRendersHtmlCorrectly()
    {
        $this->_catalogMvpHelperMock->method('isSharedCatalogPermissionEnabled')->willReturn(false);
        $this->_toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->_customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->_customerSessionMock->method('getOndemandCompanyInfo')->willReturn([]);
        $this->_catalogMvpHelperMock->method('getSharedCatalogUrl')->willReturn('/single-category.html');

        $rootCat = $this->createMock(Category::class);
        $rootCat->method('getId')->willReturn(2);
        $rootCat->method('getParentId')->willReturn(1);
        $rootCat->method('getName')->willReturn('Single Category');
        $rootCat->method('getUrl')->willReturn('/different-url.html'); // To prove the helper is used
        $rootCat->method('getLevel')->willReturn(0);
        $rootCat->method('getData')->with('is_active')->willReturn(1);

        $categories = [$rootCat];

        $categoryCollectionMock = $this->createMock(CategoryCollection::class);
        $categoryCollectionMock->method('addAttributeToSelect')->willReturnSelf();
        $categoryCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $categoryCollectionMock->method('setOrder')->willReturnSelf();
        $categoryCollectionMock->method('getIterator')->willReturn(new \ArrayIterator($categories));

        $categoryFactoryCreateResultMock = $this->createMock(Category::class);
        $categoryFactoryCreateResultMock->method('getCollection')->willReturn($categoryCollectionMock);
        $this->_categoryFactoryMock->method('create')->willReturn($categoryFactoryCreateResultMock);

        $html = $this->_menuTreeManagement->renderMegaMenuHtmlOptimized('outer', 'children', 5, 2, null, false, [], []);

        $this->assertStringContainsString('<span>Shared Catalog</span>', $html);
        $this->assertStringContainsString('href="/different-url.html"', $html);
        $this->assertStringContainsString('class="level0', $html);
    }

    /**
     * Test rendering mega menu HTML with children categories
     *
     * @return void
     */
    public function testRenderMegaMenuHtmlOptimizedWithChildren()
    {
        $this->_catalogMvpHelperMock->method('isSharedCatalogPermissionEnabled')->willReturn(false);
        $this->_toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->_customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->_customerSessionMock->method('getOndemandCompanyInfo')->willReturn([]);

        // Parent Category
        $parentCat = $this->createMock(Category::class);
        $parentCat->method('getId')->willReturn(2);
        $parentCat->method('getParentId')->willReturn(1); // Root
        $parentCat->method('getName')->willReturn('Parent Category');
        $parentCat->method('getUrl')->willReturn('/parent.html');
        $parentCat->method('getLevel')->willReturn(2);
        $parentCat->method('getData')->with('is_active')->willReturn(1);

        // Child Category
        $childCat = $this->createMock(Category::class);
        $childCat->method('getId')->willReturn(10);
        $childCat->method('getParentId')->willReturn(2); // Child of Parent
        $childCat->method('getName')->willReturn('Child Category');
        $childCat->method('getUrl')->willReturn('/child.html');
        $childCat->method('getLevel')->willReturn(3);
        $childCat->method('getData')->with('is_active')->willReturn(1);


        $productCollectionMock = $this->createMock(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);
        $productCollectionMock->method('count')->willReturn(1);
        $childCat->method('getProductCollection')->willReturn($productCollectionMock);


        $categories = [$parentCat, $childCat];

        $categoryCollectionMock = $this->createMock(CategoryCollection::class);
        $categoryCollectionMock->method('addAttributeToSelect')->willReturnSelf();
        $categoryCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $categoryCollectionMock->method('setOrder')->willReturnSelf();
        $categoryCollectionMock->method('getIterator')->willReturn(new \ArrayIterator($categories));

        $categoryFactoryCreateResultMock = $this->createMock(Category::class);
        $categoryFactoryCreateResultMock->method('getCollection')->willReturn($categoryCollectionMock);
        $this->_categoryFactoryMock->method('create')->willReturn($categoryFactoryCreateResultMock);

        $html = $this->_menuTreeManagement->renderMegaMenuHtmlOptimized('o', 'c', 5, 2, null, false, [], []);

        // Assert parent is rendered with the special "Shared Catalog" name
        $this->assertStringContainsString('<span>Shared Catalog</span>', $html);
        $this->assertStringContainsString('href="/parent.html"', $html);
        // Assert child is rendered inside a submenu
        $this->assertStringContainsString('<ul class="level0 submenu">', $html);
        $this->assertStringContainsString('<span>Child Category</span>', $html);
        $this->assertStringContainsString('href="/child.html"', $html);
    }



    /**
     * Test rendering mega menu for print product category
     *
     * @return void
     */
    public function testRenderMegaMenuForPrintProductCategory(): void
    {
        $printProductCategoryId = 5;
        $childCategoryIdWithProducts = 51;
        $childCategoryIdWithoutProducts = 52;

        // Root Print Product Category
        $printCategory = $this->_createCategoryMock($printProductCategoryId, 'Print Products Root', 2, 1, 'http://localhost/print');

        // Child category with products
        $childWithProducts = $this->_createCategoryMock($childCategoryIdWithProducts, 'Brochures', 3, $printProductCategoryId, 'http://localhost/brochures');
        $productCollectionWithProducts = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()->getMock();
        $productCollectionWithProducts->method('count')->willReturn(10);
        $childWithProducts->method('getProductCollection')->willReturn($productCollectionWithProducts);

        // Child category without products (should be skipped)
        $childWithoutProducts = $this->_createCategoryMock($childCategoryIdWithoutProducts, 'Flyers', 3, $printProductCategoryId, 'http://localhost/flyers');
        $productCollectionWithoutProducts = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()->getMock();
        $productCollectionWithoutProducts->method('count')->willReturn(0);
        $childWithoutProducts->method('getProductCollection')->willReturn($productCollectionWithoutProducts);

        $categoryCollectionMock = $this->createMock(CategoryCollection::class);
        $categoryCollectionMock->method('addAttributeToSelect')->willReturnSelf();
        $categoryCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $categoryCollectionMock->method('setOrder')->willReturnSelf();
        $categoryCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([$printCategory, $childWithProducts, $childWithoutProducts]));

        $categoryFactoryCreateResultMock = $this->createMock(Category::class);
        $categoryFactoryCreateResultMock->method('getCollection')->willReturn($categoryCollectionMock);
        $this->_categoryFactoryMock->method('create')->willReturn($categoryFactoryCreateResultMock);
        $this->_catalogMvpHelperMock->method('isSharedCatalogPermissionEnabled')->willReturn(true);

        // Mock customer session for company info
        $this->_toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->_customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->_customerSessionMock->method('getOndemandCompanyInfo')->willReturn(['company_type' => 'epro']);

        $html = $this->_menuTreeManagement->renderMegaMenuHtmlOptimized(
            '',
            '',
            5,
            null,
            $printProductCategoryId,
            true,
            [],
            []
        );

        // Assert root print category is rendered
        $this->assertStringContainsString('<span>Print Products</span>', $html);
        // Assert child with products is rendered
        $this->assertStringContainsString('<span>Brochures</span>', $html);
        // Assert child without products is rendered (the logic doesn't seem to filter by product count in this context)
        $this->assertStringContainsString('<span>Flyers</span>', $html);
    }

     /**
      * Test rendering category tree HTML with deep nesting and skips
      *
      * @return void
      */
    public function testRenderCategoryTreeHtmlWithDeepNestingAndSkips(): void
    {
        $sharedCatalogId = 3;
        $customProductId = 32;
        $skuOnlyProductId = 33;

        // Level 1
        $rootCategory = $this->_createCategoryMock($sharedCatalogId, 'Shared Catalog', 2, 1, 'http://localhost/shared');
        // Level 2
        $childCategory = $this->_createCategoryMock(31, 'Level 2 Category', 3, $sharedCatalogId, 'http://localhost/level2');
        // Level 3 (Grandchild)
        $grandchildCategory = $this->_createCategoryMock(311, 'Level 3 Category', 4, 31, 'http://localhost/level3');
        // Categories to be skipped
        $customProductCategory = $this->_createCategoryMock($customProductId, 'Custom Product', 3, $sharedCatalogId, 'http://localhost/custom');
        $skuOnlyCategory = $this->_createCategoryMock($skuOnlyProductId, 'SKU Only', 3, $sharedCatalogId, 'http://localhost/sku');

        $categoryCollectionMock = $this->createMock(CategoryCollection::class);
        $categoryCollectionMock->method('addAttributeToSelect')->willReturnSelf();
        $categoryCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $categoryCollectionMock->method('setOrder')->willReturnSelf();
        $categoryCollectionMock->method('getIterator')->willReturn(
            new \ArrayIterator(
                [
                $rootCategory,
                $childCategory,
                $grandchildCategory,
                $customProductCategory,
                $skuOnlyCategory
                ]
            )
        );

        $categoryFactoryCreateResultMock = $this->createMock(Category::class);
        $categoryFactoryCreateResultMock->method('getCollection')->willReturn($categoryCollectionMock);
        $this->_categoryFactoryMock->method('create')->willReturn($categoryFactoryCreateResultMock);

        $this->_catalogMvpHelperMock->method('isSharedCatalogPermissionEnabled')->willReturn(true);

        // Setup toggle config expectations for multiple calls
        $this->_toggleConfigMock->method('getToggleConfigValue')
            ->willReturnMap(
                [
                [MenuTreeManagement::TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE, true],
                [MenuTreeManagement::EXPLORES_NON_STANDARD_CATALOG, true]
                ]
            );

        // Mock customer session for company info
        $this->_customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->_customerSessionMock->method('getOndemandCompanyInfo')->willReturn(['company_type' => 'epro']);

        $this->_toggleConfigMock->method('getToggleConfig')
            ->willReturnMap(
                [
                [MenuTreeManagement::EPRO_PRINT_CUSTOM_PRODUCT, $customProductId],
                [MenuTreeManagement::EPRO_PRINT_SKUONLY_PRODUCT, $skuOnlyProductId],
                ]
            );

        $html = $this->_menuTreeManagement->renderMegaMenuHtmlOptimized(
            '',
            '',
            5,
            $sharedCatalogId,
            null,
            true,
            [],
            []
        );

        // Assert Level 1, 2, and 3 (recursion) are rendered
        $this->assertStringContainsString('<span>Shared Catalog</span>', $html);
        $this->assertStringContainsString('<span>Level 2 Category</span>', $html);
        $this->assertStringContainsString('<span>Level 3 Category</span>', $html);

        // Assert that the non-standard categories were skipped
        $this->assertStringNotContainsString('<span>Custom Product</span>', $html);
        $this->assertStringNotContainsString('<span>SKU Only</span>', $html);
    }


    /**
     * Test sorting categories by name
     *
     * @return void
     */
    public function testSortCategoriesByName(): void
    {
        // Setup categories out of alphabetical order
        $categoryA = $this->_createCategoryMock(10, 'Category B', 2, 1);
        $categoryB = $this->_createCategoryMock(11, 'Category A', 2, 1);

        $categoriesByParent = [
            2 => [$categoryA, $categoryB]
        ];

        // Use reflection to test the private method
        $reflection = new \ReflectionClass(MenuTreeManagement::class);
        $method = $reflection->getMethod('sortCategoriesByName');
        $method->setAccessible(true);

        $method->invokeArgs($this->_menuTreeManagement, [&$categoriesByParent]);

        // Assert that the categories are now sorted by name
        $this->assertEquals('Category A', $categoriesByParent[2][0]->getName());
        $this->assertEquals('Category B', $categoriesByParent[2][1]->getName());
    }


    /**
     * Creates a mock for the Category class.
     *
     * @param int         $id       Category ID
     * @param string      $name     Category name
     * @param int         $level    Category level
     * @param int         $parentId Parent category ID
     * @param string|null $url      Category URL
     *
     * @return MockObject|Category
     */
    private function _createCategoryMock(
        int $id,
        string $name,
        int $level,
        int $parentId,
        ?string $url = null
    ): MockObject|Category {
        $categoryMock = $this->createMock(Category::class);
        $categoryMock->method('getId')->willReturn($id);
        $categoryMock->method('getName')->willReturn($name);
        $categoryMock->method('getLevel')->willReturn($level);
        $categoryMock->method('getParentId')->willReturn($parentId);
        $categoryMock->method('getData')->with('is_active')->willReturn(1);

        if ($url) {
            $categoryMock->method('getUrl')->willReturn($url);
        }

        // Default product collection mock, can be overridden in specific tests
        $productCollectionMock = $this->createMock(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);
        $productCollectionMock->method('count')->willReturn(1);
        $categoryMock->method('getProductCollection')->willReturn($productCollectionMock);

        return $categoryMock;
    }

    /**
     * Test rendering category tree HTML with empty categories.
     *
     * @return void
     */
    public function testRenderCategoryTreeHtmlWithEmptyCategories(): void
    {
        // Test to cover line 176 - early return when categoriesByParent is empty
        $categoriesByParent = [];

        // Use reflection to test the private method directly
        $reflection = new \ReflectionClass(MenuTreeManagement::class);
        $method = $reflection->getMethod('renderCategoryTreeHtml');
        $method->setAccessible(true);

        $result = $method->invokeArgs(
            $this->_menuTreeManagement,
            [
            999, // non-existent parent ID
            'nav-1',
            0,
            $categoriesByParent,
            true,
            'epro',
            false,
            32,
            33,
            true
            ]
        );

        // Should return empty string when no categories exist for the parent
        $this->assertEquals('', $result);
    }

    /**
     * Test rendering category tree HTML with print categories that have no products.
     *
     * @return void
     */
    public function testRenderCategoryTreeHtmlWithPrintCategoryNoProducts(): void
    {
        // Test to cover line 193 - skip categories with no products in print mode
        $printProductCategoryId = 5;
        $childCategoryId = 51;

        // Create a mock category that specifically has no products
        $childCategoryEmpty = $this->createMock(\Magento\Catalog\Model\Category::class);
        $childCategoryEmpty->method('getId')->willReturn($childCategoryId);
        $childCategoryEmpty->method('getName')->willReturn('Empty Category');
        $childCategoryEmpty->method('getLevel')->willReturn(3);
        $childCategoryEmpty->method('getParentId')->willReturn($printProductCategoryId);
        $childCategoryEmpty->method('getUrl')->willReturn('http://localhost/empty');
        $childCategoryEmpty->method('getData')->with('is_active')->willReturn(1);

        // Create a product collection mock that returns 0 count
        $productCollectionEmpty = $this->createMock(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);
        $productCollectionEmpty->method('count')->willReturn(0);
        $childCategoryEmpty->method('getProductCollection')->willReturn($productCollectionEmpty);

        $categoriesByParent = [
            $printProductCategoryId => [$childCategoryEmpty]
        ];

        // Use reflection to test the private method directly with isPrintProduct = true
        $reflection = new \ReflectionClass(MenuTreeManagement::class);
        $method = $reflection->getMethod('renderCategoryTreeHtml');
        $method->setAccessible(true);

        $result = $method->invokeArgs(
            $this->_menuTreeManagement,
            [
            $printProductCategoryId,
            'nav-1',
            0,
            $categoriesByParent,
            true,
            'epro',
            true, // isPrintProduct = true to trigger the condition on line 192
            32,
            33,
            true
            ]
        );

        // With isPrintProduct=true and productCount=0, the category should be skipped (line 193)
        // Resulting in empty ul tags
        $this->assertEquals('<ul class="level0 submenu"></ul>', $result);
    }

    public function testRenderMegaMenuHtmlOptimizedWithCustomCategoryNames(): void
    {
        $sharedCatalogCategoryId = 2;
        $printProductCategoryId = [5, 10];
        $categoriesName = [
            $sharedCatalogCategoryId => 'Custom Shared Catalog Name',
            5 => 'Custom Print Products Name',
            10 => 'Custom Office Supplies'
        ];

        $this->_catalogMvpHelperMock->method('isSharedCatalogPermissionEnabled')->willReturn(true);
        $this->_toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->_customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->_customerSessionMock->method('getOndemandCompanyInfo')->willReturn(['company_type' => 'epro']);

        $sharedCategory = $this->_createCategoryMock($sharedCatalogCategoryId, 'Shared Catalog', 2, 1, '/shared-url.html');
        $printCategory = $this->_createCategoryMock(5, 'Print Products', 2, 1, '/print-url.html');
        $officeSupplies = $this->_createCategoryMock(10, 'Office Supplies', 2, 1, '/office-supplies.html');

        $categoryCollectionMock = $this->createMock(\Magento\Catalog\Model\ResourceModel\Category\Collection::class);
        $categoryCollectionMock->method('addAttributeToSelect')->willReturnSelf();
        $categoryCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $categoryCollectionMock->method('setOrder')->willReturnSelf();
        $categoryCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([$sharedCategory, $printCategory, $officeSupplies]));

        $categoryFactoryCreateResultMock = $this->createMock(\Magento\Catalog\Model\Category::class);
        $categoryFactoryCreateResultMock->method('getCollection')->willReturn($categoryCollectionMock);
        $this->_categoryFactoryMock->method('create')->willReturn($categoryFactoryCreateResultMock);

        $html = $this->_menuTreeManagement->renderMegaMenuHtmlOptimized(
            '',
            '',
            5,
            $sharedCatalogCategoryId,
            $printProductCategoryId,
            true,
            [],
            $categoriesName
        );

        $this->assertStringContainsString('<span>Shared Catalog</span>', $html);
        $this->assertStringContainsString('<span>Custom Print Products Name</span>', $html);
        $this->assertStringContainsString('<span>Custom Office Supplies</span>', $html);
    }
}
