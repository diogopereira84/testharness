<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedDetails\Controller\Order;

use Fedex\SharedDetails\Api\PickupStoreEmailResolverInterface;
use Magento\Framework\App\Action\Context;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Sales\Api\OrderRepositoryInterface;
use Fedex\SharedDetails\Helper\CommercialReportHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class GenerateReport extends \Magento\Framework\App\Action\Action implements HttpGetActionInterface
{
    public const PRINT_ON_DEMAND = 'PrintOnDemand';
    public const FXO_PRINT_PRODUCTS = 'FXOPrintProducts';
    public const CUSTOM_BILLING_FIELDS = 'customBillingFields';
    public const CAS_NOTES_FIELDS = 'casNotesFields';
    public const FIELD_NAME = 'fieldName';
    public const VALUE = 'value';
    public const CREDIT_CARD = 'Credit Card';
    public const FEDEX_ACCOUNT = 'FedEx account';
    public const TOGGLE_ADD_STORE_EMAIL_IN_REPORT = 'tiger_b2244879';

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected $orders;
    private Filesystem\Directory\WriteInterface|null $directory;

    /**
     * @param Context $context
     * @param CompanyRepositoryInterface $companyRepository
     * @param CollectionFactoryInterface $orderCollectionFactory
     * @param SessionFactory $customerSession
     * @param LoggerInterface $logger
     * @param PageFactory $resultPageFactory
     * @param Filesystem $filesystem
     * @param OrderRepositoryInterface $orderRepository
     * @param CommercialReportHelper $commercialReportHelper
     * @param PickupStoreEmailResolverInterface $storeEmailResolver
     * @param ToggleConfig $toggleConfig
     * @throws FileSystemException
     */
    public function __construct(
        Context $context,
        private CompanyRepositoryInterface $companyRepository,
        private CollectionFactoryInterface $orderCollectionFactory,
        private SessionFactory $customerSession,
        private LoggerInterface $logger,
        private PageFactory     $resultPageFactory,
        Filesystem $filesystem,
        private OrderRepositoryInterface $orderRepository,
        private CommercialReportHelper $commercialReportHelper,
        private PickupStoreEmailResolverInterface $storeEmailResolver,
        private ToggleConfig $toggleConfig
    ) {
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        parent::__construct($context);
    }

    /**
     * Retrieve Headers row array for Export
     * @return string[]
     */
    public function _getExportHeaders()
    {
        return [
            'Date' => __('Date'),
            'Company Name' => __('Company Name'),
            'Customer Last Name' => __('Customer Last Name'),
            'Customer First Name' => __('Customer First Name'),
            'Project' => __('Project'),
            'Project Source' => __('Project Source'),
            'Copies' => __('Copies'),
            'Unit Price' => __('Unit Price'),
            'Shipping' => __('Shipping'),
            'Tax' => __('Tax'),
            'Total Price' => __('Total Price'),
            'Payment Type' => __('Payment Type'),
            'Delivery' => __('Delivery'),
            'Branch ID'=> __('Branch ID'),
            'Store Email'=> __('Store Email'),
            'GTN' => __('GTN'),
            'Customer Phone' => __('Customer Phone'),
            'Email' => __('Email'),
            'Recipient First Name' => __('Recipient First Name'),
            'Recipient Last Name' => __('Recipient Last Name'),
            'Recipient Phone' => __('Recipient Phone'),
            'Recipient Address1' => __('Recipient Address1'),
            'Recipient Address2' => __('Recipient Address2'),
            'Recipient City' => __('Recipient City'),
            'Recipient State' => __('Recipient State'),
            'Recipient Zip Code' => __('Recipient Zip Code'),
            'Recipient Country' => __('Recipient Country'),
            'Credit Card PO #' => __('Credit Card PO #'),
            'Custom Billing Name 1' => __('Custom Billing Name 1'),
            'Custom Billing Field 1' => __('Custom Billing Field 1'),
            'Custom Billing Name 2' => __('Custom Billing Name 2'),
            'Custom Billing Field 2' => __('Custom Billing Field 2'),
            'Custom Billing Name 3' => __('Custom Billing Name 3'),
            'Custom Billing Field 3' => __('Custom Billing Field 3'),
            'Custom Billing Name 4' => __('Custom Billing Name 4'),
            'Custom Billing Field 4' => __('Custom Billing Field 4'),
            'Custom Billing Name 5' => __('Custom Billing Name 5'),
            'Custom Billing Field 5' => __('Custom Billing Field 5'),
            'CAS PO Number' => __('CAS PO Number'),
            'CAS Order Note Name 1' => __('CAS Order Note Name 1'),
            'CAS Order Note Field 1' => __('CAS Order Note Field 1'),
            'CAS Order Note Name 2' => __('CAS Order Note Name 2'),
            'CAS Order Note Field 2' => __('CAS Order Note Field 2'),
            'CAS Order Note Name 3' => __('CAS Order Note Name 3'),
            'CAS Order Note Field 3' => __('CAS Order Note Field 3'),
            'CAS Order Note Name 4' => __('CAS Order Note Name 4'),
            'CAS Order Note Field 4' => __('CAS Order Note Field 4'),
            'CAS Order Note Name 5' => __('CAS Order Note Name 5'),
            'CAS Order Note Field 5' => __('CAS Order Note Field 5'),
        ];
    }

    /**
     * Get a row data of the particular columns
     * @param array $orderItem
     * @return string[]
     */
    public function getRowRecord($orderItem)
    {
        $order = $this->orderRepository->get($orderItem['order_id']);

        $customerSession = $this->customerSession->create();
        $companyId = $customerSession->getCustomerCompany();

        $companyData = $this->companyRepository->get($companyId);
        $companyName = $companyData->getCompanyName();
        $shippingAmt = $order->getShippingAmount();
        $gtn = $order->getIncrementId();
        $delivery = !empty($order->getEstimatedPickupTime()) ?
            'Pick Up at FedEx Office' : $order->getShippingDescription();
        $orderBillingAddress = $order->getBillingAddress();
        $orderShippingAddress = $order->getShippingAddress();
        $street = $orderShippingAddress->getStreet();
        $paymentType = $this->getPaymentType($order);
        $projectDetails = $this->getProjectDetail($orderItem);

        //get custom billing fields data.
        $orderBillingFields = $this->getBillingFieldsData($paymentType, $order->getBillingFields());

        return [
            date("d M Y", strtotime($order->getCreatedAt())),
            $companyName,
            $orderBillingAddress->getLastname(),
            $orderBillingAddress->getFirstname(),
            !empty($projectDetails['name']) ? $projectDetails['name'] : '',
            !empty($projectDetails['source']) ? $projectDetails['source'] : '',
            $orderItem['qty_ordered'],
            round($orderItem['price'] - ($orderItem['discount_amount'] / $orderItem['qty_ordered']), 2),
            $shippingAmt,
            $order->getCustomTaxAmount(),
            $orderItem['row_total'] - $orderItem['discount_amount'],
            $paymentType,
            $delivery,
            $this->commercialReportHelper->getBranchId($orderItem['order_id']),
            $orderItem['store_email'] ?? '',
            ('`').$gtn,
            $orderBillingAddress->getTelephone(),
            $orderBillingAddress->getEmail(),
            $orderShippingAddress->getFirstname(),
            $orderShippingAddress->getLastname(),
            $orderShippingAddress->getTelephone(),
            isset($street[0]) ? $street[0] : '',
            isset($street[1]) ? $street[1] : '',
            $orderShippingAddress->getCity(),
            $orderShippingAddress->getRegion(),
            $orderShippingAddress->getPostcode(),
            $orderShippingAddress->getCountryId(),
            $orderBillingFields[static::CUSTOM_BILLING_FIELDS][0][static::VALUE] ?? null,
            $orderBillingFields[static::CUSTOM_BILLING_FIELDS][0][static::FIELD_NAME] ?? null,
            $orderBillingFields[static::CUSTOM_BILLING_FIELDS][0][static::VALUE] ?? null,
            $orderBillingFields[static::CUSTOM_BILLING_FIELDS][1][static::FIELD_NAME] ?? null,
            $orderBillingFields[static::CUSTOM_BILLING_FIELDS][1][static::VALUE] ?? null,
            $orderBillingFields[static::CUSTOM_BILLING_FIELDS][2][static::FIELD_NAME] ?? null,
            $orderBillingFields[static::CUSTOM_BILLING_FIELDS][2][static::VALUE] ?? null,
            $orderBillingFields[static::CUSTOM_BILLING_FIELDS][3][static::FIELD_NAME] ?? null,
            $orderBillingFields[static::CUSTOM_BILLING_FIELDS][3][static::VALUE] ?? null,
            $orderBillingFields[static::CUSTOM_BILLING_FIELDS][4][static::FIELD_NAME] ?? null,
            $orderBillingFields[static::CUSTOM_BILLING_FIELDS][4][static::VALUE] ?? null,
            $orderBillingFields[static::CAS_NOTES_FIELDS][0][static::VALUE] ?? null,
            $orderBillingFields[static::CAS_NOTES_FIELDS][0][static::FIELD_NAME] ?? null,
            $orderBillingFields[static::CAS_NOTES_FIELDS][0][static::VALUE] ?? null,
            $orderBillingFields[static::CAS_NOTES_FIELDS][1][static::FIELD_NAME] ?? null,
            $orderBillingFields[static::CAS_NOTES_FIELDS][1][static::VALUE] ?? null,
            $orderBillingFields[static::CAS_NOTES_FIELDS][2][static::FIELD_NAME] ?? null,
            $orderBillingFields[static::CAS_NOTES_FIELDS][2][static::VALUE] ?? null,
            $orderBillingFields[static::CAS_NOTES_FIELDS][3][static::FIELD_NAME] ?? null,
            $orderBillingFields[static::CAS_NOTES_FIELDS][3][static::VALUE] ?? null,
            $orderBillingFields[static::CAS_NOTES_FIELDS][4][static::FIELD_NAME] ?? null,
            $orderBillingFields[static::CAS_NOTES_FIELDS][4][static::VALUE] ?? null,
        ];
    }

    /**
     * Generate Report Execute action.
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\PageFactory $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $dateRange = $this->getRequest()->getParam('dateRange');
        $emailData = $this->getRequest()->getParam('emailData');

        try {
            $customerSession = $this->customerSession->create();
            $companyId = $customerSession->getCustomerCompany();
            $customerGroupId = $customerSession->getCustomer()->getGroupId();
            $customerEmail = $customerSession->getCustomer()->getSecondaryEmail();

            if (!empty($emailData)) {
                $emailData = $customerEmail .','. $emailData;
            } else {
                $emailData = $customerEmail;
            }

            $ordersCollection = $this->orderCollectionFactory->create();
            $ordersCollection->addFieldToSelect('*');
            $ordersCollection->getSelect()->join(
                'company_order_entity',
                'main_table.entity_id = company_order_entity.order_id',
                []
            )->where("company_order_entity.company_id= ?", $companyId);

            // Initialize dates to 1 month by default
            $fromDate = date("Y-m-d H:i:s", strtotime("-1 month"));
            $toDate = date("Y-m-d H:i:s");
            if (!empty($dateRange)) {
                $dateRangeArray = explode("-", $dateRange);
                $startDate = trim($dateRangeArray[0]);
                $endDate = trim($dateRangeArray[1]);
                $fromDate = date('Y-m-d', strtotime($startDate)) .' 00:00:00';
                $toDate = date('Y-m-d', strtotime($endDate)) .' 23:59:59';
            }
            $ordersCollection->addFieldToFilter('main_table.created_at',
                    [
                        'from' => $fromDate,
                        'to' => $toDate
                    ]
            );
            $this->orders = $ordersCollection;
            $this->orders->setOrder('created_at', 'DESC');
            if ($this->toggleConfig->getToggleConfigValue(self::TOGGLE_ADD_STORE_EMAIL_IN_REPORT)) {
                $orderIds = [];
                foreach ($this->orders as $order) {
                    $orderIds[] = $order->getEntityId();
                }
                $storeEmails = $this->storeEmailResolver->getStoreEmailsByOrderIds($orderIds);
            }
            $itemObj = new \ArrayObject();
            foreach ($this->orders as $order) {
                $orderId = $order->getEntityId();
                $orderItems = $order->getAllItems();
                foreach ($orderItems as $orderItem) {
                    $itemData = $orderItem->getData();
                    if ($this->toggleConfig->getToggleConfigValue(self::TOGGLE_ADD_STORE_EMAIL_IN_REPORT)) {
                        if (isset($storeEmails[$orderId])) {
                            $itemData['store_email'] = $storeEmails[$orderId];
                        }
                    }
                    $itemObj->append($itemData);
                }
            }
            $itemIteratorObj = $itemObj->getIterator();

            $convert = new \Magento\Framework\Convert\Excel($itemIteratorObj, [$this, 'getRowRecord']);
            $convert->setDataHeader($this->_getExportHeaders());

            $this->directory->create('export');
            $time = microtime();
            $fileName = 'export/commercial_report_' . $companyId . '_' . $time . '.xls';
            $stream = $this->directory->openFile($fileName, 'w+');
            $stream->lock();
            $convert->write($stream, 'Sheet1');
            $stream->unlock();
            $stream->close();

            // Send Email and remove files
            if ($fileName) {
                $this->commercialReportHelper->sendEmail($fileName, $emailData);
            }

            return $resultPage;
        } catch (\Exception $error) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ . ' Error while exporting order data for company id:'.
                $companyId .' is: ' . $error->getMessage()
            );
        }

        return $resultPage;
    }

    /**
     * Get Project Detail
     * @param object $orderItem
     * @return empty|array
     */
    public function getProjectDetail($orderItem)
    {
        $productId = $orderItem['product_id'];
        $productOptionData = $orderItem['product_options'];
        $attributeSetName = $this->commercialReportHelper->getAttributeSet($productId);

        $projectDetails = [];
        if ($attributeSetName == static::PRINT_ON_DEMAND) {
            $projectDetails['name'] = $orderItem['name'];
            $projectDetails['source'] = 'Catalog';
        } elseif ($attributeSetName == static::FXO_PRINT_PRODUCTS) {
            if (
                isset($productOptionData['info_buyRequest']) &&
                isset($productOptionData['info_buyRequest']['external_prod'])
            ) {
                $externalProdData = $productOptionData['info_buyRequest']['external_prod'];

                $productName = null;
                foreach ($externalProdData as $prodData) {
                    if (isset($prodData['userProductName'])) {
                        $productName = $prodData['userProductName'];
                    }
                }
                $projectDetails['name'] = $productName;
                $projectDetails['source'] = 'Send & Print';
            }
        }

        return $projectDetails;
    }

    /**
     * Get Payment Type
     * @param object $order
     * @return null|string
     */
    public function getPaymentType($order)
    {
        $paymentType = null;
        if ($order->getPayment()->getMethod() == 'fedexccpay') {
            $paymentType = static::CREDIT_CARD;
        } elseif ($order->getPayment()->getMethod() == 'fedexaccount') {
            $paymentType = static::FEDEX_ACCOUNT;
        }

        return $paymentType;
    }

    /**
     * Get Order Billing Field Data
     * @param string $paymentType
     * @param string $billingFields
     * @return array[]
     */
    public function getBillingFieldsData($paymentType, $billingFields)
    {
        $orderBillingFields = [];
        if (isset($billingFields)) {
            $billingFieldsArray = json_decode($billingFields, true);
            if (isset($billingFieldsArray['totalRecords'])) {
                $count = 0;
                foreach ($billingFieldsArray['items'] as $billingField) {
                    if ($paymentType == static::CREDIT_CARD) {
                        $orderBillingFields[static::CUSTOM_BILLING_FIELDS]
                            [$count][static::FIELD_NAME] = $billingField[static::FIELD_NAME];
                        $orderBillingFields[static::CUSTOM_BILLING_FIELDS]
                            [$count][static::VALUE] = $billingField[static::VALUE];
                    } elseif ($paymentType == static::FEDEX_ACCOUNT) {
                        $orderBillingFields[static::CAS_NOTES_FIELDS]
                            [$count][static::FIELD_NAME] = $billingField[static::FIELD_NAME];
                        $orderBillingFields[static::CAS_NOTES_FIELDS][$count]
                            [static::VALUE] = $billingField[static::VALUE];
                    }
                    $count++;
                }
            }
        }

        return $orderBillingFields;
    }
}
