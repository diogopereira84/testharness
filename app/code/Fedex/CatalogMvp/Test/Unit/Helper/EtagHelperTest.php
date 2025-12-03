<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\Helper;

use Fedex\CatalogMvp\Helper\EtagHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Catalog\Model\Category;

class EtagHelperTest extends TestCase
{
    /**
     * @var Category
     */
    private $category;

    /**
     * @var EtagHelper
     */
    private $etagHelper;

    /**
     * Setup method
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);

        $this->category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData', 'getId'])
            ->getMock();

        $this->etagHelper = $objectManagerHelper->getObject(
            EtagHelper::class,
            []
        );
    }

    /**
     * Test case for generateEtag
     *
     * @return void
     */
    public function testGenerateEtag(): void
    {
        $categoryName = 'Test Category';
        $categoryId = 123;

        $this->category->expects($this->any())
            ->method('getData')
            ->with('name')
            ->willReturn($categoryName);

        $this->category->expects($this->any())
            ->method('getId')
            ->willReturn($categoryId);

        $etag = $this->etagHelper->generateEtag($this->category);

        $this->assertIsString($etag);
        $this->assertEquals(32, strlen($etag));
    }
}
