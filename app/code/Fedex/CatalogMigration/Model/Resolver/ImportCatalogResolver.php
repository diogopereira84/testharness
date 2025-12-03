<?php
/**
 * @category     Fedex
 * @package      Fedex_CatalogMigration
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Brajmohan Rajput <brajmohan.rajput.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CatalogMigration\Model\Resolver;

use Magento\Framework\Exception\InputException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Company\Model\CompanyFactory;
use Fedex\CatalogMigration\Helper\CatalogMigrationHelper;

class ImportCatalogResolver implements ResolverInterface
{
	/**
     * CatalogMigrationResolver Constructor.
	 * @param Filesystem $fileSystem
     * @param File $file
	 * @param CompanyFactory $companyFactory
     * @param CatalogMigrationHelper $catalogMigrationHelper
     */
	public function __construct(
	    protected Filesystem $fileSystem,
	    protected File $file,
	    protected CompanyFactory $companyFactory,
	    protected CatalogMigrationHelper $catalogMigrationHelper
	)
 {
 }

	/**
	 * @inheritdoc
	 */
	public function resolve(
		Field $field,
		$context,
		ResolveInfo $info,
		array $value = null,
		array $args = null
	) {
		if (empty($args['input']) || !is_array($args['input']) || !count($args['input'])) {
			throw new GraphQlInputException(__('You must specify your input.'));
		}

		if (empty($args['input'][0]['company_url_ext'])) {
			throw new GraphQlInputException(__('You must specify your "company url extension".'));
		}

		if (empty($args['input'][0]['base64_encoded_file'])) {
			throw new GraphQlInputException(__('You must specify your "file base64 encode".'));
		}

		try {
			$result = [];
			$datas = [];
			$companyId = null;
			$sharedCatalogId = null;
			$companyUrlExt = $args['input'][0]['company_url_ext'];
			$fileContent = base64_decode($args['input'][0]['base64_encoded_file']);

			$companyObj = $this->companyFactory->create()
				->getCollection()
				->addFieldToFilter('company_url_extention', ['eq' => $companyUrlExt])
				->getFirstItem();

			if ($companyObj->getId()) {
				$companyId = $companyObj->getId();
				$sharedCatalogId = $companyObj->getSharedCatalogId();

				$mediaPath = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
				// csv file name with full path
				$csvFileFullPath = $mediaPath .'import/'.$companyUrlExt;

				// delete csv file if same file already exit in pub/media/import/ directory
				if ($this->file->isExists($csvFileFullPath))  {
					$this->file->deleteFile($csvFileFullPath);
				}

				$catalogMigrationData = $this->file->fileOpen($csvFileFullPath, 'wb');
				$this->file->fileWrite($catalogMigrationData, $fileContent);
				$this->file->fileClose($catalogMigrationData);

				$readCatalogMigrationFile = $this->file->fileOpen($csvFileFullPath, 'r');
				while (($row = $this->file->fileGetCsv($readCatalogMigrationFile, 100000)) !== false) {
					$datas[] = $row;
				}

				// Row each mandatory column validation & company invalid url
				$sheetValidatioResponse = $this->catalogMigrationHelper->validateSheetData(
					$datas,
					$companyId,
					$sharedCatalogId,
					$companyUrlExt
				);

				$result = [
					'status'  => $sheetValidatioResponse['status'],
					'message' => $sheetValidatioResponse['message']
				];

				// delete csv file if exit in pub/media/import/ directory
				if ($this->file->isExists($csvFileFullPath))  {
					$this->file->deleteFile($csvFileFullPath);
				}
			}

			return $result;
		} catch (\Exception $e) {
			throw new GraphQlInputException(__($e->getMessage()));
		}
	}
}
