<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CmsImportExport\Model\Import;

use Magento\Framework\Exception\LocalizedException;
use Magento\MediaStorage\Model\File\UploaderFactory;
use \Fedex\CmsImportExport\Helper\Data;
use Psr\Log\LoggerInterface;

class Validate extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var AllowedExtension
     */
    protected $allowedExtensions = ['csv'];

    /**
     * @var fileId  $fileId
     */
    protected $fileId = 'import_file';

    /**
     * @var ValidColumnNames
     */
    const VALIDCOLUMNNAMES =["type","title","identifier","content","is_active","stores",
    "content_heading","meta_title","meta_keywords","meta_description","layout_update_xml",
    "custom_theme","custom_root_template","custom_theme_from","custom_theme_to","page_layout",
    "name","template","preview_image","created_for","instance_type","theme_id","widget_parameters",
    "page_groups_json","page_id_identifier","block_id_identifier","category_id_name","product_id_sku"];

    /**
     * @param UploaderFactory $uploaderFactory
     * @param Data $helper
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected UploaderFactory $uploaderFactory,
        protected Data $helper,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * To validate import Csv
     *
     * Validate header columns, rows data
     *
     * @return string
     */
    public function validateCsv()
    {
        $destinationPath = $this->helper->getDestinationPath();
        try {
            $uploader = $this->uploaderFactory->create(['fileId' => $this->fileId])
                ->setAllowCreateFolders(true)
                ->setAllowedExtensions($this->allowedExtensions);
            $uploader->setAllowRenameFiles(false);
            $this->uploaderSave($uploader, $destinationPath);
            $uploadedFile = $destinationPath . '/' . $uploader->getUploadedFileName();
            $csvHeader = $this->helper->getCsvHeader($uploadedFile);
            
            if (count((array)array_unique((array)$csvHeader))!=count((array)$csvHeader)) {
                return "Duplicate column exist in CSV";
            }
            $csvData = $this->helper->convertCsvToArray($uploadedFile);
            
            $rowCountValidate = [];
            $result = "";
            $validateResult = [];
            if (!is_array($csvData)) {
                $result = $csvData;
            }
            if (!empty($csvData) && is_array($csvData)) {
                foreach ($csvData as $rowNum => $data) {
                    if (count($data)>count(array_unique($csvHeader))) {
                        $rowCountValidate[] = $rowNum+1;
                        continue;
                    }
                    if ($this->validateRow($rowNum+1, $data)!="") {
                        $validateResult[] = $this->validateRow($rowNum+1, $data);
                    }
                }
                $this->returnMessage($csvHeader, $rowCountValidate);
                $result = $this->validateResult($validateResult, $csvData);
            } else {
		    $result = "No data available in csv to import";
	    }
            return $result;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * Return Message
     * @return string
     */
    public function returnMessage($csvHeader, $rowCountValidate)
    {
        if (count($csvHeader) != count(self::VALIDCOLUMNNAMES)) {
            return "Columns are not valid as per valid csv format";
        }
        if (!empty($rowCountValidate)) {
            return "Column count does not match with header count at row ".
            implode(",", $rowCountValidate);
        }
    }

    /**
     * Validate Result
     * @return string
     */
    public function validateResult($validateResult, $csvData)
    {
        if (!empty($validateResult) && is_array($validateResult)) {
            $rows = "Invalid rows at ". implode(",", $validateResult);
            $result = "Checked rows ".count($csvData).", Invalid rows ".
            count($validateResult)."<br/>";
            $result = $result.$rows;
        } else {
            $result = "success";
        }
        return $result;
    }

    /**
     * Save Uploader
     * @return void
     */
    public function uploaderSave($uploader, $destinationPath)
    {
        if (!$uploader->checkAllowedExtension($uploader->getFileExtension())) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Invalid file type.');
            // @codeCoverageIgnoreStart
            throw new \Magento\Framework\Exception\LocalizedException(
                new \Magento\Framework\Phrase('Invalid file type.')
            );
            // @codeCoverageIgnoreEnd
        }

        if (!$uploader->save($destinationPath)) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' File cannot be saved to path.');
            // @codeCoverageIgnoreStart
            throw new LocalizedException(
                __('File cannot be saved to path: $1', $destinationPath)
            );
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * To validate import Csv row data
     *
     * @param int $rowNum
     * @param array $data
     * @return string
     */
    public function validateRow($rowNum, $data)
    {
        $validateRow = "";
        if (isset($data["type"])) {
            if ($data["type"]=="cms_block" || $data["type"]=="cms_page") {
                $validateRow = $this->validateBlockPage($rowNum, $data);
            } elseif ($data["type"]=="template") {
                $validateRow = $this->validateTemplate($rowNum, $data);
            } elseif ($data["type"]=="widget") {
                $validateRow = $this->validateWidget($rowNum, $data);
            }
        }
        return $validateRow;
    }

    /**
     * To validate import Csv row data based on required column for Blocks and Pages
     *
     * @param int $rowNum
     * @param array $data
     * @return string
     */
    public function validateBlockPage($rowNum, $data)
    {
        $validateRow = "";
        $identifier = $data['identifier'] ?? '';
        $title = $data['title'] ?? '';
        $content = $data['content'] ?? '';
        if (!$identifier || !$title || !$content) {
            $validateRow = $rowNum;
        }
        return $validateRow;
    }

    /**
     * To validate import Csv row data based on required column for template
     *
     * @param int $rowNum
     * @param array $data
     * @return string
     */
    public function validateTemplate($rowNum, $data)
    {
        $validateRow = "";
        $name = $data['name'] ?? '';
        $createdFor = $data['created_for'] ?? '';
        $content = $data['content'] ?? '';
        if (!$name || !$createdFor || !$content) {
            $validateRow = $rowNum;
        }
        return $validateRow;
    }

    /**
     * To validate import Csv row data based on required column for widget
     *
     * @param int $rowNum
     * @param array $data
     * @return string
     */
    public function validateWidget($rowNum, $data)
    {
        $validateRow = "";
        $instanceType = $data['instance_type'] ?? '';
        $themeId = $data['theme_id'] ?? '';
        if (!$instanceType || !$themeId) {
            $validateRow = $rowNum;
        }
        return $validateRow;
    }
}
