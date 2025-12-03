<?php
/**
 * @category  Fedex
 * @package   Fedex_GraphQl
 * @copyright Copyright (c) 2025 FedEx.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Service;

use Fedex\InStoreConfigurations\Model\System\Config;

class CheckLogEnabledForMutation
{
    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * @param string $mutationName
     * @return bool
     */
    public function execute(string $mutationName = ''): bool
    {
        if (empty($mutationName)) {
            return false;
        }
        $allowedMutations = $this->config->getNewrelicGraphqlMutationsList();
        return in_array($mutationName, $allowedMutations);
    }
}
