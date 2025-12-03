<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\FXOCMConfigurator\Cron;

use Fedex\FXOCMConfigurator\Helper\Data as FxocmHelper;
use Fedex\FXOCMConfigurator\Model\ResourceModel\OrderRetationPeriod\CollectionFactory as OrderRetationPeriodCollectionFactory;
use Fedex\FXOCMConfigurator\Model\OrderRetationPeriodFactory;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Psr\Log\LoggerInterface;

class OrderDocumentLifeExtendCron
{

    /**
     * @var OrderRetationPeriodCollectionFactory
     */
    protected $orderRetationPeriodCollectionFactory;

    /**
     * @var OrderRetationPeriod
     */
    protected $orderRetationPeriod;

    /**
     * Constructor
     *
     * @param FxocmHelper $fxocmHelper
     * @param OrderRetationPeriodCollectionFactory $OrderRetationPeriodCollectionFactory
     * @param OrderRetationPeriod $orderRetationPeriod
     * @param CatalogDocumentRefranceApi   $catalogDocumentRefranceApi
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected FxocmHelper $fxocmHelper,
        OrderRetationPeriodCollectionFactory $OrderRetationPeriodCollectionFactory,
        OrderRetationPeriodFactory $orderRetationPeriod,
        protected CatalogDocumentRefranceApi $catalogDocumentRefranceApi,
        protected LoggerInterface $logger
    ) {
        $this->orderRetationPeriodCollectionFactory = $OrderRetationPeriodCollectionFactory;
        $this->orderRetationPeriod = $orderRetationPeriod;
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        if ($this->fxocmHelper->getNewDocumentsApiImagePreviewToggle()) {
            try {
                  $now = new \DateTime();
                  $legacyDocReorderCronToggle = $this->fxocmHelper->getLegacyDocsNoReorderCronToggle();
                  //Get Order Retation Period table collection, with equal to current date and less than 3 flag.
                  //Need to extend life only 3 times (6 months x 3= 18 months)
                  $orderRetationPeriodCollection = $this->orderRetationPeriodCollectionFactory->create();
                  $orderRetationPeriodCollection = $orderRetationPeriodCollection->addFieldToFilter('extended_date', ['eq' => $now->format('Y-m-d')]);
                  $orderRetationPeriodCollection = $orderRetationPeriodCollection->addFieldToFilter('extended_flag', ['lt' => 3]);

                  //Log for debug purpose
                  $this->logger->info(__METHOD__.':'.__LINE__.' Cron document life extend call');

                if ($orderRetationPeriodCollection->getSize()) {
                    foreach ($orderRetationPeriodCollection as $orderRetationPeriodEach) {
                          
                        $documentId = $orderRetationPeriodEach->getDocumentId();
                        // Check if documentId is numeric; if so, skip API call
                        if (isset($documentId) && is_numeric($documentId) && $legacyDocReorderCronToggle) {
                            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Skipping document life extend for numeric documentId: ' . $documentId . ' for order_item_id : ' . $orderRetationPeriodEach->getOrderItemId());
                            continue;
                        }
                          //call document extend api
                          $response = $this->catalogDocumentRefranceApi->documentLifeExtendApiCallWithDocumentId($documentId);
                        if (!empty($response) && array_key_exists('output', $response)) {
                            
                            // Increase extend flag
                            $extendedFlag = $orderRetationPeriodEach->getExtendedFlag() + 1;
                            $documentExpiryDate = $response['output']['document']['expirationTime'];

                            $orderRetationPeriodModel = $this->orderRetationPeriod->create();
                            $orderRetationPeriodModel->load($orderRetationPeriodEach->getId(), "id");
                            $orderRetationPeriodModel->setExtendedDate(date("Y-m-d", strtotime($documentExpiryDate)));
                            $orderRetationPeriodModel->setExtendedFlag($extendedFlag);
                            $orderRetationPeriodModel->save();
                          //Log for debug purpose
                            $this->logger->info(__METHOD__.':'.__LINE__.' Cron document life extend for order_item_id : ' . $orderRetationPeriodEach->getOrderItemId());
                        }

                    }
                }
            } catch (\Exception $e) {
                 $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error in processing document life extend api cron: ' .
                 $e->getMessage());
            }
        }
    }
}
