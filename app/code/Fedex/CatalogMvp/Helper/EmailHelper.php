<?php
/**
 * Fedex_CatalogMvp
 *
 * @category   Fedex
 * @package    Fedex_CatalogMvp
 * @author     Manish Chaubey
 * @email      manish.chaubey.osv@fedex.com
 * @copyright  © FedEx, Inc. All rights reserved.
 */

 declare(strict_types=1);

namespace Fedex\CatalogMvp\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\CIDPSG\Helper\Email;
use Psr\Log\LoggerInterface;
use Magento\Backend\Helper\Data;
use Magento\Company\Model\CompanyFactory;
use Magento\Customer\Model\CustomerFactory;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Framework\UrlInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class EmailHelper extends AbstractHelper
{
    private const READY_FOR_REVIEW_CTC_EMAIL_TEMPLATE =
        'fedex_catalog_item_ready_for_reviw_ca_email_template';

    private const READY_FOR_REVIEW_CA_EMAIL_TEMPLATE =
        'fedex_catalog_item_ready_for_reviw_email_template';

    private const READY_FOR_ORDER_CA_EMAIL_TEMPLATE =
        'fedex_catalog_item_ready_for_order_email_template';

    private const READY_FOR_REVIEW_CA_EMAIL_TEMPLATE_WITHOUT_PRICE =
        'fedex_catalog_item_ready_for_reviw_email_template_without_price';

    private const READY_FOR_ORDER_CA_EMAIL_TEMPLATE_WITHOUT_PRICE =
        'fedex_catalog_item_ready_for_order_email_template_without_price';

    private const EMAIL_SUBJECT = 'Catalog Item Pending for Review';

    private const EMAIL_SUBJECT_REVIEW = 'Catalog Item Ready for Review';

    private const EMAIL_SUBJECT_ORDER = 'Catalog Item Ready for Order';

    private const EMAIL_SUBJECT_CATALOG_EXPIRATION_NOTIFICATION = 'Catalog Expiring Notifications';

    private const CATALOG_EXPIRATION_NOTIFICATION_EMAIL_TEMPLATE =
        'fedex_catalog_expiry_notification_email_template';

    private const FROM_EMAIL = 'no-reply@fedex.com';

    private const REMOVE_ARRAY_KEYS = ['0', '1','2', '3', '4'];

    private const REMOVE_EXTRA_EXTENSION_PATH = '.html';

    private const IMAGE_PATH = 'wysiwyg/external-link.png';

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customeRepositoryInterface;

    public $status;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param Email $email
     * @param StoreManagerInterface $storeManager
     * @param Data $backendHelper
     * @param CompanyFactory $companyFactory
     * @param CustomerFactory $customerFactory
     * @param SelfReg $selfRegHelper
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param ResourceConnection $resourceConnection
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        Context $context,
        readonly private LoggerInterface $logger,
        readonly private Email $email,
        readonly private StoreManagerInterface $storeManager,
        readonly private Data $backendHelper,
        readonly private CompanyFactory $companyFactory,
        readonly private CustomerFactory $customerFactory,
        readonly private SelfReg $selfRegHelper,
        CustomerRepositoryInterface $customerRepositoryInterface,
        protected ResourceConnection $resourceConnection,
        protected ToggleConfig $toggleConfig
    ) {
        parent::__construct($context);
        $this->customeRepositoryInterface = $customerRepositoryInterface;
    }

    /**
     * Send quote email (Pending review and Change Request Emails)
     *
     * @param array $productData
     * @return mixed
     */
    public function sendReadyForReviewEmail($productData) : mixed
    {
        $adminEmailId = '';
        $loginUrl = $this->backendHelper->getUrl(
            'catalog/product/edit',
            ['id' => $productData['product_id']]
        );
        $urlPart = explode('/', $loginUrl);
        if ($urlPart[5] == "catalog") {
            unset($urlPart[3]);
        }
        $productData['login_url'] = implode('/', $urlPart).'?email=1';
        $folderPath = !empty($productData['folder_path']) ? explode('/', $productData['folder_path']) : [];
        $removeKeys = self::REMOVE_ARRAY_KEYS;
        $folderPath = array_diff_key($folderPath, $removeKeys);
        foreach ($removeKeys as $key) {
            unset($folderPath[$key]);
        }
        $productData['folder_path'] = '/'.str_replace(self::REMOVE_EXTRA_EXTENSION_PATH, '', implode('/', $folderPath));

        $companyId = $productData['company_id'];
        $companyObj = $this->companyFactory->create()
            ->getCollection()
            ->addFieldToFilter('entity_id', $companyId)->getFirstItem();
        $customerEmailId = $productData['customer_email'];
        $toList = null !== $companyObj->getNonStandardCatalogDistributionList() ?
            $companyObj->getNonStandardCatalogDistributionList() : $customerEmailId;
        $productData['to_list'] = $toList;
        $productData['site_name'] = $companyObj->getCompanyName();
        $productData['admin_name'] = $toList;
        $productData['customer_email'] = $customerEmailId ?? '';
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' call send email function for catalog pending Review ');
        $emailData = $this->prepareReadyForReviewRequest($productData);
        $emailData = json_decode((string)$emailData, true);

        return $this->email->sendEmail($emailData);
    }

    /**
     * Send quote email
     *
     * @param array $productData
     * @return mixed
     */
    public function sendReadyForReviewEmailCustomerAdmin($productData) : mixed
    {
        $productData['product_name'] = $productData['product_name'] ?? '';
        $productData['price'] = $productData['product_price'] ?? '';
        $productData['login_url'] = $productData['folder_path'].'?email=1' ?? '';
        $folderPath = !empty($productData['folder_path']) ? explode('/', $productData['folder_path']) : [];
        $removeKeys = self::REMOVE_ARRAY_KEYS;
        $folderPath = array_diff_key($folderPath, $removeKeys);
        foreach ($removeKeys as $key) {
            unset($folderPath[$key]);
        }
        $productData['folder_path'] = '/'.str_replace(self::REMOVE_EXTRA_EXTENSION_PATH, '', implode('/', $folderPath));
        $productData['to_list'] = $productData['customer_email'];
        $productData['admin_name'] = $productData['customer_name'];
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' call send email function for catalog ready for Review ');
        $emailData = $this->prepareReadyForReviewRequestCustomerAdmin($productData);
        $emailData = json_decode((string)$emailData, true);

        return $this->email->sendEmail($emailData);
    }

    /**
     * Send quote email
     *
     * @param array $productData
     * @return mixed
     */
    public function sendReadyForOrderEmailCustomerAdmin($productData) : mixed
    {
        $productData['product_name'] = $productData['product_name'] ?? '';
        $productData['price'] = $productData['product_price'] ?? '';
        $productData['login_url'] = $productData['folder_path'].'?email=1' ?? '';
        $folderPath = !empty($productData['folder_path']) ? explode('/', $productData['folder_path']) : [];
        $removeKeys = self::REMOVE_ARRAY_KEYS;
        $folderPath = array_diff_key($folderPath, $removeKeys);
        foreach ($removeKeys as $key) {
            unset($folderPath[$key]);
        }
        $productData['folder_path'] = '/'.str_replace(self::REMOVE_EXTRA_EXTENSION_PATH, '', implode('/', $folderPath));
        $productData['to_list'] = $productData['customer_email'];
        $productData['admin_name'] = $productData['customer_name'];
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' call send email function for catalog ready for order ');
        $emailData = $this->prepareReadyForOrderRequestCustomerAdmin($productData);
        $emailData = json_decode((string)$emailData, true);

        return $this->email->sendEmail($emailData);
    }

    /**
     * Prepare ready for review email request
     *
     * @param array $productData
     * @return mixed
     */
    private function prepareReadyForReviewRequest($productData) : mixed
    {
        $emailData = $this->buildEmailData($productData);
        $fromEmail = self::FROM_EMAIL;
        $toEmail = $productData['to_list'];
        $storeId = $this->storeManager->getStore()->getId();
        $templateId = $this->getTemplateId();
        $subject = self::EMAIL_SUBJECT;
        $emailTemplateContent = $this->email->loadEmailTemplate($templateId, $storeId, $emailData);

        return json_encode($this->buildEmailPayload($emailTemplateContent, $subject, $toEmail, $fromEmail));
    }

    /**
     * Prepare ready for review email request
     *
     * @param array $productData
     * @return mixed
     */
    private function prepareReadyForReviewRequestCustomerAdmin($productData) : mixed
    {
            $emailData = $this->buildEmailData($productData);
            $fromEmail = self::FROM_EMAIL;
            $toEmail = $productData['to_list'];
            $storeId = $this->storeManager->getStore()->getId();
            $templateId = $this->getTemplateIdCustomerAdmin();
            $subject = self::EMAIL_SUBJECT_REVIEW;
            $emailTemplateContent = $this->email->loadEmailTemplate($templateId, $storeId, $emailData);

            return json_encode($this->buildEmailPayload($emailTemplateContent, $subject, $toEmail, $fromEmail));
    }

    /**
     * Prepare ready for order email request
     *
     * @param array $productData
     * @return mixed
     */
    private function prepareReadyForOrderRequestCustomerAdmin($productData) : mixed
    {
            $emailData = $this->buildEmailData($productData);
            $fromEmail = self::FROM_EMAIL;
            $toEmail = $productData['to_list'];
            $storeId = $this->storeManager->getStore()->getId();
            $templateId = $this->getTemplateIdReadyForOrderCustomerAdmin();
            $subject = self::EMAIL_SUBJECT_ORDER;
            $emailTemplateContent = $this->email->loadEmailTemplate($templateId, $storeId, $emailData);

            return json_encode($this->buildEmailPayload($emailTemplateContent, $subject, $toEmail, $fromEmail));
    }

    /**
     * Build Email Payload
     *
     * @param string $templateContent
     * @param string $subject
     * @param string $toEmail
     * @param string $fromEmail
     * @return array
     */
    private function buildEmailPayload(
        $templateContent,
        $subject,
        $toEmail,
        $fromEmail
        ) : array {
        return [
            'templateData' => $templateContent,
            'templateSubject' => $subject,
            'toEmailId' => $toEmail,
            'fromEmailId' => $fromEmail,
            'retryCount' => 0,
            'errorSupportEmailId' => '',
            'attachment' => ''
        ];
    }

    /**
     * Get Template Id
     *
     * @return string
     */
    public function getTemplateId() : string
    {
        return self::READY_FOR_REVIEW_CTC_EMAIL_TEMPLATE;
    }

    /**
     * Get Template Id
     *
     * @return string
     */
    public function getTemplateIdCustomerAdmin() : string
    {
        if ($this->toggleConfig->getToggleConfigValue('explorers_non_standard_catalog_emails_without_price')) {
            return self::READY_FOR_REVIEW_CA_EMAIL_TEMPLATE_WITHOUT_PRICE;
        }

        return self::READY_FOR_REVIEW_CA_EMAIL_TEMPLATE;
    }

    /**
     * Get Template Id
     *
     * @return string
     */
    public function getTemplateIdReadyForOrderCustomerAdmin() : string
    {
        if ($this->toggleConfig->getToggleConfigValue('explorers_non_standard_catalog_emails_without_price')) {
            return self::READY_FOR_ORDER_CA_EMAIL_TEMPLATE_WITHOUT_PRICE;
        }

        return self::READY_FOR_ORDER_CA_EMAIL_TEMPLATE;
    }

    /**
     * Build email data
     *
     * @param array $productData
     * @return array
     */
    private function buildEmailData($productData) : array
    {
        return [
            'document_name' => $productData['product_name'] ?? '',
            'admin_name' => $productData['admin_name'] ?? '',
            'site_name' => $productData['site_name'] ?? '',
            'item_name' => $productData['item_name'] ?? '',
            'folder_path' => $productData['folder_path'] ?? '',
            'added_by' => $productData['added_by'] ?? '',
            'special_instruction' => $productData['special_instruction'] ?? '',
            'login_url' => $productData['login_url'] ?? '',
            'price' => $productData['price'] ?? '',
            'customer_email' => $productData['customer_email'] ?? ''
        ];
    }

    /**
     * Get special instruction from product instance
     *
     * @param array $proData
     * @return string
     */
    public function getSpecialInstruction(array $proData) : string
    {
        $specialInstruction = '';
        if (isset($proData['properties'])) {
            foreach ($proData['properties'] as $properties) {
                if ($properties['name'] == 'USER_SPECIAL_INSTRUCTIONS') {
                    $specialInstruction = !empty($properties['value']) ? $properties['value'] : '';
                }
            }
        }

        return $specialInstruction;
    }

    /**
     * Send catalog expiration email
     *
     * @param array $twoMonthExpiryCatalogdata
     * @return mixed
     */
    public function sendCatalogExpirationEmail($twoMonthExpiryCatalogdata) : mixed
    {
        $adminInfo = explode(',',$twoMonthExpiryCatalogdata['user_id']);
        $adminName = $adminInfo[0];
        $adminEmailId = $adminInfo[1];
        $companyId = $twoMonthExpiryCatalogdata['company_id'];
        $toList = '';
        $list = [];
        $allowedUserForComany = $this->selfRegHelper->getEmailNotificationAllowUserList($companyId);
        foreach($allowedUserForComany as $allowedUser){
            $list[] = $allowedUser['address'];
        }
        $toList = $adminEmailId .','.implode(',',$list);
        $twoMonthExpiryCatalogdata['admin_name'] = $adminName;
        $twoMonthExpiryCatalogdata['to'] = $toList;
        $emailData = $this->prepareCatalogExpirationRequest($twoMonthExpiryCatalogdata);
        $emailData = json_decode((string)$emailData, true);
        return $this->email->sendEmail($emailData);
    }

    /**
     * Prepare catalog expiration email request
     *
     * @param array $twoMonthExpiryCatalogdata
     * @return mixed
     */
    private function prepareCatalogExpirationRequest($twoMonthExpiryCatalogdata) : mixed
    {
        $emailData = $this->buildEmailDataForCatalogExpiration($twoMonthExpiryCatalogdata);
        $fromEmail = self::FROM_EMAIL;
        $toEmail = $twoMonthExpiryCatalogdata['to'];
        $storeId = $this->storeManager->getStore()->getId();
        $templateId = $this->getTemplateIdCatalogExpirationEmail();
        $subject = self::EMAIL_SUBJECT_CATALOG_EXPIRATION_NOTIFICATION;
        $emailTemplateContent = $this->email->loadEmailTemplate($templateId, $storeId, $emailData);
        return json_encode($this->buildEmailPayload($emailTemplateContent, $subject, $toEmail, $fromEmail));
    }

    /**
     * Get Template Id
     *
     * @return string
     */
    public function getTemplateIdCatalogExpirationEmail() : string
    {
        return self::CATALOG_EXPIRATION_NOTIFICATION_EMAIL_TEMPLATE;
    }

    /**
     * Build email data for catalog expiration
     *
     * @param array $data
     * @return array
     */
    private function buildEmailDataForCatalogExpiration($data) : array
    {
        $image = $this->getMediaUrl().self::IMAGE_PATH;
        return [
            'admin_name' => $data['admin_name'] ?? '',
            'catalog_items' => $data['catalogExpirationData'] ?? null,
            'image' => $image
        ];
    }

    /**
     * get Media URL
     *
     * @return string
     */
    public function getMediaUrl()
    {
        return $this->storeManager->getStore()
        ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, true);
    }

    /**
     * get Media URL
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->storeManager->getStore()
        ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true);
    }

    /**
     * Get Customer Details By ProductId
     *
     * @param int $productId
     * @return array
     */
    public function getCustomerDetails($productId)
    {
        $customerDetails = [];
        $collection = $this->customerFactory->create()->getCollection();
        $catalogProductActivityTable = $this->resourceConnection->getTableName('catalog_product_activity');
        $collection->getSelect()->joinInner(
            ['cpa' => $catalogProductActivityTable],
            'e.entity_id = cpa.user_id'
        )
        ->where('cpa.product_id = ?', $productId)
        ->where('cpa.activity_type = ?', 1);

        $customerObject = $collection->getFirstItem();
        if (!empty($customerObject->getData('email'))) {
            $customerObj = $this->customeRepositoryInterface->getById($customerObject->getId());
            if ($customerObj !== null && !empty($customerObj->getCustomAttribute('secondary_email'))) {
                $customerDetails['customer_email'] = $customerObj->getCustomAttribute('secondary_email')->getValue()
                ? $customerObj->getCustomAttribute('secondary_email')->getValue() : $customerObject->getData('email');
            } else {
                $customerDetails['customer_email'] = !empty($customerObject->getData('email'))
                ? $customerObject->getData('email') : '';
            }
            $firstName = !empty($customerObject->getData('firstname')) ? $customerObject->getData('firstname') : '';
            $lastName = !empty($customerObject->getData('lastname')) ? $customerObject->getData('lastname') : '';

            $customerDetails['customer_name'] = $firstName." ".$lastName;
        }

        return $customerDetails;
    }
}
