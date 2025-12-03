<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Company\Controller\Adminhtml\Index;

use Magento\Framework\File\Csv;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Fedex\Company\Helper\ExportCompanyData as ExportCompanyDataHelper;

/**
 * Controller for export company.
 */
class Export extends \Magento\Company\Controller\Adminhtml\Index implements HttpGetActionInterface
{
    /**
     * @param Context         $context
     * @param FileFactory     $fileFactory
     * @param Csv             $csvProcessor
     * @param DirectoryList   $directoryList
     * @param LoggerInterface $logger
     * @param PageFactory     $resultPageFactory
     * @param ForwardFactory  $resultForwardFactory
     * @param CompanyRepositoryInterface $companyRepository
     * @param ExportCompanyDataHelper $exportCompanyDataHelper
     */
    public function __construct(
        protected Context $context,
        protected FileFactory $fileFactory,
        protected Csv $csvProcessor,
        protected DirectoryList $directoryList,
        protected LoggerInterface $logger,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        CompanyRepositoryInterface $companyRepository,
        protected ExportCompanyDataHelper $exportCompanyDataHelper
    ) {
        parent::__construct($context, $resultForwardFactory, $resultPageFactory, $companyRepository);
    }

    /**
     * Edit company action.
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $companyId = $this->getRequest()->getParam('id');

        try {
            $company = $this->companyRepository->get($companyId);
            /** Add yout header name here */
            $content[] = [
                'general' => __('General'),
                'ui_customization' => __('UI Customization'),
                'cxml_notification_messages' => __('Cxml Notification Messages'),
                'catalog_and_document_user_settings' => __('Catalog And Document User Settings'),
                'fxo_web_analytics' => __('FXO Web Analytics'),
                'email_notification_options' => __('Email Notification Options'),
                'upload_to_quote' => __('Upload To Quote'),
                'authentication_method' => __('Authentication Method'),
                'order_settings' => __('Order Settings'),
                'payment_methods' => __('Payment Methods'),
                'notification_banner_configuration' => __('Notification Banner Configuration'),
                'delivery_options' => __('Delivery Options'),
                'account_information' => __('Account Information'),
                'legal_address' => __('Legal Address'),
                'company_admin'=> __('Company Admin'),
                'company_credit' => __('Company Credit'),
                'advanced_settings' => __('Advanced Settings'),
                'mvp_catalog_setting' => __('MVP Catalog Setting')
            ];

            $content[] = [
                $this->exportCompanyDataHelper->exportCompanyGeneralTab($company),
                $this->exportCompanyDataHelper->exportUiCustomization($company),
                $this->exportCompanyDataHelper->exportCxmlNotificationTab($company),
                $this->exportCompanyDataHelper->exportCatalogAndDocumentTab($company),
                $this->exportCompanyDataHelper->exportFxoWebAnalyticsTab($company),
                $this->exportCompanyDataHelper->exportEmailNotificationOptionsTab($company),
                $this->exportCompanyDataHelper->exportUploadToQuoteTab($company),
                $this->exportCompanyDataHelper->exportAuthenticationTab($company),
                $this->exportCompanyDataHelper->exportOrderSettingsTab($company),
                $this->exportCompanyDataHelper->exportPaymentMethodsTab($company),
                $this->exportCompanyDataHelper->exportNotificationBannerTab($company),
                $this->exportCompanyDataHelper->exportDeliveryOptionsTab($company),
                $this->exportCompanyDataHelper->exportAccountInformationTab($company),
                $this->exportCompanyDataHelper->exportLegalAddressTab($company),
                $this->exportCompanyDataHelper->exportCompanyAdminTab($company),
                $this->exportCompanyDataHelper->exportCompanyCreditTab($company),
                $this->exportCompanyDataHelper->exportAdvancedSettingsTab($company),
                $this->exportCompanyDataHelper->exportMvpCatalogSettingTab($company)
            ];

            $fileName = $company->getCompanyName().'.csv';
            $filePath = $this->directoryList->getPath(DirectoryList::MEDIA) . "/" . $fileName;
            $this->csvProcessor->setEnclosure('"')->setDelimiter(',')->saveData($filePath, $content);
            $this->fileFactory->create(
                $fileName,
                [
                    'type'  => "filename",
                    'value' => $fileName,
                    'rm'    => true,
                ],
                DirectoryList::MEDIA,
                'text/csv',
                null
            );
        } catch (\Exception $error) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ . ' Error while exporting company data for company id: '
                . $companyId.' is: ' . $error->getMessage()
            );
        }

        return $resultPage;
    }
}
