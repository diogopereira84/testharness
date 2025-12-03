<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Setup\Patch\Data;

use Fedex\SelfReg\Model\CompanySelfRegData;
use Magento\Company\Model\CompanyFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * @codeCoverageIgnore
 */
class SyncSelfregData implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CompanyRepositoryInterface $companyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TransactionFactory $transactionFactory
     * @param CompanySelfRegData $companySelfRegData
     * @param CompanyFactory $company
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private CompanyRepositoryInterface $companyRepository,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private TransactionFactory $transactionFactory,
        private CompanySelfRegData $companySelfRegData,
        private CompanyFactory $company
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $companySelfRegDataCollection = $this->companySelfRegData->getCollection();
        $transaction = $this->transactionFactory->create();
        foreach ($companySelfRegDataCollection as $companySelfRegData) {
            $company = $this->company->create()->load($companySelfRegData->getCompanyId());
            $company->setData('self_reg_data', $companySelfRegData->getSelfRegData());
            $transaction->addObject($company);
        }
        $transaction->save();
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
        return [];
    }
}
