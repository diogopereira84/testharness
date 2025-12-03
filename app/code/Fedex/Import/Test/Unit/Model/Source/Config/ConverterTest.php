<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Test\Unit\Model\Source\Config;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\Import\Model\Source\Config\Converter;
use Magento\Framework\Config\Converter\Dom;

class ConverterTest extends TestCase
{
    protected $model;
    /**
     * Set up method
     */
    public function setUp():void
    {
        $this->model = new Converter();
    }

    /**
     * Test method for Convert
     *
     * @return void
     */
    public function testConvert()
    {
        $inputData = new \DOMDocument();
        $inputData->load(__DIR__ . '/_files/converter/dom/cdata.xml');
        $expectedResult = require __DIR__ . '/_files/converter/dom/cdata.php';
        $this->assertEquals($expectedResult, $this->model->convert($inputData));
    }
}
