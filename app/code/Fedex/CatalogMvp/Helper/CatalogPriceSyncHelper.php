<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Helper;

use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\Header\Helper\Data as HeaderData;
use Magento\Company\Model\CompanyFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue\CollectionFactory;
use Magento\Company\Model\ResourceModel\Company\CollectionFactory as CompanyCollectionFactory;
use Magento\Catalog\Api\AttributeSetRepositoryInterface;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\CatalogMvp\Api\WebhookInterface;
use Fedex\Punchout\Helper\Data;
use Magento\Framework\Message\ManagerInterface;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Fedex\Company\Helper\Data as companyHelper;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * CatalogPriceSyncHelper Helper
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CatalogPriceSyncHelper extends AbstractHelper
{
    public const STATUS_PENDING             = 'pending';
    public const STATUS_COMPLETED           = 'completed';
    public const PRINT_ON_DEMAND            = 'PrintOnDemand';
    private const EXPLORERS_NON_STANDARD_CATALOG = 'explorers_non_standard_catalog';
    public const D_177875_CORRECT_PRICE_PRODUCT = 'tech_titans_d_177875_correct_price_product';

    public const TECHTITANS_B_2096706_SHARED_CATALOG_PRICE_SYNC = 'techtitans_B2096706_shared_catalog_price_sync';
    public const MAGEGEEKS_D_236791 = 'magegeeks_d236791';
    public const TECH_TITANS_E_475721 = 'tech_titans_E_475721';

    /**
     * Data Construct
     * @param Context $context
     * @param CatalogSyncQueueFactory $catalogSynchQueueFactory
     * @param ManagerInterface $messageManager
     * @param CollectionFactory $catalogSyncCollectionFactory
     * @param CompanyCollectionFactory $companyCollectionFactory
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $configInterface
     * @param Data $punchoutHelperData
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param FXORate $fxoRateHelper
     * @param WebhookInterface $webhookInterface
     * @param companyHelper $companyHelper
     * @param CatalogMvp $catalogMvpHelper
     * @param HeaderData $headerData
     * @param ToggleConfig $toggleConfig
     * @param DeliveryHelper $deliveryHelper
     * @return void
     */
    public function __construct(
        Context                         $context,
        protected CatalogSyncQueueFactory         $catalogSynchQueueFactory,
        protected ManagerInterface                $messageManager,
        protected CollectionFactory               $catalogSyncCollectionFactory,
        protected CompanyCollectionFactory        $companyCollectionFactory,
        protected LoggerInterface                 $logger,
        protected ScopeConfigInterface            $configInterface,
        protected Data                            $punchoutHelperData,
        protected AttributeSetRepositoryInterface $attributeSetRepository,
        protected FXORate                         $fxoRateHelper,
        protected WebhookInterface                $webhookInterface,
        protected companyHelper                   $companyHelper,
        protected CatalogMvp                      $catalogMvpHelper,
        protected HeaderData                      $headerData,
        protected ToggleConfig                    $toggleConfig,
        protected DeliveryHelper                  $deliveryHelper,
        private readonly CompanyFactory $companyFactory,
    )
    {
        parent::__construct($context);
    }
    /**
     * set price Queue shared catalog.
     *
     * @param int $sharedCatalogId
     * @param int $sharedCatalogCompanyID
     * @param mixed $userName
     * @param mixed $emailId
     * @param mixed $manualSchedule
     * @param mixed $sharedCatalogName
     * @return boolean
     */
    public function createSyncCatalogPriceQueue(
        $sharedCatalogCustomerGroupId,
        $sharedCatalogId,
        $sharedCatalogCategoryId = null,
        $sharedCatalogName = null,
        $userName = 'System',
        $manualSchedule = false,
        $emailId = null
    )
    {
        $sharedCatalogCompanyID = null;
        try {
            $sharedCatalogCompanyID = $this->getCompanyId((int)$sharedCatalogCustomerGroupId);
            $sharedCatPriceSyncToggle = $this->toggleConfig->getToggleConfigValue(self::TECHTITANS_B_2096706_SHARED_CATALOG_PRICE_SYNC);

            if ($sharedCatalogName !== 'null') {
                $sharedCatalogName = str_contains(strtolower($sharedCatalogName), 'shared catalog') ?
                    str_replace('shared catalog', '', strtolower($sharedCatalogName)) :
                    $sharedCatalogName;
            }

            if ($sharedCatPriceSyncToggle && $sharedCatalogCompanyID && $this->isSelfReg($sharedCatalogCompanyID)) {
                $this->createSyncCatalogPriceQueueForSelfReg(
                    $sharedCatalogId,
                    $sharedCatalogCompanyID,
                    $userName,
                    $emailId,
                    $manualSchedule,
                    $sharedCatalogName);

                return $this;
            } elseif (!empty($sharedCatalogCategoryId)) {
                if (!empty($sharedCatalogCompanyID)) {
                    $alreadyInQueue = false;
                    $catalogSynchQueueCollection = $this->catalogSyncCollectionFactory->create();

                    $catalogSyncQueueCollection = $catalogSynchQueueCollection
                        ->addFieldToFilter('status', ['eq' => self::STATUS_PENDING])
                        ->addFieldToFilter('shared_catalog_id', [
                            'eq' => $sharedCatalogId
                        ]);

                    if ($catalogSyncQueueCollection->getSize()) {
                        $alreadyInQueue = true;
                    }
                    // @codeCoverageIgnoreStart
                    if (empty($alreadyInQueue)) {
                        $requestData = [
                            'shared_catalog_id' => $sharedCatalogId,
                        ];
                        $catalogSynchQueue = $this->catalogSynchQueueFactory->create();
                        $catalogSynchQueue->setCompanyId($sharedCatalogCompanyID)
                            ->setSharedCatalogId($sharedCatalogId)
                            ->setStatus(self::STATUS_PENDING)
                            ->setCreatedBy($userName)
                            ->setEmailId($emailId);
                        $catalogSynchQueue->getResource()->save($catalogSynchQueue);
                        $this->webhookInterface->addProductToRM($requestData);
                        if ($manualSchedule) {
                            $this->messageManager->addSuccessMessage(__(
                                $sharedCatalogName . ' shared catalog for price sync added in queue as with pending status.'
                            ));
                        }
                    } else {
                        if ($manualSchedule) {
                            $this->messageManager->addErrorMessage(__(
                                $sharedCatalogName . ' shared catalog price sync already added in queue.'
                            ));
                        }
                    }
                    // @codeCoverageIgnoreEnd
                } else {
                    if ($manualSchedule) {
                        $this->messageManager->addErrorMessage(__(
                            'No company is assigned with ' . $sharedCatalogName . ' shared Catalog.'
                        ));
                    }
                }
            } else {
                if ($manualSchedule) {
                    $this->messageManager->addErrorMessage(__(
                        'No root category is assigned with ' . $sharedCatalogName . ' shared Catalog.'
                    ));
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            if ($manualSchedule) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
    }
    /**
     * set price Queue for selfreg company shared catalog.
     *
     * @param int $sharedCatalogId
     * @param int $sharedCatalogCompanyID
     * @param mixed $userName
     * @param mixed $emailId
     * @param mixed $manualSchedule
     * @param mixed $sharedCatalogName
     * @return boolean
     */
    public function createSyncCatalogPriceQueueForSelfReg(
        $sharedCatalogId,
        int $sharedCatalogCompanyID,
        mixed $userName,
        mixed $emailId,
        mixed $manualSchedule,
        mixed $sharedCatalogName): void
    {
        $alreadyInQueue = false;
        $catalogSynchQueueCollection = $this->catalogSyncCollectionFactory->create();

        $catalogSyncQueueCollection = $catalogSynchQueueCollection
            ->addFieldToFilter('status', ['eq' => self::STATUS_PENDING])
            ->addFieldToFilter('shared_catalog_id', [
                'eq' => $sharedCatalogId
            ]);

        if ($catalogSyncQueueCollection->getSize()) {
            $alreadyInQueue = true;
        }
        // @codeCoverageIgnoreStart
        if (empty($alreadyInQueue)) {
            $requestData = [
                'shared_catalog_id' => $sharedCatalogId,
            ];
            $catalogSynchQueue = $this->catalogSynchQueueFactory->create();
            $catalogSynchQueue->setCompanyId($sharedCatalogCompanyID)
                ->setSharedCatalogId($sharedCatalogId)
                ->setStatus(self::STATUS_PENDING)
                ->setCreatedBy($userName)
                ->setEmailId($emailId);
            $catalogSynchQueue->getResource()->save($catalogSynchQueue);
            $this->webhookInterface->addProductToRM($requestData);
            if ($manualSchedule) {
                $this->messageManager->addSuccessMessage(__(
                    $sharedCatalogName . ' shared catalog for price sync added in queue as with pending status.'
                ));
            }
        } else {
            if ($manualSchedule) {
                $this->messageManager->addErrorMessage(__(
                    $sharedCatalogName . ' shared catalog price sync already added in queue.'
                ));
            }
        }
    }

    /**
     * Get current shared catalog associate company Id.
     *
     * QuequcleanUp
     * @return boolean
     */
    public function quequCleanUp($shareCatalogId)
    {
        $catalogSynchQueueCollection = $this->catalogSyncCollectionFactory->create();
        $catalogSyncQueueCollection = $catalogSynchQueueCollection
            ->addFieldToFilter('status', ['eq' => self::STATUS_PENDING])
            ->addFieldToFilter('shared_catalog_id', [
                'eq' => $shareCatalogId
            ]);

        if ($catalogSyncQueueCollection->getSize()) {
            $catalogSynchQueue = $this->catalogSynchQueueFactory->create();
            $catalogSynchQueue->setId(current($catalogSyncQueueCollection->getData())['id'])
                ->setStatus(self::STATUS_COMPLETED);
            $catalogSynchQueue->getResource()->save($catalogSynchQueue);
        }
    }

    /**
     * Get current shared catalog associate company Id.
     *
     * @param int $customGroupId
     * @return int company Id
     */
    public function getCompanyId(int $customGroupId): ?int
    {
        $companyId = null;
        $companyObj = $this->companyCollectionFactory->create();
        $companyDataList = $companyObj->addFieldToFilter('customer_group_id', ['eq' => $customGroupId]);
        if ($companyDataList->getSize()) {
            $companyId = $companyDataList->getFirstItem()->getId();
        }
        return $companyId;
    }

    /**
     * Validate if current company is SelfReg
     * Get company data
     * @param Int $companyId
     * @return bool
     */
    public function isSelfReg(int $companyId): bool
    {
        $companyData = $this->companyFactory->create()->load($companyId);
        if ($companyData && isset($companyData['storefront_login_method_option']) && $companyData['storefront_login_method_option'] != 'commercial_store_epro') {
            return true;
        }

        return false;
    }

    /**
     * Function to call get Product Price from
     * Rate quote API
     * @param $product
     * @param $sitename
     */
    public function getProductPrice($product, $siteName, $customerGroupId)
    {
        $attributeSetId = $product->getAttributeSetId();
        $attributeSetName = $this->getAttributeSetName($attributeSetId);
        $isPodProductEditAble = $this->catalogMvpHelper->isProductPodEditAbleById($product->getId());
        if ($attributeSetName == SELF::PRINT_ON_DEMAND && $isPodProductEditAble) {
            $externalProd = $product->getExternalProd();
            $externalProd = $externalProd ? json_decode($externalProd, true) : null;
            $externalProdProduct = $externalProd ?? null;
            $productaray[] = $externalProdProduct;

            return $this->rateApiCall($productaray, $siteName, $customerGroupId);
        }
    }

    /**
     * Function to call rate Api to get product price
     *
     * @param $externalProd
     * @param $siteName
     * @return $netAmount
     */
    public function rateApiCall($externalProd, $siteName, $customerGroupId)
    {
        $fedexAccountNumber = null;
        if ($customerGroupId) {
            $companyId = $this->getCompanyId($customerGroupId);
            $fedexAccountNumber = $this->getFedexNdcAccountNumber($companyId);
        }

        // D_177875 if customerGroupId is null, load the data from the company
        if ($this->getCorrectPriceToggle()) {
            if ($customerGroupId == null) {
                $companyObj = $this->deliveryHelper->getAssignedCompany();
                if (!empty($companyObj)) {
                    $companyId = $companyObj->getId();
                    $fedexAccountNumber = $this->getFedexNdcAccountNumber($companyId);
                }
            }
        }

        $rateApiData = [
            'rateRequest' => [
                'fedExAccountNumber' => $fedexAccountNumber,
                'profileAccountId' => null,
                'site' => $siteName,
                'siteName' => null,
                'products' => $externalProd,
                'recipients' => null,
                'loyaltyCode' => null,
                'specialInstructions' => null,
                'coupons' => null,
                'instanceId' => "1690974312938"
            ],
        ];
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        $dataString = json_encode($rateApiData);
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "Content-Length: " . strlen($dataString),
            $authHeaderVal . $this->punchoutHelperData->getAuthGatewayToken(),
            "Cookie: Bearer=" . $this->punchoutHelperData->getTazToken()
        ];
        $setupURL = $this->getRateApiUrl();

        // call rate quote API, passing only headers and datastring.
        $response = $this->fxoRateHelper->callRateApi(
            null,
            null,
            null,
            null,
            $setupURL,
            $headers,
            $dataString,
            null,
            null,
            null,
            true
        );

        if ($this->isMagegeeksD236791ToggleEnabled()) {
            if (is_array($response) && !array_key_exists('errors', $response) && array_key_exists('output', $response)) {
                return $response;
            }
            return false;
        }

        if ($this->toggleConfig->getToggleConfigValue(static::EXPLORERS_NON_STANDARD_CATALOG)) {
            if (is_array($response) && !array_key_exists('errors', $response) && array_key_exists('output', $response)) {
                return $response;
            }
        } else {
            if (is_array($response) && !array_key_exists('errors', $response) && array_key_exists('output', $response)) {
                return $response['output']['rate']['rateDetails'][0]['netAmount'];
            }
        }
        return false;
    }

    /**
     * Function to get getAttributeSetName from attribute id
     * @param  $attributeSetId
     * @return string|null
     */
    public function getAttributeSetName($attributeSetId)
    {
        try {
            $attributeSet = $this->attributeSetRepository->get($attributeSetId);
        } catch (\Exception $exception) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $exception->getMessage());
            return;
        }
        $attributeSetName = trim($attributeSet['attribute_set_name']);
        return $attributeSetName;
    }

    /**
     * Get Rate API URL
     */
    public function getRateApiUrl()
    {
        return $this->configInterface->getValue("fedex/general/rate_api_url");
    }

    /**
     * D-176141 - Fix
     * Get FedEx discount account number getFedexNdcAccountNumber
     */
    public function getFedexNdcAccountNumber($companyId)
    {
        $accountNumber = null;
        if (!empty($companyId)) {
            $company = $this->companyHelper->getCustomerCompany($companyId);

            if ($this->isTechTitansE475721ToggleEnabled()) {
                $accountNumber = trim((string) $company->getFedexAccountNumber()) ?? null;
                if (empty($accountNumber)) {
                    $accountNumber = trim((string) $company->getDiscountAccountNumber()) ?? null;
                }
            } else {
                $accountNumber = trim((string) $company->getDiscountAccountNumber()) ?? null;
                if (empty($accountNumber)) {
                    $accountNumber = trim((string) $company->getFedexAccountNumber()) ?? null;
                }
            }
        }
        return $accountNumber;
    }

    /**
     * D-176141 - D_177875 if customerId is null, load the data from the company Toggle
     */
    public function getCorrectPriceToggle()
    {
        return $this->toggleConfig->getToggleConfigValue(static::D_177875_CORRECT_PRICE_PRODUCT);
    }

    /**
     * Check if Customer Admin
     *
     * @return bool
     */
    public function customerAdminCheck()
    {
        return $this->deliveryHelper->isCustomerAdminUser();
    }

    /**
     * Check if MAGEGEEKS_D_236791 toggle is enabled
     *
     * @return bool
     */
    public function isMagegeeksD236791ToggleEnabled(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::MAGEGEEKS_D_236791);
    }

    /**
     * Check if TECH_TITANS_E_475721 toggle is enabled
     *
     * @return bool
     */
    public function isTechTitansE475721ToggleEnabled(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_E_475721);
    }
}
