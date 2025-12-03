<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\UploadToggleCsv\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Escaper;
use Magento\Backend\Block\Template\Context;

class File extends Field
{
    /**
     * @param Context $context
     * @param Escaper $escaper
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected Escaper $escaper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }
    /**
     * Render the HTML of the form field
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $elementId = $element->getHtmlId();

        $html = '<input type="file"'
            . ' id="' . $this->escaper->escapeHtmlAttr($elementId) . '"'
            . ' name="' . $this->escaper->escapeHtmlAttr($element->getName()) . '"'
            . ' accept=".csv" />';

        $html .= '<div style="margin-top: 10px;">';

        $html .= '<div style="margin-bottom: 10px;">';

        $html .= '<button type="button" style="display: inline-block; margin-right: 10px;" '
            . 'onclick="applyCsv()">'
            . $this->escaper->escapeHtml(__('Apply List'))
            . '</button>';
        $html .= '<button type="button" style="display: inline-block;" '
            . 'onclick="removeCsv()">'
            . $this->escaper->escapeHtml(__('Remove List'))
            . '</button>';
        $html .= '</div>';

        $html .= '<button type="button" style="display: block; margin-bottom: 10px;" '
            . 'onclick="downloadCsv()">'
            . $this->escaper->escapeHtml(__('Download List'))
            . '</button>';

        $html .= '</div>';

        $html .= '<script type="text/javascript">
            window.formKey = "' . $this->getFormKey() . '";
            window.applyUrl = "' . $this->getUrl('uploadtogglecsv/featuretoggle/apply') . '";
            window.toggleSelector = "tr[id*=\'row_environment_toggle\']";
        </script>';

        $html .= '<script type="text/javascript">
            require(["jquery", "Fedex_UploadToggleCsv/js/feature-toggle"], function($) {
                $("#' . $elementId . '").on("change", function() {
                    window.uploadCsvHandler(this.files[0]);
                });
            });
        </script>';

        return $html;
    }
}
