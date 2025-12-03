<?php
namespace Fedex\Email\Block\Order\Email;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceCheckout\Api\OrderItemsInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Store\Model\App\Emulation;

class Items extends Template implements OrderItemsInterface
{
    // @codingStandardsIgnoreLine
    protected $_template = 'Fedex_Email::email/items.phtml';

    /**
     * @param Context $context
     * @param PriceHelper $priceHelper
     * @param Emulation $emulation
     * @param ToggleConfig $toggleConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        private PriceHelper $priceHelper,
        private Emulation $emulation,
        protected ToggleConfig $toggleConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @param $value
     * @return float|string
     */
    public function formattedCurrencyValue($value): float|string
    {
        return $this->priceHelper->currency($value, true, false);
    }

    /**
     * Retrieve url of a view file
     *
     * @param string $fileId
     * @param array $params
     * @return string
     */
    public function getViewFileUrl($fileId, array $params = []): string
    {
        $this->emulation->startEnvironmentEmulation(0, \Magento\Framework\App\Area::AREA_FRONTEND, true);
        $fileUrl = parent::getViewFileUrl($fileId, $params);
        $this->emulation->stopEnvironmentEmulation();
        return $fileUrl;
    }

    /**
     * @return array
     */
    public function getShipmentItemsFormatted(): array
    {
        $shipmentItem = $this->getShipmentItems();
        $shipmentItem3p = [];
        foreach ($shipmentItem as $key => $item) {
            if (isset($item["mirakl_shop_name"])) {
                $shipmentItem3p[$item["mirakl_shop_name"]][] = $item;
                unset($shipmentItem[$key]);
            }
        }
        return ['1p' => $shipmentItem, '3p' => $shipmentItem3p];
    }

    /**
     * @param $shippingMethod
     * @return string
     */
    public function formatShippingMethodName($shippingMethod): string
    {
        if (!str_starts_with($shippingMethod, 'FedEx')) {
            return 'FedEx '.$shippingMethod;
        }
        return $shippingMethod;
    }

    /**
     * Retrieve expected delivery date toggle
     *
     * @return bool
     */
    public function isExpectedDeliveryDateEnabled(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue('sgc_enable_expected_delivery_date');
    }
}
