<?php

namespace Fedex\Shipment\Test\Unit\Controller\Adminhtml\shipment;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Fedex\Shipment\Controller\Adminhtml\shipment\NewAction;

/**
 * Test class for Fedex\Shipment\Controller\Adminhtml\shipment\NewAction
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class NewActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManager|MockObject */
    protected $objectManagerHelper;

    /** @var Context|MockObject */
    protected $context;

    /** @var ForwardFactory|MockObject */
    protected $forwardFactory;

    /** @var NewAction|MockObject */
    protected $newAction;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->context = $this->createMock(Context::class);

        $this->forwardFactory = $this->getMockBuilder(ForwardFactory::class)
        ->setMethods(['create','forward'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->newAction = $this->objectManagerHelper->getObject(
            NewAction::class,
            [
                'context' => $this->context,
                'resultForwardFactory' => $this->forwardFactory
            ]
        );
    }

    /**
     * Test testExecute method.
     */
    public function testExecute()
    {
        $testMethod = new \ReflectionMethod(
            \Fedex\Shipment\Controller\Adminhtml\shipment\NewAction::class,
            '_isAllowed',
        );
        $testMethod->invoke($this->newAction);
        $this->forwardFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->forwardFactory->expects($this->any())->method('forward')->willReturn("edit");
        $this->assertEquals('edit', $this->newAction->execute());
    }
}
