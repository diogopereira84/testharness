<?php

declare(strict_types=1);

namespace Fedex\WebAnalytics\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class DynamicRows
 * @package Fedex\WebAnalytics\Block\Adminhtml
 */
class DynamicRowsStaticValue extends Widget
{
    /**
     * Block-Class to be generated through layout
     * @var string
     */
    protected $blockClassName = Form\Field\Widget\DynamicRowsStaticValue::class;

    /**
     * DynamicRows constructor.
     *
     * @param Context $context
     * @param Json $jsonSerializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        private Json $jsonSerializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Prepare chooser element HTML
     *
     * @param AbstractElement $element Form Element
     * @return AbstractElement
     * @throws LocalizedException
     */
    public function prepareElementHtml(AbstractElement $element)
    {
        $uniqId = $this->mathRandom->getUniqueHash($element->getId());

        $dynamicRows = $this->getLayout()->createBlock(
            $this->blockClassName
        )->setElement(
            $element
        )->setConfig(
            $this->getConfig()
        )->setFieldsetId(
            $this->getFieldsetId()
        )->setUniqId(
            $uniqId
        );

        if ($element->getValue()) {
            $values = $element->getValue();
            $values = array_filter($values);
            $serializer = $this->jsonSerializer;
            $values = array_map(function($entry) use ($serializer){
                return $serializer->unserialize($entry);
            }, $values);
            $element->setValue($values);
        }

        $element->setData('after_element_html', $dynamicRows->toHtml());
        return $element;
    }
}
