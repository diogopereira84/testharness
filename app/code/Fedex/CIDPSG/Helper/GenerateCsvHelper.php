<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Helper;

use Fedex\CIDPSG\Helper\AdminConfigHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;

/**
 * CIDPSG GenerateCsvHelper class
 */
class GenerateCsvHelper extends AbstractHelper
{
    public const EXCEL_DIR_PATH = 'cidpsg';
    private Filesystem\Directory\WriteInterface $directory;

    public $formData;
    public $usStates;
    /**
     * GenerateCsvHelper Constructor
     *
     * @param Context $context
     * @param DirectoryList $directoryList
     * @param Filesystem $filesystem
     * @param AdminConfigHelper $adminConfigHelper
     */
    public function __construct(
        Context $context,
        protected DirectoryList $directoryList,
        protected Filesystem $filesystem,
        protected AdminConfigHelper $adminConfigHelper
    ) {
        parent::__construct($context);
        $this->directory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * Generate ecxel file
     *
     * @param array $headerDataArray
     * @param array $rowDataArray
     * @param string $fileName
     * @param string $cidpsgDir
     * @return string $attachFile
     */
    public function generateExcel($headerDataArray, $rowDataArray, $fileName, $cidpsgDir)
    {
        if (!$this->directory->isDirectory($cidpsgDir)) {
            $this->directory->create($cidpsgDir);
        }

        $filepath = '/' . $cidpsgDir . '/' . $fileName;
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();
        $stream->writeCsv($headerDataArray);
        $stream->writeCsv($rowDataArray);

        return $this->directoryList->getPath('media') . $filepath;
    }

    /**
     * Generate Excel for the authorized user
     *
     * @param array $formData
     * @return mixed
     */
    public function generateExcelForAuthrizedUser($formData)
    {
        $country = ($formData['country'] == 'US') ? 'United States' : 'Canada';
        $allStates = $this->adminConfigHelper->getAllStates($formData['country']);

        $state = '';
        foreach ($allStates as $stateValue) {
            if ($stateValue['label'] == $formData['state']) {
                $state = $stateValue['title'];
                break;
            }
        }

        $headerDataArray = [
            'Account Name',
            'FXK Account Number',
            'FXK Authorized User Number',
            'User Status',
            'Authorized User Name',
            'Country',
            'Street Address',
            'Street Address Line 2',
            'City',
            'State',
            'Zip/Postal Code',
            'Customer Reference',
            'Attention',
            'Suite#/Other',
            'Authorized User Email',
            'Authorized User Phone',
            'Authorized User Phone Ext',
            'Physical Card Requested'
        ];

        $rowDataArray = [
            $formData['company_name'],
            $formData['office_account_no'],
            '',
            '',
            $formData['account_user_name'],
            $country,
            $formData['street_address'],
            $formData['address_line_two'],
            $formData['city'],
            $state,
            $formData['zipcode'],
            '',
            '',
            $formData['suite'],
            $formData['email'],
            $formData['phone'],
            '',
            $formData['account_no_radio']
        ];

        $timeZoneUtc = new \DateTime(date("Y-m-d H:i:s"), new \DateTimeZone('UTC'));
        $timeZoneUtc->setTimezone(new \DateTimeZone('CST'));
        $cstTime = $timeZoneUtc->format('Ymd_His');
        $fileName = 'AuthorizedUserUpdate_' . str_replace(" ", "_", $formData['account_user_name']) . '_'
            . $formData['office_account_no'] . '_' . $cstTime . '.csv';

        return $this->generateExcel($headerDataArray, $rowDataArray, $fileName, self::EXCEL_DIR_PATH);
    }
}
