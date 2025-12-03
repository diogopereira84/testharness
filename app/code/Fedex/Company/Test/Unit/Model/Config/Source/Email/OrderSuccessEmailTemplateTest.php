<?php
namespace Fedex\Company\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Magento\Config\Model\Config\Source\Email\Template;
use Magento\Framework\Option\ArrayInterface;
use Fedex\Company\Model\Config\Source\Email\OrderSuccessEmailTemplate;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class OrderSuccessEmailTemplateTest extends TestCase
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManagerInstance;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $orderSuccessEmailTemplate;
    /**
     * @var Template
     */
    public $emailTemplateProvider;

    protected function setUp(): void
    {

        $this->emailTemplateProvider = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->setMethods(['toOptionArray'])
            ->getMock();

        $this->objectManagerInstance = \Magento\Framework\App\ObjectManager::getInstance();
        $this->objectManager = new ObjectManager($this);
        $this->orderSuccessEmailTemplate = $this->objectManager->getObject(
            OrderSuccessEmailTemplate::class,
            [
                'emailTemplateProvider' => $this->emailTemplateProvider
            ]
        );
    }

    public function testToOptionArray()
    {
        $this->emailTemplateProvider
        ->expects($this->any())
        ->method('toOptionArray')
        ->willReturn([]);
        $this->assertNotNull($this->orderSuccessEmailTemplate->toOptionArray());
    }

}
