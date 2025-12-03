<?php
/**
 *  XML Renderer allows to format array or object as valid XML document.
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Punchout\Test\Unit\Plugin\Magento\Framework\Webapi\Rest;

use Fedex\Punchout\Plugin\Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class RequestTest extends \PHPUnit\Framework\TestCase
{
    protected $subject;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $requestObj;
    protected function setUp(): void
    {
        $this->subject = $this->getMockBuilder(\Magento\Framework\Webapi\Rest\Request::class)
        ->setMethods(['getRequestUri'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->requestObj = $this->objectManager->getObject(
            Request::class,
            [
            ]
        );
    }
    /**
 * @param \Magento\Framework\Webapi\Rest\Request $subject
 * @param array $result
 * @return array
 */
    public function testafterGetAcceptTypes()
    {

        $this->subject->expects($this->any())
        ->method('getRequestUri')
        ->willReturn(true);

        $this->assertNotNUll($this->requestObj->afterGetAcceptTypes($this->subject, []));
    }
}

