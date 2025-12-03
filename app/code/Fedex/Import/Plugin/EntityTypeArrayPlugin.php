<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Plugin;

use Magento\ImportExport\Model\Import\ConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\ImportExport\Model\Source\Import\Entity;

/**
 * Plugin Class EntityTypeArrayPlugin
 */
class EntityTypeArrayPlugin extends Entity
{
    /**
     * EntityTypeArrayPlugin constructor.
     * @param ConfigInterface $importConfig
     * @param Http $request
     */
    public function __construct(
        ConfigInterface $importConfig,
        protected Http $request
    ) {
        parent::__construct($importConfig);
    }

    /**
     * Around plugin for toOptionArray method
     *
     * @param Entity $subject
     * @param callable $proceed
     * @return array
     */
    public function aroundToOptionArray($subject, $proceed)
    {
        $sOptions = [];
        $sOptions[] = ['label' => __('-- Please Select --'), 'value' => ''];
        $options = [];
        $result = $proceed();
        //@codeCoverageIgnoreStart
        foreach ($result as $entityConfig) {
            if (strpos($entityConfig['value'], 'catalog_product') !== false) {
                $sOptions[] = $entityConfig;
            } else {
                $options[] = $entityConfig;
            }
        }
        //@codeCoverageIgnoreEnd
        if ($this->request->getRouteName() === 'import') {
            return $sOptions;
        } else {
            return $options;
        }
    }
}
