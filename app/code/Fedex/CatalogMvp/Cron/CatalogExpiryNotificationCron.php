<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Cron;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Fedex\CatalogMvp\Helper\EmailHelper;

class CatalogExpiryNotificationCron
{
    const EXPLORERS_E408187_CATALOG_EXPIRY_NOTIFICATIONS_TOGGLE = 'catalog_expiry_notifications';
    const EXTRA_EXTENSION_PATH = '.html';
    const ID = 'id';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var configInterface
     */
    protected $catalogDocumentRefranceApiHelper;

    /**
     * CatalogExpiryNotificationCron Constructor
     *
     * @param LoggerInterface $loggerInterface
     * @param ToggleConfig $toggleConfig
     * @param CatalogDocumentRefranceApi $catalogDocumentRefranceApiHelper
     * @param EmailHelper $emailHelper
     * @param TimezoneInterface $date
     */
    public function __construct(
        LoggerInterface $loggerInterface,
        protected ToggleConfig $toggleConfig,
        CatalogDocumentRefranceApi $catalogDocumentRefranceApiHelper,
        protected EmailHelper $emailHelper
    ) {
        $this->logger                               = $loggerInterface;
        $this->catalogDocumentRefranceApiHelper     = $catalogDocumentRefranceApiHelper;
    }

    /**
     * CatalogExpiryNotificationCron Execute
     *
     * @return mixed
     */
    public function execute()
    {
        $catalogExpiryNotificationsToggle = $this->toggleConfig->getToggleConfigValue(self::EXPLORERS_E408187_CATALOG_EXPIRY_NOTIFICATIONS_TOGGLE);
        if ($catalogExpiryNotificationsToggle) {
            $formattedTwoMonthExpiryCatalogdata = $catalogExpirationData = $initialCatalogExpirationData = [];
            $emailUrlLink = '';
            $twoMonthExpiryCatalogdata = $this->catalogDocumentRefranceApiHelper->getExpiryDocuments();
            $expiryCatalogCount = count($twoMonthExpiryCatalogdata);
            for($i=0; $i<$expiryCatalogCount; $i++) {
                $key = $twoMonthExpiryCatalogdata[$i]['user_id'] . '-' . $twoMonthExpiryCatalogdata[$i]['company_id'];
                $catalogExpirationDate = $twoMonthExpiryCatalogdata[$i]['expiration_date'];
                $companyUrlExtention = $twoMonthExpiryCatalogdata[$i]['company_url_extention'];
                $categoryUrlPath = $twoMonthExpiryCatalogdata[$i]['category_url_path'];
                $folderPath = $this->getFolderPath($categoryUrlPath, $companyUrlExtention);
                if(isset($folderPath)) {
                    $emailUrlLink = $folderPath.'?email=1' ?? '';
                }
                $twoMonthExpiryCatalogdata[$i]['folder_path'] = $emailUrlLink;
                if (!isset($catalogExpirationData[$key])) {
                    $catalogExpirationData[$key] =  [
                        'user_id' => $twoMonthExpiryCatalogdata[$i]['user_id'],
                        'company_id' => $twoMonthExpiryCatalogdata[$i]['company_id'],
                        'catalogExpirationData' => $initialCatalogExpirationData
                    ];
                }
                $catalogExpirationData[$key]['catalogExpirationData'][] = [
                    'name' => $twoMonthExpiryCatalogdata[$i]['name'],
                    'catalog_expiration_date' => $catalogExpirationDate,
                    'folder_path' => $twoMonthExpiryCatalogdata[$i]['folder_path']
                ];
            }
            $formattedTwoMonthExpiryCatalogdata = array_values($catalogExpirationData);
            foreach($formattedTwoMonthExpiryCatalogdata as $twoMonthExpiryCatalogdata) {
                try {

                    $isEmailSuccess = $this->emailHelper->sendCatalogExpirationEmail($twoMonthExpiryCatalogdata);
                    if($isEmailSuccess) {
                            $this->logger->info(__METHOD__ . ':' . __LINE__ .' '."Catalog Expiration Email Sent Successfully:".$twoMonthExpiryCatalogdata['user_id']);
                    } else {
                            $this->logger->info(__METHOD__ . ':' . __LINE__ .' '."Unable to Send Catalog Expiration Email:".$twoMonthExpiryCatalogdata['user_id']);
                    }
                } catch(\Exception $e) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ .'Error sending catalog expiration email to '."user_id:".$twoMonthExpiryCatalogdata['user_id'].' '.$e->getMessage());
                }
            }
        }
    }

    /**
     * get FolderPath
     *
     * @param string $categoryUrlPath
     * @param string $urlExtention
     * @return mixed
     */
    public function getFolderPath($categoryUrlPath, $urlExtention)
    {
        $folderPath = '';
        $storFrontUrl = $this->emailHelper->getBaseUrl();
        $folderPath = $storFrontUrl.$urlExtention .'/'. trim($categoryUrlPath, '/') . self::EXTRA_EXTENSION_PATH;
        return $folderPath;
    }
}
