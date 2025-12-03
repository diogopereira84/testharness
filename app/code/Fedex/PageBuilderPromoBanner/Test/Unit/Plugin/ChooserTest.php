<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\PageBuilderPromoBanner\Test\Unit\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\DataObject;
use Fedex\PageBuilderPromoBanner\Plugin\Chooser;
use Fedex\PageBuilderPromoBanner\Test\Unit\Plugin\GetHtml;
use Magento\Cms\Block\Adminhtml\Block\Widget\Chooser as WidgetChooser;

class ChooserTest extends \PHPUnit\Framework\TestCase
{
    protected $chooserMock;
    /**
     * @var ObjectManager|MockObject
     */
    private $objectManager;

    /**
     * @var Chooser|MockObject
     */
    private $chooserObject;

    protected function setUp(): void
    {

        $this->chooserMock = $this->getMockBuilder(WidgetChooser::class)
        ->setMethods()
        ->disableOriginalConstructor()
        ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->chooserObject = $this->objectManager->getObject(
            Chooser::class,
            [
                'chooserMock' => $this->chooserMock
            ]
        );
    }

    /**
     * Test afterPrepareElementHtml
     *
     * @return void
     */
    public function testAfterPrepareElementHtml()
    {
        $arrCustomer = new GetHtml();
        $this->assertEquals(
            $arrCustomer,
            $this->chooserObject->afterPrepareElementHtml($this->chooserMock, $arrCustomer)
        );
    }
}
