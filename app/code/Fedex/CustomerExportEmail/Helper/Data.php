<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomerExportEmail\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Ui\Model\BookmarkManagement;
use Magento\User\Model\UserFactory;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Data extends AbstractHelper
{
    public const EXCEL_DIR_PATH = 'customerdata_export';

    public $customerData;
    private Filesystem\Directory\WriteInterface $directory;


    /**
     * GenerateCsvHelper Constructor
     *
     * @param Context $context
     * @param DirectoryList $directoryList
     * @param Filesystem $filesystem
     * @param AdminConfigHelper $adminConfigHelper
     * @param GroupRepositoryInterface $groupRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param CompanyManagementInterface $companyRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param BookmarkManagement $bookmarkManagement
     * @param UserFactory $userFactory
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        Context $context,
        protected DirectoryList $directoryList,
        protected Filesystem $filesystem,
        protected GroupRepositoryInterface $groupRepository,
        private AddressRepositoryInterface $addressRepository,
        private CompanyManagementInterface $companyRepository,
        protected CustomerRepositoryInterface $customerRepository,
        private BookmarkManagement $bookmarkManagement,
        protected UserFactory $userFactory,
        protected WebsiteRepositoryInterface $websiteRepository,
        private ToggleConfig $toggleConfig
    ) {
        parent::__construct($context);
        $this->directory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * Generate csv file
     *
     * @param array $headerDataArray
     * @param array $rowDataArray
     * @param string $fileName
     * @param string $customerDataDir
     * @return string $attachFile
     */
    public function generateExcel($headerDataArray, $rowDataArray, $fileName, $customerDataDir)
    {
        if (!$this->directory->isDirectory($customerDataDir)) {
            $this->directory->create($customerDataDir);
        }

        $filepath = '/' . $customerDataDir . '/' . $fileName;
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();
        $stream->writeCsv($headerDataArray);
        foreach($rowDataArray as $row) {
            $stream->writeCsv($row);
        }

        return $this->directoryList->getPath('media') . $filepath;
    }

    /**
     * Generate Customer data csv
     *
     * @param array $customerData
     * @param array $inActiveColumns
     * @return mixed
     */
    public function generateCustomerDataCsv($customerFormData, $inActiveColumns)
    {
        $counter = 0;

        $headerDataArray = [
                'entity_id' => 'ID',
                'name' => 'Name',
                'email' => 'Email',
                'confirmation' => 'Confirmed email',
                'group_id'=> 'Group',
                'billing_telephone' => 'Phone',
                'billing_postcode' => 'Zip',
                'billing_country_id' => 'Country',
                'billing_region' => 'State/Province',
                'created_at' => 'Customer Since',
                'website_id' => 'Website',
                'created_in' => 'Account Created in',
                'billing_full' => 'Billing Address',
                'shipping_full' => 'Shipping Address',
                'dob' => 'Date of Birth',
                'taxvat' => 'Tax VAT Number',
                'gender' => 'Gender',
                'billing_street' => 'Street Address',
                'billing_city' => 'City',
                'billing_fax' => 'Fax',
                'billing_vat_id' => 'VAT Number',
                'billing_firstname' => 'Billing Firstname',
                'billing_lastname' => 'Billing Lastname',
                'lock_expires' => 'Account Lock',
                'customer_type' => 'Customer Type',
                'company_name' => 'Company',
                'sales_representative_username' => 'Sales Representative',
                'status' => 'Status',
                'customer_status' => 'Customer Status',
                'secondary_email' => 'Secondary Email',
                'fcl_profile_contact_number' => 'FCL Profile Contact Number'
        ];

        $headerDataArray  = $this->removeInactiveColumns($headerDataArray, $inActiveColumns);

        foreach ($customerFormData as $customerData) {

            $groupEntity = $this->groupRepository->getById($customerData['group_id']);

            $groupName = $groupEntity->getCode();

            $addressData = null;
            if ($customerData['default_billing']) {
                $addressData = $this->addressRepository->getById($customerData['default_billing']);
            }

            $company = $this->companyRepository->getByCustomerId($customerData['entity_id']);
            $customerType = 'Retail User';
            $companyName = '';
            if ($company) {
                $customerType = 'Company user';
                $companyName = $company->getCompanyName();
                $salesRep = $this->userFactory->create()->load($company->getSalesRepresentativeId());
                $salesRepEmail = $salesRep->getEmail();
            }

            $region = '';
            $country = '';
            $full_address = '';
            if ($addressData != null) {
                $region = $addressData->getRegion()->getRegion();
                $full_address = $addressData->getStreet()[0].' '.$addressData->getCity().
                ' '.$region.' '.$addressData->getPostcode();
                $country = ($addressData->getCountryId() == 'US') ? 'United States' : 'Canada';
            }

            $customer = $this->customerRepository->getById($customerData['entity_id']);

            $status = $customerData['is_active']?'Active':'Inactive';

            $customerStatus = '';

            if ($customer->getCustomAttribute('customer_status') !== null) {
                if ($customer->getCustomAttribute('customer_status')->getValue() == '3') {
                    $customerStatus = 'Email Verification Pending';
                }elseif ($customer->getCustomAttribute('customer_status')->getValue() == '2') {
                    $customerStatus = 'Pending For Approval';
                } elseif ($customer->getCustomAttribute('customer_status')->getValue() == '1') {
                    $customerStatus = 'Active';
                } else {
                    $customerStatus = 'Inactive';
                }
            }

            $website = $this->websiteRepository->getById($customerData['website_id']);

            $rowDataArray[$counter] = [
                'entity_id' => $customerData['entity_id'],
                'name' => $customerData['firstname'].' '.$customerData['lastname'],
                'email' => $customerData['email'],
                'confirmation' => 'Confirmation Not Required',
                'group_id'=> $groupName,
                'billing_telephone' => $addressData!=null?$addressData->getTelePhone():'',
                'billing_postcode' => $addressData!=null?$addressData->getPostcode():'',
                'billing_country_id' => $country,
                'billing_region' => $region,
                'created_at' => date('M d, Y h:i:s A', strtotime($customerData['created_at'])),
                'website_id' => $website->getName(),
                'created_in' => $customerData['created_in'],
                'billing_full' => $full_address,
                'shipping_full' => $full_address,
                'dob' => $customerData['dob'],
                'taxvat' => $customerData['taxvat'],
                'gender' => $customerData['gender'],
                'billing_street' => $addressData!=null?$addressData->getStreet()[0]:'',
                'billing_city' => $addressData!=null?$addressData->getCity():'',
                'billing_fax' => $addressData!=null?$addressData->getFax():'',
                'billing_vat_id' => $customerData['taxvat'],
                'billing_firstname' => $addressData!=null?$addressData->getFirstname():'',
                'billing_lastname' => $addressData!=null?$addressData->getLastname():'',
                'lock_expires' => 'Unlocked',
                'customer_type' => $customerType,
                'company_name' => $companyName,
                'sales_representative_username' => $this->customerExportIssueFixToggleEnabled() ? ($salesRepEmail ?? null): $salesRepEmail,
                'status' => $status,
                'customer_status' => $customerStatus,
                'secondary_email' => $customer->getCustomAttribute('secondary_email')!==null?$customer
                ->getCustomAttribute('secondary_email')->getValue():'',
                'fcl_profile_contact_number' => $customer->getCustomAttribute('fcl_profile_contact_number')!==null?
                $customer->getCustomAttribute('fcl_profile_contact_number')->getValue():''
            ];

            $rowDataArray[$counter] = $this->removeInactiveColumns($rowDataArray[$counter], $inActiveColumns);

            $counter++;

        }

        $timeZoneUtc = new \DateTime(date("Y-m-d H:i:s"), new \DateTimeZone('UTC'));
        $timeZoneUtc->setTimezone(new \DateTimeZone('CST'));
        $cstTime = $timeZoneUtc->format('Ymd_His');
        $fileName = 'customerdata'. $cstTime . '.csv';

        return $this->generateExcel($headerDataArray, $rowDataArray, $fileName, self::EXCEL_DIR_PATH);
    }

    /**
     * Remove Inactive Columns
     * @param  array $dataArray
     * @param  array $inactivecolumns
     * @return array
     */
    public function removeInactiveColumns($dataArray, $inactivecolumns)
    {
        foreach ($dataArray as $key => $value) {
            if(in_array($key,$inactivecolumns)){
                unset($dataArray[$key]);
            }
        }
        return array_values($dataArray);
    }

    /**
     * Get Inactive admin columns
     * @return array $inActiveColumns
     */
    public function getInActiveColumns()
    {
        $bookmark = $this->bookmarkManagement->getByIdentifierNamespace('current', 'customer_listing');
        $config = $bookmark->getConfig();
        $columns = $config['current']['columns'];
        $inActiveColumns = [];
        foreach ($columns as $column => $config){
            if(false === $config['visible'] && $column != 'ids'){
                $inActiveColumns[] = $column;
            }
        }
        return array_values($inActiveColumns);
    }

    /**
     * Get the toggle configuration value of techtitans_D180778_fix
     * @return bool
     */
    public function customerExportIssueFixToggleEnabled(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue('techtitans_D180778_fix');
    }
}
