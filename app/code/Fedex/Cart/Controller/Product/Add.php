<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Controller\Product;

use Magento\Framework\App\Action\Context;
use Fedex\Cart\Model\Quote\Product\Add as QuoteProductAdd;
use Magento\Checkout\Model\Session;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\InBranch\Model\InBranchValidation;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\FXOCMConfigurator\Helper\Batchupload as BatchuploadHelper;
use Fedex\CustomerCanvas\Model\ConfigProvider;
use \Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Execute Add to cart
 */
class Add extends \Magento\Framework\App\Action\Action
{
    /**
     * Add constructor.
     * @param Context $context
     * @param QuoteProductAdd $quoteProductAdd
     * @param Session $quote
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        Context $context,
        protected QuoteProductAdd $quoteProductAdd,
        protected Session $quote,
        private LoggerInterface $logger,
        protected ToggleConfig $toggleConfig,
        private InBranchValidation $inBranchValidation,
        private JsonFactory $resultJsonFactory,
        protected BatchuploadHelper $batchuploadhelper,
        protected ConfigProvider $configProvider,
        protected DateTime $date
    ) {
        parent::__construct($context);
    }

    /**
     * Add Controller Execute Method
     */
    public function execute()
    {
        try {
            $requestData = $this->getRequest()->getPost('data');
            $itemId = $this->getRequest()->getPost('itemId') ?? false;
            $resultJsonData = $this->resultJsonFactory->create();
            if ($requestData) {
                $insatnceId = strtotime(date("Y-m-d H:i:s"));
                $requestDataArray = json_decode($requestData,true);
                if (isset($requestDataArray['productType']) &&
                ($requestDataArray['productType'] == "COMMERCIAL_PRODUCT")) {
                    if(isset($requestDataArray['fxoProductInstance']['productConfig']['product']) &&
                    ($requestDataArray['fxoProductInstance']['productConfig']['product']['instanceId'] == "0" ||
                    $requestDataArray['fxoProductInstance']['productConfig']['product']['instanceId'] == null)) {
                        $requestDataArray['fxoProductInstance']['productConfig']['product']['instanceId'] = $insatnceId;
                    }
                    $requestData = json_encode($requestDataArray);
                }
                if($this->configProvider->isDyeSubEnabled()) {
                    if(empty($requestDataArray['instanceId'])){
                        $vendorOptions = $requestDataArray['vendorOptions'] ??[];
                        if(!empty($vendorOptions)){
                            $vendorOptions['designCreationTime'] = $this->date->date('Y-m-d H:i:s');
                        }
                        $requestDataArray['vendorOptions'] = $vendorOptions;
                        $requestData = json_encode($requestDataArray);
                    }
                }
            }
              //Inbranch Implementation
                    $isInBranchProductExist = $this->inBranchValidation->isInBranchValid($requestData,true);
                    if($isInBranchProductExist){
                        return $resultJsonData->setData(['isInBranchProductExist' => true]);
                    }
             //Inbranch Implementation
            $this->quoteProductAdd->setCart($this->quote->getQuote());
            $data = $this->quoteProductAdd->addItemToCart($requestData, $itemId);

            // To save userworkspace data in session after Add to cart from FXO CM
            if ( $this->toggleConfig->getToggleConfigValue('batch_upload_toggle') &&
                $this->toggleConfig->getToggleConfigValue('fxo_cm_toggle')
                )
            {
                $requestDataArray = json_decode($requestData,true);
                if(!empty($requestDataArray['userWorkspace']['files']))
                {
                    $workSpaceData = json_encode($requestDataArray['userWorkspace']);
                    $this->batchuploadhelper->addBatchUploadData($workSpaceData);
                }
            }

        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('An error occurred'));
        }
    }
}
