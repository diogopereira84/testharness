<?php
namespace Fedex\SelfReg\Test\Unit\Block\Adminhtml\Edit\Button;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Fedex\SelfReg\Block\Adminhtml\Edit\Button\Reset;

class ResetTest extends \PHPUnit\Framework\TestCase
{
    protected $objectManager;
    protected $resetButtonClass;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->resetButtonClass = $this->objectManager->getObject(
            Reset::class,
            [

            ]
        );
    }


    public function testGetButtonData()
    {
        $this->assertNotNull($this->resetButtonClass->getButtonData());
    }
}
