<?php
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\CatalogMvp;

use Exception;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Price\TierPricePersistence;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ProductPriceHandler
{
    /** @var string  */
    const ATTRIBUTE_SET_NAME = 'PrintOnDemand';

    /** @var $podAttributeSetId */
    private mixed $podAttributeSetId = null;

    /**
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param TierPricePersistence $tierPricePersistence
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private StoreManagerInterface $storeManager,
        private CustomerSession $customerSession,
        private ProductRepositoryInterface $productRepository,
        private AttributeSetRepositoryInterface $attributeSetRepository,
        private TierPricePersistence $tierPricePersistence,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private FilterBuilder $filterBuilder,
        private LoggerInterface $logger,
        private ToggleConfig $toggleConfig
    ) {
    }

    /**
     * Logic was encapsulated to handle it with a toggle
     *
     * @param $quote
     * @param $rateApiOutputData
     * @return void
     */
    public function handle($quote, $rateApiOutputData): void
    {
        try {
            $productIds = [];
            $index = 0;
            $tierPriceData = [];
            $websiteId =  $this->storeManager->getStore()->getWebsiteId();
            $customerGroupId = $this->customerSession->getCustomer()->getGroupId();
            $arrayKey = $customerGroupId . '-' . $websiteId;
            $productIds = $this->getProductIds($quote, $productIds);
            $qtyOfProductsOnQuote = count($productIds);
            // @codeCoverageIgnoreStart
            if (!($qtyOfProductsOnQuote > 0)) {
                return;
            }
            // @codeCoverageIgnoreEnd
            if (!isset($rateApiOutputData['output']['rateQuote']['rateQuoteDetails'][0]['productLines'])) {
                return;
            }
            foreach ($productIds as $productId) {
                if (!$this->matchProductPODAttributeSetToUpdatePrice($productId)) {
                    $index++;
                    continue;
                }
                $rateQuoteResponse = $rateApiOutputData['output']['rateQuote']['rateQuoteDetails'][0];
                if (!isset($rateQuoteResponse['productLines'][$index]['productLinePrice'])) {
                    $index++;
                    continue;
                }

                if($this->toggleConfig->getToggleConfigValue('tech_titan_d_202382')){
                    $rateQuoteResPrice = round((float)($rateQuoteResponse['productLines'][$index]['productLinePrice'] / $rateQuoteResponse['productLines'][$index]['unitQuantity']), 6);
                } else {
                    $rateQuoteResPrice = round($rateQuoteResponse['productLines'][$index]['productLinePrice'] / $rateQuoteResponse['productLines'][$index]['unitQuantity'], 2);
                }


                $tierPriceData = $this->matchProductTierPriceToUpdate(
                    $productId,
                    $arrayKey,
                    $rateQuoteResPrice,
                    $websiteId,
                    $customerGroupId,
                    $tierPriceData
                );
                $index++;
            }
            if (!empty($tierPriceData)) {
                $this->tierPricePersistence->update($tierPriceData);
            }
        } catch (Exception $error) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                ' System error, while mvp product price update. ' . $error->getMessage().
                ' Price update. ' . json_encode($tierPriceData).
                ' Product ids. ' . json_encode($productIds)
            );
        }
    }

    /**
     * @param mixed $productId
     * @return bool
     * @throws NoSuchEntityException
     */
    private function matchProductPODAttributeSetToUpdatePrice(mixed $productId): bool
    {
        $this->preparePODAttributeSetId();
        $product = $this->productRepository->getById($productId);
        $attributeSetId = $product->getAttributeSetId();
        if ($attributeSetId != $this->podAttributeSetId) {
            return false;
        }
        return true;
    }

    /**
     * @param $quote
     * @param array $productIds
     * @return array
     */
    private function getProductIds($quote, array $productIds): array
    {
        foreach ($quote->getAllVisibleItems() as $item) {
            $productIds[] = $item->getProduct()->getId();
        }
        return $productIds;
    }

    /**
     * @param mixed $productId
     * @param string $arrayKey
     * @param mixed $rateQuoteResPrice
     * @param mixed $websiteId
     * @param mixed $customerGroupId
     * @param array $tierPriceData
     * @return array
     * @throws NoSuchEntityException
     */
    private function matchProductTierPriceToUpdate(
        mixed $productId,
        string $arrayKey,
        mixed $rateQuoteResPrice,
        mixed $websiteId,
        mixed $customerGroupId,
        array $tierPriceData
    ): array {
        /** @var Product $product */
        $product = $this->productRepository->getById($productId);
        $rowId = $product->getRowId();
        $tierPrice = $product->getData('tier_price');
        if ($this->isAbleToUpdatePrice($tierPrice, $arrayKey, $rateQuoteResPrice)) {
            $tierPriceData[$rowId] =
                [
                    'all_groups' => 0,
                    'row_id' => $rowId,
                    'website_id' => $websiteId,
                    'customer_group_id' => $customerGroupId,
                    'qty' => 1,
                    'value' => $rateQuoteResPrice,
                    'percentage_value' => null
                ];
        }
        return $tierPriceData;
    }

    /**
     * @param mixed $tierPrice
     * @param string $arrayKey
     * @param mixed $rateQuoteResPrice
     * @return bool
     */
    private function isAbleToUpdatePrice(mixed $tierPrice, string $arrayKey, mixed $rateQuoteResPrice): bool
    {
        return empty($tierPrice) || (array_key_exists($arrayKey, $tierPrice) && (float)$tierPrice[$arrayKey]['price'] !== (float)$rateQuoteResPrice);
    }

    /**
     * @return void
     */
    private function preparePODAttributeSetId(): void
    {
        if (empty($this->podAttributeSetId)) {
            $filter = $this->filterBuilder
                ->setField('attribute_set_name')
                ->setValue(self::ATTRIBUTE_SET_NAME)
                ->setConditionType('eq')
                ->create();
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilters([$filter])
                ->create();
            $attributes = $this->attributeSetRepository->getList($searchCriteria)->getItems();
            foreach ($attributes as $attribute) {
                $this->podAttributeSetId = $attribute->getId();
            }
        }
    }
}
