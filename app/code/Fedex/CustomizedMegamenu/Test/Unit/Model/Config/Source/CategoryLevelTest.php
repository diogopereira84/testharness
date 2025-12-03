<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 namespace Fedex\CustomizedMegamenu\Test\Unit\Model\Config\Source;

 use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
 use PHPUnit\Framework\TestCase;
 use Fedex\CustomizedMegamenu\Model\Config\Source\CategoryLevel;

/**
 * Test Class for megamenu CategoryLevel class
 */
class CategoryLevelTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $categoryLevelMock;
    /**
     * Set up method
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->categoryLevelMock = $this->objectManager->getObject(CategoryLevel::class);
    }

    /**
     * Test for toOptionArray method.
     *
     * @return void
     */
    public function testToOptionArray()
    {
        $response = [
            ['value' => '2', 'label' => __('Second Level')],
            ['value' => '3', 'label' => __('Third Level')],
        ];

        $this->assertEquals($response, $this->categoryLevelMock->toOptionArray());
    }
}
