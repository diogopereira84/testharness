<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\InBranch\Setup\Patch\Data;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RemoveInBranchToggle implements DataPatchInterface
{
    const INBRANCH_TOGGLE = "environment_toggle_configuration/environment_toggle/tigerteam_in_branch_document_flow_epro";

    public function __construct(
        private SchemaSetupInterface $schemaSetup
    )
    {
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $installer = $this->schemaSetup;
        $installer->startSetup();
        $installer->getConnection()->query("DELETE  FROM core_config_data where path='".self::INBRANCH_TOGGLE."'");
        $installer->endSetup();
    }
}
