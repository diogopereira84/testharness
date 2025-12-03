<?php
declare(strict_types=1);
namespace Fedex\CSP\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\Factory;

class EntriesList extends AbstractFieldArray
{
    /**
     * Entries constructor.
     *
     * @param Context $context
     * @param Factory $elementFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        private Factory $elementFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('entry_name', ['label' => __('Entry')]);
        $this->_addAfter = false;
        $this->_template = 'Fedex_CSP::system/config/form/field/entriesList.phtml';

        parent::_construct();
    }

    /**
     * Render array cell
     * @param string $columnName
     * @return mixed|string
     * @throws \Exception
     */
    public function renderCellTemplate($columnName)
    {
        if ($columnName == 'store_id' && isset($this->_columns[$columnName])) {
            $options = $this->getOptions(__('-- Select Store --'));
            $element = $this->elementFactory->create('select');
            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                $options
            );
            return str_replace("\n", '', $element->getElementHtml());
        }

        return parent::renderCellTemplate($columnName);
    }

    /**
     * Get list of store views.
     *
     * @param bool|false $label
     * @return array
     */
    protected function getOptions($label = false)
    {
        $options = [];
        foreach ($this->_storeManager->getStores() as $store) {
            $options[] = [
                'value' => $store->getId(),
                'label' => $store->getName()
            ];
        }

        if ($label) {
            array_unshift($options, [
                'value' => '',
                'label' => $label
            ]);
        }

        return $options;
    }
}
