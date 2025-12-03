<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Rutvee Sojitra <rsojitra@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\LiveSearchFacets\Plugin\Block\Adminhtml\Product\Attribute\Edit\Tab;

use Closure;
use Magento\Framework\Registry;

/**
 * @inerhitDoc
 */
class Advanced
{
    /**
     * @var Registry
     */
    private Registry $coreRegistry;

    /**
     * @param Registry $registry
     */
    public function __construct(
        Registry $registry
    ) {
        $this->coreRegistry = $registry;
    }

    /**
     * Around plugin to add facet tooltip
     *
     * @param \Magento\Catalog\Block\Adminhtml\Product\Attribute\Edit\Tab\Advanced $subject
     * @param Closure $proceed
     * @return mixed
     */
    public function aroundGetFormHtml(
        \Magento\Catalog\Block\Adminhtml\Product\Attribute\Edit\Tab\Advanced $subject,
        Closure $proceed
    ) {
        $attributeObject = $this->coreRegistry->registry('entity_attribute');
        $form = $subject->getForm();
        $fieldset = $form->getElement('advanced_fieldset');
        $fieldset->addField(
            'facet_tooltip',
            'text',
            [
                'name' => 'facet_tooltip',
                'label' => __('Tooltip'),
                'title' => __('Tooltip'),
            ]
        );
          $form->setValues($attributeObject->getData());
        return $proceed();
    }
}
