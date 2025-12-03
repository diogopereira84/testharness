<?php
/**
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Company\Model\Company\Custom\Billing\Invoiced;

use Exception;

class Collection extends \Magento\Framework\Data\Collection
{
    /**
     * Load data
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws Exception
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        usort($this->_items, function ($a, $b) {
            return ((int)$a->getData('position')) <=> ((int)$b->getData('position'));
        });

        return $this;
    }

    /**
     * Return items as raw array
     *
     * @return array
     */
    public function getItemsArray(): array
    {
        $data = $this->toArray();

        if (isset($data['items'])) {
            return $data['items'];
        }

        return $data;
    }
}
