<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\ComputerRental\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Fedex\ComputerRental\Model\CRdataModel;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;

class Index implements HttpPostActionInterface,HttpGetActionInterface
{
    /**
     *
     * constructor.
     *
     * @param RedirectFactory $resultRedirectFactory
     * @param CRdataModel $CRdataModel
     * @param UrlInterface $url
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly RedirectFactory $resultRedirectFactory,
        protected CRdataModel $CRdataModel,
        protected UrlInterface $url,
        private readonly LoggerInterface $logger,
        private readonly RequestInterface $request,
    ) {
    }

    /**
     * Execute the action
     *
     * @return Redirect
     */
    public function execute()
    {
        $noRouteUrl = $this->url->getUrl('noroute');
        $resultRedirect = $this->resultRedirectFactory->create();
        try{
            $redirectURL = $noRouteUrl;

            $storeNumber = $this->request->getParam('storeNumber')??'';
            $locationId = $this->request->getParam('locationId')??'';
            if($storeNumber!="" && $locationId!=""){
                $redirectURL = $this->request->getParam('redirectURL')??'';
                $this->CRdataModel->saveStoreCodeInSession($storeNumber);
                $this->CRdataModel->saveLocationCode($locationId);
                $redirectURL = ($redirectURL!="")?$redirectURL:$noRouteUrl;
            }

            $resultRedirect->setUrl($redirectURL);
            return $resultRedirect;
        }catch(\Exception $e){
            $this->logger->error('Error in '.__FILE__.__LINE__.' no' . $e->getMessage());
            $resultRedirect->setUrl($noRouteUrl);
            return $resultRedirect;
        }
    }
}
