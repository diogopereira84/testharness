<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model;

use Fedex\MarketplacePunchout\Api\Data\CustomerPunchoutUniqueIdSearchResultInterface;
use Magento\Framework\Api\SearchResults;

class CustomerPunchoutUniqueIdSearchResult extends SearchResults implements CustomerPunchoutUniqueIdSearchResultInterface
{

}
