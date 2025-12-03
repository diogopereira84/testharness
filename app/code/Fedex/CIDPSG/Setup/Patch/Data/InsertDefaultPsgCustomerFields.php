<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InsertDefaultPsgCustomerFields implements DataPatchInterface
{
    public const PSG_CUSTOMER_FIELD_TABLE = 'psg_customer_fields';
    public const PSG_CUSTOMER_TABLE = 'psg_customer';

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        protected ModuleDataSetupInterface $moduleDataSetup
    )
    {
    }

    /**
     * To insert default value
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $psgCustTblName = $this->moduleDataSetup->getConnection()->getTableName(self::PSG_CUSTOMER_TABLE);

        $psgCustData = [
            'company_participation_id' => 'default',
            'client_id' => 'default',
            'company_name' => 'default',
            'participation_agreement' => 'Participation Agreement text needs to be entered here',
            'support_account_type' => 2,
            'created_by' => 'default',
            'updated_by' => 'default',
        ];

        $this->moduleDataSetup->getConnection()->insert($psgCustTblName, $psgCustData);
        $insertId = $this->moduleDataSetup->getConnection()->lastInsertId($psgCustTblName);

        if ($insertId) {
            $custFieldDataArray = [
                [
                    'psg_customer_entity_id' => $insertId,
                    'field_label' => "Participation code",
                    'field_description' => "Alpha/Numeric code received through company or mail.",
                    'validation_type' => 'text',
                    'is_required' => 1,
                    'position' => 1
                ],
                [
                    'psg_customer_entity_id' => $insertId,
                    'field_label' => "Contract Holder's Company Name",
                    'field_description' => "What company contracted with FedEx Office?",
                    'validation_type' => 'text',
                    'is_required' => 1,
                    'position' => 2
                ],
                [
                    'psg_customer_entity_id' => $insertId,
                    'field_label' => "Participant's company name",
                    'field_description' => "What is your company or business name?",
                    'validation_type' => 'text',
                    'is_required' => 1,
                    'position' => 3
                ],
                [
                    'psg_customer_entity_id' => $insertId,
                    'field_label' => "Company Affiliation",
                    'field_description' => "What is your affiliation with contracted company?",
                    'validation_type' => 'text',
                    'is_required' => 0,
                    'position' => 4
                ],
                [
                    'psg_customer_entity_id' => $insertId,
                    'field_label' => "Participant's first name",
                    'field_description' => "What is your first name?",
                    'validation_type' => 'text',
                    'is_required' => 1,
                    'position' => 5
                ],
                [
                    'psg_customer_entity_id' => $insertId,
                    'field_label' => "Participant's Last Name",
                    'field_description' => "What is your last name?",
                    'validation_type' => 'text',
                    'is_required' => 1,
                    'position' => 6
                ],
                [
                    'psg_customer_entity_id' => $insertId,
                    'field_label' => "Business Email Address",
                    'field_description' => "What is your business email address?",
                    'validation_type' => 'email',
                    'is_required' => 1,
                    'position' => 7
                ],
                [
                    'psg_customer_entity_id' => $insertId,
                    'field_label' => "Business Phone Number",
                    'field_description' => "What is your business phone number?",
                    'validation_type' => 'telephone',
                    'is_required' => 1,
                    'position' => 8
                ],
                [
                    'psg_customer_entity_id' => $insertId,
                    'field_label' => "FedEx Shipping Account",
                    'field_description' => "Enter FedEx Shipping Account Number",
                    'validation_type' => 'fedex_shipping_account',
                    'is_required' => 1,
                    'position' => 9
                ]
            ];

            $psgCustFieldTblName = $this->moduleDataSetup->getConnection()
            ->getTableName(self::PSG_CUSTOMER_FIELD_TABLE);
            $this->moduleDataSetup->getConnection()->insertMultiple($psgCustFieldTblName, $custFieldDataArray);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return $this->getDependencies();
    }

    /**
     * @inheritdoc
     */
    public static function getVersion()
    {
        return '1.0.3';
    }
}
