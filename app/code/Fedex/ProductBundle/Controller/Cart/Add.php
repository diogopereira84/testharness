<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\Controller\Cart;

use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\ProductBundle\Model\Cart\AddBundleToCart;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class Add implements HttpPostActionInterface
{
    private const PARAM_PRODUCT_ID   = 'product';
    private const PARAM_BUNDLE_OPT   = 'bundle_option';
    private const PARAM_QTY          = 'qty';

    public function __construct(
        private RequestInterface $request,
        private Validator $formKeyValidator,
        private LoggerInterface $logger,
        private JsonFactory $resultJsonFactory,
        private AddBundleToCart $addBundleToCart,
        private UrlInterface $url,
        private ConfigInterface $productBundleConfig
    ) {
    }

    public function execute(): ResultInterface
    {
        $resultJson = $this->resultJsonFactory->create();

        if(!$this->productBundleConfig->isTigerE468338ToggleEnabled()) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Product Bundle feature is disabled.')
            ]);
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Invalid form key.')
            ]);
        }

        try {
            $productId = (int)$this->request->getParam(self::PARAM_PRODUCT_ID);
            $bundleOptions = (array)$this->request->getParam(self::PARAM_BUNDLE_OPT, []);
            $qty = max(1, (int)$this->request->getParam(self::PARAM_QTY, 1));

            if (!$productId || empty($bundleOptions)) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Product and bundle options are required.')
                ]);
            }

            $this->addBundleToCart->execute($productId, $bundleOptions, $qty);

            return $resultJson->setData([
                'success' => true,
                'message' => __('Bundle product added to cart.'),
                'backUrl' => $this->url->getUrl('checkout/cart')
            ]);
        } catch (LocalizedException $e) {
            $this->logger->critical($e); // More visibility than error()
            return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $resultJson->setData([
                'success' => false,
                'message' => __('Unable to add product to cart.')
            ]);
        }
    }
}
