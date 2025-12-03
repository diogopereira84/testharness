<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Setup\Patch\Data;

use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\Company\Model\Config\Source\CredtiCardOptions;
use Fedex\Company\Model\Config\Source\FedExAccountOptions;
use Fedex\Company\Model\Config\Source\PaymentOptions;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanySearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * @codeCoverageIgnore
 */
class UpdateFedExPaymentOptions implements DataPatchInterface
{
    /**
     * UpdateFedExPaymentOptions constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CompanyRepositoryInterface $companyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Json $json
     * @param AdditionalDataFactory $additionalDataFactory
     * @return void
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private CompanyRepositoryInterface $companyRepository,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private Json $json,
        private AdditionalDataFactory $additionalDataFactory
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        // Delete all records
        $collection = $this->additionalDataFactory->create()->getCollection();
        if ($collection->getSize()) {
            foreach ($collection as $item) {
                $item->delete();
            }
        }
        // Get companies list
        $companies = $this->getCompanyList();
        foreach ($companies as $company) {
            $paymentOption = $company->getPaymentOption();
            // Get newly mapped payment options
            $paymentOption = $this->getUpdatedPaymentOption($paymentOption);
            $paymentType = $paymentOption['type'];
            $paymentMethod = $paymentOption['method'];
            // Save updated payment options
            $additionalDataFactory = $this->additionalDataFactory->create();
            $additionalDataFactory->setCompanyPaymentOptions($this->json->serialize([$paymentOption['method']]));
            if ($paymentMethod == PaymentOptions::FEDEX_ACCOUNT_NUMBER) {
                $additionalDataFactory->setFedexAccountOptions($paymentType);
            } elseif ($paymentMethod == PaymentOptions::CREDIT_CARD) {
                $additionalDataFactory->setCreditcardOptions($paymentType);
            }
            $additionalDataFactory->setCompanyId($company->getId());
            $additionalDataFactory->save();
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
        return [];
    }

    /**
     * Get company collection
     *
     * @return CompanySearchResultsInterface
     */
    private function getCompanyList()
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        return $this->companyRepository->getList($searchCriteria)->getItems();
    }

    /**
     * Get latest payment options mapped with the old options
     *
     * @return array
     */
    private function getUpdatedPaymentOption($paymentOption)
    {
        switch ($paymentOption) {
            case "legacyaccountnumber":
                $newPaymentOption['method'] = PaymentOptions::FEDEX_ACCOUNT_NUMBER;
                $newPaymentOption['type'] = FedExAccountOptions::LEGACY_FEDEX_ACCOUNT;
                break;
            case "sitecreditcard":
                $newPaymentOption['method'] = PaymentOptions::CREDIT_CARD;
                $newPaymentOption['type'] = CredtiCardOptions::LEGACY_SITE_CREDIT_CARD;
                break;
            case "accountnumbers":
                $newPaymentOption['method'] = PaymentOptions::FEDEX_ACCOUNT_NUMBER;
                $newPaymentOption['type'] = FedExAccountOptions::CUSTOM_FEDEX_ACCOUNT;
                break;
            default:
                $newPaymentOption['method'] = '';
                $newPaymentOption['type'] = '';
        }

        return $newPaymentOption;
    }
}
