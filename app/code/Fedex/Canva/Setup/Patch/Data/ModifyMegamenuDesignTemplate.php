<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Setup\Patch\Data;

use Magento\Framework\App\State;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magedelight\Megamenu\Model\Menu\Item;

/**
 * @codeCoverageIgnore
 */
class ModifyMegamenuDesignTemplate implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Item $menuItemsCollection
     * @param State $state
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private Item $menuItemsCollection,
        private State $state
    )
    {
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $this->state->emulateAreaCode(
            \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
            [$this, 'updateMenuItem'],
            [$this->moduleDataSetup]
        );
    }

    /**
     * @param ModuleDataSetupInterface $setup
     */
    public function updateMenuItem(ModuleDataSetupInterface $setup)
    {
        $setup->startSetup();
        $menuCollection = $this->menuItemsCollection->getCollection()
            ->addFieldToFilter('item_name', 'Design Templates')
            ->getItems();
        foreach ($menuCollection as $menu) {
            if ($menu->getItemType() == 'link') {
                $menu->setItemClass('external_link canva_home_button');
                $menu->setItemLink('https://www.fedex.com/apps/ondemand/print-online/templates?beta=true');
                $menu->save();
            }
        }
        $setup->endSetup();
    }

    /**
     * @inheritDoc
     */
    public function revert()
    {
        $this->state->emulateAreaCode(
            \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
            [$this, 'revertMenuItem'],
            [$this->moduleDataSetup]
        );
    }

    /**
     * @param ModuleDataSetupInterface $setup
     */
    public function revertMenuItem(ModuleDataSetupInterface $setup)
    {
        $setup->startSetup();
        $menuCollection = $this->menuItemsCollection->getCollection()
            ->addFieldToFilter('item_name', 'Design Templates')
            ->getItems();

        foreach ($menuCollection as $menu) {
            if ($menu->getItemType() == 'link') {
                $menu->setItemLink('https://www.fedex.com/apps/ondemand/print-online/templates?beta=true');
                $menu->setItemClass('external_link');
                $menu->save();
            }
        }
        $setup->endSetup();
    }
}
