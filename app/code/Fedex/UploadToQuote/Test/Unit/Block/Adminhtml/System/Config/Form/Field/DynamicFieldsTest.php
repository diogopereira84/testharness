<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Block\Adminhtml\System\Config\Form\Field;

use PHPUnit\Framework\TestCase;
use Fedex\UploadToQuote\Block\Adminhtml\System\Config\Form\Field\DynamicFields;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * DynamicFieldsTest unit test class
 */
class DynamicFieldsTest extends TestCase
{
    /**
     * @var ObjectManager $objectManager
     */
    private $objectManager;

    /**
     * @var DynamicFields $dynamicFields
     */
    private $dynamicFields;

    /**
     * Set up method.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->dynamicFields = $this->objectManager->getObject(DynamicFields::class);
    }

    /**
     * Test testPrepareToRender
     *
     * @return void
     */
    public function testPrepareToRender()
    {
        $reflectionClass = new \ReflectionClass(DynamicFields::class);
        $method = $reflectionClass->getMethod('_prepareToRender');
        $method->invoke($this->dynamicFields);
        $columnData = $this->dynamicFields->getColumnsForTesting();
        
        $this->assertArrayHasKey('text_field', $columnData);
        $this->assertEquals('Reason for Declining', $columnData['text_field']['label']);
        $this->assertArrayHasKey('number_field', $columnData);
        $this->assertEquals('Sort Order', $columnData['number_field']['label']);
    }
}
