<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\Quote\IntegrationNote\Command;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface GetListInterface
{
    /**
     * @param SearchCriteriaInterface $criteria
     * @return SearchResultsInterface
     */
    public function execute(SearchCriteriaInterface $criteria): SearchResultsInterface;
}
