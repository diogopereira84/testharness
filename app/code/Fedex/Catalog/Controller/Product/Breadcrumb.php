<?php

declare(strict_types=1);

namespace Fedex\Catalog\Controller\Product;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\Catalog\Helper\Breadcrumbs as BreadcrumbsHelper;

class Breadcrumb extends \Magento\Framework\App\Action\Action
{
    /**
     * Breadcrumb constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param JsonValidator $jsonValidator
     * @param ProductRepositoryInterface $productRepository
     * @param BreadcrumbsHelper $helper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        private JsonFactory $resultJsonFactory,
        private JsonValidator $jsonValidator,
        private ProductRepositoryInterface $productRepository,
        private BreadcrumbsHelper $helper,
        private StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $refererCrumb = [];
        if ($this->helper->getControlJson()) {
            $baseUrl = $this->storeManager->getStore()->getBaseUrl();
            $refererUrl = $this->getRequest()->getPost('ref');
            $productId = $this->getRequest()->getPost('pid');

            if ($refererUrl && $productId) {
                $path = explode('?', $refererUrl);
                $refererUrlKey = str_replace($baseUrl, '', $path[0]);
                $refererCrumb = $this->getRefererCrumb($refererCrumb, $refererUrlKey, $refererUrl, $productId);
            }
        }

        $resultJson = $this->resultJsonFactory->create();
        $success = true;
        return $resultJson->setData([
            'data' => json_encode($refererCrumb),
            'success' => $success
        ]);
    }

    /**
     * @param array $refererCrumb
     * @param string $refererUrlKey
     * @param string $refererUrl
     * @param int $productId
     * @return array
     */
    public function getRefererCrumb($refererCrumb, $refererUrlKey, $refererUrl, $productId)
    {
        if ($this->jsonValidator->isValid($this->helper->getControlJson())) {
            $product = $this->productRepository->getById($productId);
            $controlJSon = json_decode($this->helper->getControlJson(), true);
            foreach ($controlJSon as $control) {
                if (($control['url'] === $refererUrlKey) &&
                    (empty($control['skus']) ||
                    in_array($product->getSku(), explode(',', $control['skus'])))) {
                    $refererCrumb = [
                        'label' => $control['label'],
                        'title' => $control['label'],
                        'skus' => $control['skus'],
                        'link' => $refererUrl
                    ];
                }
            }
        }
        return $refererCrumb;
    }
}
