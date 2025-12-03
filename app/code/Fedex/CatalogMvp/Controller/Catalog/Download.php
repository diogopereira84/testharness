<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Controller\Catalog;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Fedex\CatalogMvp\Helper\Download as DownloadHelper;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Framework\UrlInterface;


class Download implements ActionInterface
{
    protected $url;

    /**
     * @param Context $context
     * @param ResultFactory $resultFactory
     * @param DownloadHelper $downloadHelper
     * @param CatalogDocumentRefranceApi $catalogDocument
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private Context $context,
        protected ResultFactory $resultFactory,
        private RequestInterface $request,
        protected DownloadHelper $downloadHelper,
        private UrlInterface $urlBuilder,
        protected CatalogDocumentRefranceApi $catalogDocument
    )
    {
    }

    /**
     * Download file
     *
     * @return string
     */
    public function execute()
    {
        $productId = $this->request->getPost('productId');
        $product = $this->catalogDocument->getProductObjectById($productId);
        $downloadFileUrl = null;

        if ($product && is_object($product)) {
            $productName = str_replace(" ", "_", $product->getName());
            $externalProd = $product->getExternalProd();

            if ($externalProd) {
                $downloadFileUrl = $this->downloadHelper->getDownloadFileUrl(
                    $productName, $externalProd);
            }
        }

        if($downloadFileUrl) {
            $downloadFileUrlBase64 = base64_encode($downloadFileUrl);
            $fileName = strtolower($this->generateFileName($productName));
            $fileName = trim($fileName,"_");
            if(!$fileName) {
                $fileName = "download";
            }
            $downloadFileUrl = $this->urlBuilder->getUrl('catalogmvp/catalog/downloadFile/fileurl/'.$downloadFileUrlBase64.'/filename/'.$fileName);
        }

        $rawResult = $this->resultFactory->create(
            ResultFactory::TYPE_RAW);

        return $rawResult->setContents($downloadFileUrl);
    }

    /**
     * @param string $productName
     * @return string
     */
    public function generateFileName($productName){
        return preg_replace("/[^a-zA-Z0-9\_]+/", "", trim($productName));
    }
}
