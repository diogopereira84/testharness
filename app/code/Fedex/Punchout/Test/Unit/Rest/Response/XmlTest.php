<?php
/**
 *  XML Renderer allows to format array or object as valid XML document.
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Punchout\Test\Unit\Rest\Response;

use Fedex\Punchout\Rest\Response\Xml;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Renders response data in Xml format.
 */
class XmlTest extends \PHPUnit\Framework\TestCase
{
    
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $XmlObj;
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->XmlObj = $this->objectManager->getObject(
            Xml::class,
            [
            ]
        );
    }
    public function testgetMimeType()
    {
        $this->assertNotNull($this->XmlObj->getMimeType());
    }
    
    /**
     * Format object|array to valid XML.
     *
     * @param object|array|int|string|bool|float|null $data
     * @return string
     */
    public function testRender()
    {
        $arrData = ["foo" => "bar"];
        $this->assertNotNull($this->XmlObj->render($arrData));
    }

    
}

