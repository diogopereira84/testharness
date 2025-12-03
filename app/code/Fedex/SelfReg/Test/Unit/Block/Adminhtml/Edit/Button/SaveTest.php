<?php
namespace Fedex\SelfReg\Test\Unit\Block\Adminhtml\Edit\Button;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Fedex\SelfReg\Block\Adminhtml\Edit\Button\Save;

class SaveTest extends \PHPUnit\Framework\TestCase
{
    protected $objectManager;
    protected $saveButtonClass;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->saveButtonClass = $this->objectManager->getObject(
            Save::class,
            [

            ]
        );
    }


    public function testGetButtonData()
    {
        $this->assertNotNull($this->saveButtonClass->getButtonData());
    }
}
