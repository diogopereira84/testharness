<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\OKTA\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class Role extends AbstractFieldArray
{
    /**
     * @var RoleColumn
     */
    private $internalRoleRenderer;

    /**
     * @inheritDoc
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'internal_role',
            [
                'label'    => __('Role (Magento)'),
                'renderer' => $this->getInternalRoleRenderer()
            ]
        );

        $this->addColumn('external_group', ['label' => __('Group (OKTA)'), 'class' => 'required-entry']);

        $this->_addAfter       = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * @inheritDoc
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];
        $internalRole = $row->getData('internal_role');

        if ($internalRole !== null) {
            $options['option_' . $this->getInternalRoleRenderer()
                ->calcOptionHash($internalRole)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * Create RoleColum block to render
     *
     * @return RoleColumn
     * @throws LocalizedException
     */
    private function getInternalRoleRenderer()
    {
        if (!$this->internalRoleRenderer) {
            $this->internalRoleRenderer = $this->getLayout()->createBlock(
                RoleColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->internalRoleRenderer;
    }
}
