<?php
/**
 * Fedex_Company
 *
 * @category   Fedex
 * @package    Fedex_Company
 */

namespace Fedex\Company\Controller\Adminhtml\UserPreference;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Fedex\Company\Helper\UserPreferenceHelper;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Request\Http;

/**
 * Import Controller Class
 *
 * @method object excute()
 */
class Import implements ActionInterface
{
    /**
     * Migratation Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param File $driverInterface
     * @param Http $request
     * @param UserPreferenceHelper $userPreferenceHelper
     * @return void
     */
    public function __construct(
        protected Context $context,
        protected JsonFactory $resultJsonFactory,
        private File $driverInterface,
        private Http $request,
        private UserPreferenceHelper $userPreferenceHelper
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
        $userPreferenceFile = $this->context->getRequest()->getFiles('file');
        $compUrlExt = $this->context->getRequest()->getParam('ext_url');
        $userPreferenceData = $this->driverInterface->fileOpen($userPreferenceFile['tmp_name'], 'r');
        $datas = [];
        while (($row = $this->driverInterface->fileGetCsv($userPreferenceData, 100000)) !== false) {
            $datas[] = $row;
        }

        // Row each mandatroy coloumn validation & company url extension
        $sheetValidationResponse = $this->userPreferenceHelper
            ->validateSheetData($datas, $compUrlExt);

        return $this->resultJsonFactory->create()->setData($sheetValidationResponse);
    }
}
