<?php
namespace Fedex\Purchaseorder\Plugin;

use Fedex\Purchaseorder\Plugin\DefaultRendererPlugin;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\DataObject;

class DefaultRendererPluginTest extends TestCase
{
    protected $defaultRenderer;
    protected $orderItemMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $defaultRendererPluginMock;
    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var \Closure
     */
    private \Closure $proceed;
    private DataObject|MockObject $item;

    public function setUp(): void
    {
        $this->defaultRenderer = $this->getMockBuilder(DefaultRenderer::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderItemRepository = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderItemMock = $this->getMockBuilder(
                \Magento\Sales\Model\Order\Item::class
            )
                ->disableOriginalConstructor()
                ->setMethods(["getData"])
                ->getMock();

        $response = $this->getMockForAbstractClass(ResponseInterface::class);

        $this->proceed = function () use ($response) {
            return $response;
        };
        $this->objectManager = new ObjectManager($this);
        $this->defaultRendererPluginMock = $this->objectManager->getObject(
            DefaultRendererPlugin::class,
            [
                'orderItemRepository'    => $this->orderItemRepository
            ]
        );
    }

    public function testAroundGetColumnHtml()
    {
        $this->item = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->item->expects($this->any())->method('getData')->willReturnSelf();
        $this->orderItemRepository->expects($this->any())->method('get')->willReturn($this->orderItemMock);
        $this->orderItemMock->expects($this->any())->method('getData')->willReturnSelf();
        $column = 'ext_order_id';
        $field = null;
        $this->defaultRendererPluginMock->aroundGetColumnHtml($this->defaultRenderer, $this->proceed, $this->item, $column, $field);

    }

    public function testAroundGetColumnHtmlwithElse()
    {
        $this->item = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->item->expects($this->any())->method('getData')->willReturnSelf();
        $this->orderItemRepository->expects($this->any())->method('get')->willReturn($this->orderItemMock);
        $this->orderItemMock->expects($this->any())->method('getData')->willReturnSelf();
        $column = 'ext_order_id_ir';
        $field = null;
        $this->defaultRendererPluginMock->aroundGetColumnHtml($this->defaultRenderer, $this->proceed, $this->item, $column, $field);

    }

    public function testAroundGetColumnHtmlwithField()
    {
        $this->item = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->item->expects($this->any())->method('getData')->willReturnSelf();
        $this->orderItemRepository->expects($this->any())->method('get')->willReturn($this->orderItemMock);
        $this->orderItemMock->expects($this->any())->method('getData')->willReturnSelf();
        $column = 'ext_order_id_ir';
        $field = 'entity_id';
        $this->defaultRendererPluginMock->aroundGetColumnHtml($this->defaultRenderer, $this->proceed, $this->item, $column, $field);

    }
}
