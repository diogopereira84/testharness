<?php
namespace Fedex\Purchaseorder\Plugin;

use Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer;
use Magento\Sales\Api\OrderRepositoryInterface;


class DefaultRendererPlugin
{
    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    public function __construct(
        OrderRepositoryInterface $orderItemRepository
    ) {
        $this->orderItemRepository = $orderItemRepository;
    }

    public function aroundGetColumnHtml(
        DefaultRenderer $defaultRenderer,
        \Closure $proceed,
        \Magento\Framework\DataObject $item,
        $column,
        $field=null
    ) {
        $orderItem = $this->orderItemRepository->get($item->getData('order_id'));
        
        if ($column == 'ext_order_id') {
            $html = $orderItem->getData('ext_order_id');
            $result = $html;
        } else {
            if ($field) {
                $result = $proceed($item, $column, $field);
            } else {
                $result = $proceed($item, $column);

            }
        }

        return $result;
    }
}
