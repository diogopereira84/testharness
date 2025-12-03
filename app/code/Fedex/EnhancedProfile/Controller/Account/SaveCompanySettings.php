<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
*/

namespace Fedex\EnhancedProfile\Controller\Account;

use Exception;
use Magento\Framework\App\ActionInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Fedex\Company\Model\AdditionalDataFactory;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\Filesystem;

/**
 * SaveCompanySettings Controller class
 */
class SaveCompanySettings implements ActionInterface
{
 
    /**
     * Initialize dependencies.
     *
     * @param RequestInterface $request
     * @param CompanyRepositoryInterface $companyRepository
     * @param AdditionalDataFactory $additionalDataFactory
     * @param LoggerInterface $logger
     * @param JsonFactory $resultJsonFactory
     * @param Json $json
     * @param UploaderFactory $uploaderFactory
     * @param AdapterFactory $adapterFactory
     * @param Filesystem $filesystem
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly AdditionalDataFactory $additionalDataFactory,
        private readonly LoggerInterface $logger,
        private readonly JsonFactory $resultJsonFactory,
        private readonly Json $json,
        private readonly UploaderFactory $uploaderFactory,
        private readonly AdapterFactory $adapterFactory,
        private readonly Filesystem $filesystem
    ) {
    }
        
    /**
     * Preferences information
     *
     * @return void
     */
    public function execute()
    {
        $requestParams = $this->request->getParams();
        $requestData = $this->extractData($requestParams);
        $companyId = $requestData['company_id'] ? $requestData['company_id'] : null;
        try {
            if ($companyId !== null) {
                $company = $this->companyRepository->get((int) $companyId);
            }
            $company->setData('is_success_email_enable', $requestData['is_success_email_enable']);
            $company->setData('is_delivery', $requestData['is_delivery']);
            $company->setData('is_pickup', $requestData['is_pickup']);
            $company->setData('hc_toggle', $requestData['hc_toggle']);
            if (isset($requestData['allowed_delivery_options'])) {
                $company->setData('allowed_delivery_options', $this->json->serialize($requestData['allowed_delivery_options']));
            }
            $company->setData('allow_own_document', $requestData['allow_own_document']);
            $company->setData('allow_shared_catalog', $requestData['allow_shared_catalog']);
            $company->setData('box_enabled', $requestData['box_enabled']);
            $company->setData('dropbox_enabled', $requestData['dropbox_enabled']);
            $company->setData('google_enabled', $requestData['google_enabled']);
            $company->setData('microsoft_enabled', $requestData['microsoft_enabled']);
            if(isset($_FILES['filepath']['name']) && $_FILES['filepath']['name'] != '') {
                $uploaderFactories = $this->uploaderFactory->create(['fileId' => 'filepath']);
                $uploaderFactories->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
                $imageAdapter = $this->adapterFactory->create();
                $uploaderFactories->addValidateCallback('company_logo',$imageAdapter,'validateUploadFile');
                $uploaderFactories->setAllowRenameFiles(true);
                $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
                $destinationPath = $mediaDirectory->getAbsolutePath('Company/Logo');
                $result = $uploaderFactories->save($destinationPath);                
                $result['previewType'] = "image";
                $result['url'] = "/media/Company/Logo/".$result['file'];
                $company->setData('company_logo', json_encode($result , true));
                if (!$result) {
                    throw new LocalizedException(
                        __('File cannot be saved to path: $1', $destinationPath)
                    );
                }
            }
            $company->save();

            // Reverting changes done by Manish for validation
            $collection = $this->additionalDataFactory->create()
                ->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('company_id', ['eq' => $companyId]);
            if ($collection->getSize()) {
                foreach ($collection as $item) {
                    $item->setIsReorderEnabled($requestData['is_reorder_enabled']);
                    $item->setIsBannerEnable($requestData['is_banner_enable']);
                    $item->setBannerTitle($requestData['banner_title']);
                    $item->setIconography($requestData['iconography']);
                    $item->setDescription($requestData['description']);
                    $item->setCtaText($requestData['cta_text']);
                    $item->setCtaLink($requestData['cta_link']);
                    $item->setLinkOpenInNewTab($requestData['link_open_in_new_tab']);
                    $item->save();
                }
            }

            // Adding condition for validation
            if (
                $requestData['is_banner_enable'] &&
                (empty($requestData['banner_title']) ||
                    empty($requestData['description']) ||
                    empty($requestData['iconography']))
            ) {
                $result = ['error' => true, 'msg' => __('validation')];
                return $this->resultJsonFactory->create()->setData($result);
            }
            
        } catch (LocalizedException $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $result = ['error' => true, 'msg' => $e->getMessage()];

            return $this->resultJsonFactory->create()->setData($result);
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $result = ['error' => true, 'msg' => $e->getMessage()];

            return $this->resultJsonFactory->create()->setData($result);
        }
        $result = ['error'=>false, 'msg'=>'Site Settings have been updated'];
        return $this->resultJsonFactory->create()->setData($result);

    }

    /**
     * Extract Data
     * @param  array $requestParams
     * @return array
     */
    public function extractData($requestParams)
    {
        $requestParams['is_success_email_enable'] = isset($requestParams['is_success_email_enable'])?1:0;
        $requestParams['is_reorder_enabled'] = isset($requestParams['is_reorder_enabled'])?1:0;
        $requestParams['allow_own_document'] = isset($requestParams['allow_own_document'])?1:0;
        $requestParams['allow_shared_catalog'] = isset($requestParams['allow_shared_catalog'])?1:0;
        $requestParams['box_enabled'] = isset($requestParams['box_enabled'])?1:0;
        $requestParams['dropbox_enabled'] = isset($requestParams['dropbox_enabled'])?1:0;
        $requestParams['google_enabled'] = isset($requestParams['google_enabled'])?1:0;
        $requestParams['microsoft_enabled'] = isset($requestParams['microsoft_enabled'])?1:0;
        $requestParams['is_banner_enable'] = isset($requestParams['is_banner_enable'])?1:0;
        $requestParams['link_open_in_new_tab'] = isset($requestParams['link_open_in_new_tab'])?1:0;
        $requestParams['is_delivery'] = isset($requestParams['is_delivery'])?1:0;
        $requestParams['is_pickup'] = isset($requestParams['is_pickup'])?1:0;
        $requestParams['hc_toggle'] = isset($requestParams['hc_toggle'])?1:0;
        $requestParams['banner_title'] = isset($requestParams['banner_title'])?$requestParams['banner_title']:'';
        $requestParams['iconography'] = isset($requestParams['iconography'])?$requestParams['iconography']:'';
        $requestParams['description'] = isset($requestParams['description'])?$requestParams['description']:'';
        $requestParams['cta_text'] = isset($requestParams['cta_text'])?$requestParams['cta_text']:'';
        $requestParams['cta_link'] = isset($requestParams['cta_link'])?$requestParams['cta_link']:'';

        return $requestParams;
    }
}
