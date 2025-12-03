<?php
declare(strict_types=1);

namespace Fedex\Pod\Plugin\Checkout\Model;

use Magento\Framework\Message\ManagerInterface;
use Magento\Checkout\Model\Session;

class Cart
{
    public function __construct(
        protected Session $session,
        protected ManagerInterface $messageManager
    )
    {
    }
    public function beforeUpdateItems(\Magento\Checkout\Model\Cart $subject, $data): void
    {
        $cartQuantity = $data;
        $quote = $subject->getQuote();

        if ((null != $quote->getItems()) || is_array($quote->getItems()) || is_object($quote->getItems())) {
            foreach ($quote->getItems() as $item) {
                if(empty($cartQuantity[$item->getId()])) {
                    continue;
                }
                $option = $item->getOptionByCode('info_buyRequest');
                $value = $option->getValue();
                $productArray = (array)json_decode($value, true);
                if(isset($productArray['external_prod'])) {
                    $externalProd = $productArray['external_prod'][0];
                    if (isset($externalProd['fxo_product'])) {
                        $fxoProduct = json_decode($externalProd['fxo_product'], true);
                        if (isset($fxoProduct['fxoProductInstance']['productConfig']['product']['qty'])) {
                            if (!empty($cartQuantity[$item->getId()])) {
                                $qty = $cartQuantity[$item->getId()]['qty'];
                                $fxoProduct['fxoProductInstance']['productConfig']['product']['qty'] = (int)$qty;
                                $externalProd['fxo_product'] = json_encode($fxoProduct);
                                $option->setValue(json_encode($productArray))->save();
                            }
                        }
                    } elseif (isset($externalProd['qty'])) {
                        $qty = $cartQuantity[$item->getId()]['qty'];
                        $externalProd['qty'] = (int)$qty;
                        $productArray['external_prod'][0] = $externalProd;
                        $option->setValue(json_encode($productArray))->save();
                    }
                }
            }
        }
    }
}
