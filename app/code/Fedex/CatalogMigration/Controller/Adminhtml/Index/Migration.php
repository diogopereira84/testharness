<?php
/**
 * Fedex_CatalogMigration
 *
 * @category   Fedex
 * @package    Fedex_CatalogMigration
 * @author     Bhairav Singh
 * @email      bhairav,singh.osv@fedex.com
 * @copyright  © FedEx, Inc. All rights reserved.
 */

namespace Fedex\CatalogMigration\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem\Driver\File;
use Fedex\CatalogMigration\Helper\CatalogMigrationHelper;
use Magento\Framework\App\Request\Http;

/**
 * Migration Controller Class
 *
 * @method object excute()
 */
class Migration implements ActionInterface
{
    /**
     * Migratation Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param File $driverInterface
     * @param CatalogMigrationHelper $catalogMigrationHelper
     * @param Http $request
     * @return void
     */
    public function __construct(
        protected Context $context,
        protected JsonFactory $resultJsonFactory,
        private File $driverInterface,
        private CatalogMigrationHelper $catalogMigrationHelper,
        private Http $request
    )
    {
    }

    /**
     * Execute controller action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $catalogMigrationFile = $this->context->getRequest()->getFiles('file');
        $compId = $this->context->getRequest()->getParam('comp_id');
        $sharedCatId = $this->context->getRequest()->getParam('shared_cat_id');
        $extUrl = $this->context->getRequest()->getParam('ext_url');
        $catalogMigrationData = $this->driverInterface->fileOpen($catalogMigrationFile['tmp_name'], 'r');
        $datas = [];

        while (($row = $this->driverInterface->fileGetCsv($catalogMigrationData, 100000)) !== false) {
            $datas[] = $row;
        }

        // Row each mandatroy coloumn validation & company invalid url
        $sheetValidatioResponse = $this->catalogMigrationHelper
            ->validateSheetData($datas, $compId, $sharedCatId, $extUrl);

        return $this->resultJsonFactory->create()->setData($sheetValidatioResponse);
    }
}
