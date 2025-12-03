<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Iago Lima <iago.lima.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class StatusMapping
 */
class MarketplaceImageMapping extends AbstractFieldArray
{
    protected $miraklAttributes;
    protected $imageAttributes;
    protected $yesnoRenderer;

    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'mirakl_attribute',
            [
                'label'    => __('Marketplace Image Attribute'),
                'renderer' => $this->getMarketplaceImageAttributesRenderer(),
                'class' => 'required-entry'
            ]
        );
        $this->addColumn(
            'image_attribute',
            [
                'label'    => __('Image Attribute'),
                'renderer' => $this->getMagentoImageAttributesRenderer(),
                'class' => 'required-entry',
                'extra_params' => 'multiple="multiple"'
            ]
        );
        $this->addColumn(
            'exclude',
            [
                'label'    => __('Exclude from PDP'),
                'renderer' => $this->getExcludeRenderer(),
                'class' => 'required-entry'
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    private function getMarketplaceImageAttributesRenderer()
    {
        if (!$this->miraklAttributes) {
            $this->miraklAttributes = $this->getLayout()->createBlock(
                MiraklAttributesColumn::class
            );
        }

        return $this->miraklAttributes;
    }

    private function getMagentoImageAttributesRenderer()
    {
        if (!$this->imageAttributes) {
            $this->imageAttributes = $this->getLayout()->createBlock(
                ImageAttributesColumn::class
            );
        }

        return $this->imageAttributes;
    }

    private function getExcludeRenderer()
    {
        if (!$this->yesnoRenderer) {
            $this->yesnoRenderer = $this->getLayout()->createBlock(
                YesNoColumn::class
            );
        }

        return $this->yesnoRenderer;
    }
}
