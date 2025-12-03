<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Setup\Patch\Data;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CreateCustomBrandedCategory  implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CategoryFactory $categoryFactory
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private CategoryFactory $categoryFactory,
        private CategoryRepositoryInterface $categoryRepository
    )
    {
    }

    /**
     * @throws CouldNotSaveException
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

            $category = $this->categoryFactory->create();
            $category->setName('Custom Branded Boxes & Padded Mailers')
                ->setIsActive(true)
                ->setParentId(2);
            $this->categoryRepository->save($category);

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheirtdoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheirtdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }
}
