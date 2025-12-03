<?php
declare(strict_types = 1);
namespace Fedex\SaaSCommon\Console\Command;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Action;
use Magento\CatalogPermissions\Model\Permission;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\CollectionFactory as CategoryPermissionCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAllowedCustomerGroupsCommand extends Command
{
    const ALLOWED_CUSTOMER_GROUPS_ATTRIBUTE = 'allowed_customer_groups';

    public function __construct(
        protected State $state,
        protected ProductCollectionFactory $productCollectionFactory,
        protected CategoryPermissionCollectionFactory $categoryPermissionCollectionFactory,
        protected ProductRepositoryInterface $productRepository,
        protected Action $productAction
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('fedex:allowed-customer-groups:update')
            ->setDescription('Update AllowedCustomerGroups attribute for all products');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);

        $this->state->setAreaCode('adminhtml');

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToSelect('entity_id');
        $productCollection->addAttributeToSelect('name');
        $productCollection->addAttributeToSelect(self::ALLOWED_CUSTOMER_GROUPS_ATTRIBUTE);

        $count = 0;
        $failures = 0;
        foreach ($productCollection as $product) {
            $categoryIds = $product->getCategoryIds();

            $allowedGroups = $this->getAllowedCustomerGroupsFromCategories($categoryIds);
            $allowedGroupsString = implode(',', $allowedGroups);

            if($product->getData(self::ALLOWED_CUSTOMER_GROUPS_ATTRIBUTE) === $allowedGroupsString) {
                continue;
            }

            try {
                $this->productAction->updateAttributes(
                    [$product->getId()],
                    [self::ALLOWED_CUSTOMER_GROUPS_ATTRIBUTE => $allowedGroupsString],
                    0
                );
            } catch (LocalizedException $e) {
                $output->writeln("<error>Error updating product {$product->getName()} - ID {$product->getId()}: {$e->getMessage()}</error>");
                $failures++;
                continue;
            }

            $output->writeln("<info>Updated AllowedCustomerGroups for {$product->getName()}.</info>");
            $count++;
        }

        $output->writeln("<info>___________________________________________________________</info>");
        $output->writeln("<info>Updated AllowedCustomerGroups for {$count} products.</info>");
        $output->writeln("<info>Failed to update AllowedCustomerGroups for {$failures} products.</info>");

        $endTime = microtime(true);
        $elapsed = $endTime - $startTime;
        $output->writeln(sprintf("<info>Command completed in %.4f seconds.</info>", $elapsed));

        return Cli::RETURN_SUCCESS;
    }

    /**
     * Get allowed customer groups from categories.
     *
     * @param array $categories
     * @return array
     */
    private function getAllowedCustomerGroupsFromCategories(array $categories): array
    {
        if (empty($categories)) {
            return [];
        }

        $permissionsCollection = $this->categoryPermissionCollectionFactory->create()
            ->addFieldToFilter('category_id', ['in' => $categories])
            ->addFieldToFilter('grant_catalog_category_view', Permission::PERMISSION_ALLOW)
            ->addFieldToSelect('customer_group_id');
        $permissionsCollection->getSelect()->group('customer_group_id');

        $customerGroupIds = $permissionsCollection->getColumnValues('customer_group_id');

        if (!empty($customerGroupIds) && in_array(null, $customerGroupIds)) {
            return ['-1']; // '-1' represents all groups
        }

        return $customerGroupIds;
    }
}
