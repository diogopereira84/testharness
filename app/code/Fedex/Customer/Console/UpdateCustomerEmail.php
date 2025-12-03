<?php

/**
 * Fedex_Customer
 *
 * @category   Fedex
 * @package    Fedex_Customer
 * @author     Manish Chaubey
 * @email      manish.chaubey.osv@fedex.com
 * @copyright  © FedEx, Inc. All rights reserved.
 */

declare (strict_types = 1);

namespace Fedex\Customer\Console;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Model\CompanyFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * UpdateCustomerEmail class
 */
class UpdateCustomerEmail extends Command
{
    /**
     * Constructor function
     *
     * @param CustomerFactory $customerFactory
     * @param CustomerResource $customerResource
     * @param State $state
     * @param CompanyFactory $companyFactory
     */
    public function __construct(
        readonly private CustomerFactory $customerFactory,
        readonly private CustomerResource $customerResource,
        readonly private State $state,
        readonly private CompanyFactory $companyFactory,
        readonly private ResourceConnection $resourceConnection
    ) {
        parent::__construct();
    }

    /**
     * Configure function
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('fedex:customer:fix-company-admin-email')
            ->setDescription('Update company admin customer email to secondary email and external ID to email.')
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_REQUIRED,
                'Limit',
            );

        parent::configure();
    }

    /**
     * Execute function
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_GLOBAL);
        $limit = (int) $input->getOption('limit');

        $updatedRecordsCount = 0;

        $customerTable = $this->resourceConnection->getTableName('customer_entity');

        $companyCollection = $this->companyFactory->create()->getCollection()
                    ->addFieldToSelect(['storefront_login_method_option', 'super_user_id'])
                    ->addFieldToFilter('storefront_login_method_option', 'commercial_store_wlgn');
        $companyCollection->getSelect()->join(
            $customerTable . ' as customer',
            'main_table.super_user_id = customer.entity_id',
            array('customer.entity_id as customer_id', 'customer.email', 'customer.external_id')
        );
        
        $companyCollection->getSelect()->where('customer.external_id is null or customer.email != customer.external_id');
        $companyCollection->getSelect()->limit($limit);
        
        $superAdminUserId = null;
        foreach ($companyCollection as $companyObj) {
            $superAdminUserId = null != $companyObj ? $companyObj->getSuperUserId() : null;

            if ($superAdminUserId) {

                $externalId = $companyObj->getExternalId();
                $email = $companyObj->getEmail();
                $customerId = $companyObj->getCustomerId();

                $customer = $this->customerFactory->create()->load($customerId);
                if (!$externalId) {
                    // then check if uuid available
                    if (!empty($customer->getCustomerUuidValue())) {
                        $externalId = $customer->getCustomerUuidValue() . '@fedex.com';
                    } else {
                        continue;
                    }
                }

                if ($email == $externalId) {
                    continue;
                }
                
                // Perform updates only if the current customer is the super user
                // Update email field to secondary email if it is different
                $secondaryEmail = $customer->getData('secondary_email');

                $output->writeln('Existing Secondary email found for customer id: ' .
                    $customerId . ' is: ' . $secondaryEmail);
                
                try {
                    $customer->setSecondaryEmail($email);
                    $customer->setEmail($externalId);
                    $this->customerResource->save($customer);

                    $output->writeln('Secondary email was updated for customer id: ' . $customerId . ' with Email: ' . $email);
                    $output->writeln('External id ' . $externalId . ' was updated in email ' . $email . ' for customer: ' . $customerId);
                    $updatedRecordsCount++;
                } catch (\Exception $e) {
                    $output->writeln("Failed to update data for customer {$customerId} : " . $e->getMessage());
                }
                
            }
        }
        $output->writeln("Total {$updatedRecordsCount} records updated for secondary email.");
        
        return Command::SUCCESS;
    }
}
