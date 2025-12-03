<?php
/**
 * @copyright Copyright (c) 2023 Fedex.
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\Company\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\System\Store;

class CompanyStore extends AbstractFieldArray
{
    /**
     * @var Companycolumn
     */
    private $companyRenderer;

    /**
     * @var Store
     */
    private $storeRenderer;

    /**
     * @var Store
     */
    private $storeViewRenderer;

    /**
     * @inheritDoc
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'company',
            [
                'label'    => __('Company'),
                'renderer' => $this->getCompanyRenderer(),
                'class' => 'required-entry'
            ]
        );

        $this->addColumn(
            'store',
            [
                'label' => __('Store'),
                'renderer' => $this->getStoreRenderer(),
                'class' => 'required-entry'
            ]
        );

        $this->_addAfter       = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * @inheritDoc
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];
        $company = $row->getData('internal_role');

        if ($company !== null) {
            $options['option_' . $this->getCompanyRenderer()
                ->calcOptionHash($company)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * Create CompanyColumn block to render
     *
     * @return CompanyColumn
     * @throws LocalizedException
     */
    private function getCompanyRenderer()
    {
        if (!$this->companyRenderer) {
            $this->companyRenderer = $this->getLayout()->createBlock(
                CompanyColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->companyRenderer;
    }

    /**
     * Create RoleColum block to render
     *
     * @return Store
     * @throws LocalizedException
     */
    private function getStoreRenderer()
    {
        if (!$this->storeRenderer) {
            $this->storeRenderer = $this->getLayout()->createBlock(
                StoreColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->storeRenderer;
    }
}
