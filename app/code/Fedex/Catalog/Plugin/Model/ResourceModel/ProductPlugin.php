<?php
declare(strict_types=1);
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Bhairav Singh <bhairav.singh.osv@fedex.com>
 * @copyright 2024 Fedex
 */
namespace Fedex\Catalog\Plugin\Model\ResourceModel;

use Fedex\EnvironmentManager\Model\Config\SharedCatalogProductShowingInPrintProducts;
use Fedex\Ondemand\Api\Data\ConfigInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryLinkRepository;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;

class ProductPlugin
{
    /**
     * Constructor
     *
     * @param CategoryRepositoryInterface $categoryRepositoryInterface
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param CategoryLinkRepository $categoryLinkRepository
     * @param SessionManagerInterface $sessionManager
     * @param ConfigInterface $ondemandConfig
     * @param SharedCatalogProductShowingInPrintProducts $catalogItemShowingInPrintProducts
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepositoryInterface,
        protected AttributeSetRepositoryInterface $attributeSetRepository,
        protected CategoryLinkRepository $categoryLinkRepository,
        protected SessionManagerInterface $sessionManager,
        protected ConfigInterface $ondemandConfig,
        private readonly SharedCatalogProductShowingInPrintProducts $catalogItemShowingInPrintProducts,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * @param ProductResource $subject
     * @param $result
     * @param AbstractModel $object
     * @return mixed
     */
    public function afterSave(ProductResource $subject, $result, AbstractModel $object): mixed
    {
        // Skip processing if tech_titans_d_167762 toggle disabled
        if (!$this->catalogItemShowingInPrintProducts->isActive()) {
            return $result;
        }

        try {
            // Retrieve attribute set name by id
            $attributeSetName = $this->getAttributeSetNameById((int)$object->getAttributeSetId());

            // Proceed only if the attribute set name matches "PrintOnDemand"
            if ($attributeSetName !== "PrintOnDemand") {
                return $result;
            }

            // Get category IDs from the object
            $categoryIds = $object->getCategoryIds();
            // Retrieve print products category IDs from session or configuration
            if (!$this->sessionManager->hasPrintProductsCatIds()) {
                $printProductsCatId = $this->ondemandConfig->getB2bPrintProductsCategory();
                $printProductsCatIds = array_unique($this->fetchCategoryIds((int) $printProductsCatId));

                // Store print products category IDs in session
                $this->sessionManager->setPrintProductsCatIds($printProductsCatIds);
            } else {
                $printProductsCatIds = $this->sessionManager->getPrintProductsCatIds();
            }

            // Check if there are any matching categories
            $commonCategoryIds = array_intersect($printProductsCatIds, $categoryIds);
            if (!empty($commonCategoryIds)) {
                // Delete links for each common category ID
                foreach ($commonCategoryIds as $categoryId) {
                    $this->categoryLinkRepository->deleteByIds($categoryId, $object->getSku());
                }
                $this->logger->info(
                    __METHOD__ . ':' . __LINE__ . ' - Un-assigned catalog document from print products categories: ' . implode(', ', $commonCategoryIds)
                );
            }
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ . ' - Un-assigned catalog document from print products category error: ' . $e->getMessage()
            );
        }

        return $result;
    }

    /**
     * Fetches category IDs associated with the given category ID.
     *
     * @param int $categoryId The ID of the category to fetch IDs for.
     * @throws NoSuchEntityException
     * @return array An array containing unique category IDs.
     */
    private function fetchCategoryIds(int $categoryId): array
    {
        $categoryIds = [];
        $categoriesToProcess = [$categoryId];

        while (!empty($categoriesToProcess)) {
            $currentId = array_pop($categoriesToProcess);
            $category = $this->categoryRepositoryInterface->get($currentId);

            if ($category) {
                $categoryIds[] = $category->getId();
                foreach ($category->getChildrenCategories() as $childCategory) {
                    $categoriesToProcess[] = $childCategory->getId();
                }
            }
        }

        return $categoryIds;
    }

    /**
     * Retrieves the attribute set name by its ID.
     *
     * @param int $attributeSetId The ID of the attribute set.
     * @return string|null The name of the attribute set, or null if not found.
     */
    private function getAttributeSetNameById(int $attributeSetId): ?string
    {
        try {
            $attributeSet = $this->attributeSetRepository->get($attributeSetId);
            return $attributeSet->getAttributeSetName();
        } catch (NoSuchEntityException $e) {
            // Handle case where attribute set ID does not exist
            return null;
        }
    }
}
