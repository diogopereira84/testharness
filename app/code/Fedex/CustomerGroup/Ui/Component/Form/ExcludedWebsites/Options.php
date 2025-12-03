<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CustomerGroup\Ui\Component\Form\ExcludedWebsites;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Exception\StateException;
use Magento\Store\Model\System\Store;

/**
 * Options tree for "ExcludeWebsites" field
 */
class Options extends AbstractSource
{
    /**
     * @param Store $systemStore
     */
    public function __construct(
        protected Store $systemStore
    )
    {
    }

    /**
     * Retrieve all customer group exclude websites as an options array.
     *
     * @return array
     * @throws StateException
     */
    public function getAllOptions()
    {
        return $this->systemStore->getWebsiteValuesForForm();
    }
}
