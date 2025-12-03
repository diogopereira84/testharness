<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Controller\Adminhtml\Catalog;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Fedex\CatalogMvp\Helper\Download as DownloadHelper;


class Download implements ActionInterface
{
    /**
     * @param Context $context
     * @param ResultFactory $resultFactory
     * @param DownloadHelper $downloadHelper
     * @param RequestInterface $request
     */
    public function __construct(
        private Context $context,
        protected ResultFactory $resultFactory,
        private RequestInterface $request,
        protected DownloadHelper $downloadHelper,
        private UrlInterface $urlBuilder
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
        $productName = $this->request->getPost('productName');
        $externalProd = $this->request->getPost('externalProd');
        $downloadFileUrl = null;
        if ($externalProd) {
            $downloadFileUrl = $this->downloadHelper->getDownloadFileUrl(
                $productName, $externalProd);
        }
        $rawResult = $this->resultFactory->create(
            ResultFactory::TYPE_RAW);
        if ($downloadFileUrl) {
            $downloadFileUrlBase64 = base64_encode($downloadFileUrl);
            $productName = str_replace(" ", "_", $productName);
            $fileName = strtolower($this->generateFileName($productName));
            $fileName = trim($fileName,"_");
            if(!$fileName) {
                $fileName = "download";
            }
            $downloadFileUrl = $this->urlBuilder->getUrl('catalogmvp/catalog/downloadFile/fileurl/'.$downloadFileUrlBase64.'/filename/'.$fileName);
        }

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
