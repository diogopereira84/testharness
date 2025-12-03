<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOCMConfigurator
 */
declare(strict_types=1);

namespace Fedex\FXOCMConfigurator\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Cart;
use Fedex\Cart\ViewModel\ProductInfoHandler;

class ConfigProvider implements ConfigProviderInterface
{

    /**
     * @param cart $cart
     * @param ProductInfoHandler $productInfoHandler
     */
    public function __construct(
        private readonly Cart $cart,
        private readonly ProductInfoHandler $productInfoHandler
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getConfig(): array
    {
        $data = [];
        $previewUrls=[];
        
        $previewUrls = [];
        foreach($this->cart->getItems() as $item){
           $externalProd = (array)$this->productInfoHandler->getItemExternalProd($item);
           $imgSrc = $externalProd['preview_url'] ?? '';
           $previewUrls[$item->getItemId()] = $imgSrc;
        }
        $data['imagePreview'] = $previewUrls;
        return $data;
    }
}
