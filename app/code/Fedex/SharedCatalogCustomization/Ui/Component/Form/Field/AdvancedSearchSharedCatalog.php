<?php
/**
 * @category  Fedex
 * @package   Fedex_SharedCatalogCustomization
 * @copyright Copyright (c) 2024 FedEx.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Ui\Component\Form\Field;

use Fedex\Company\Api\Data\ConfigInterface;
use Magento\Company\Ui\Component\Form\Field as FormField;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class AdvancedSearchSharedCatalog extends FormField
{
    public const CREATE_NEW_VALUE = 'create_new';
    public const CREATE_NEW_LABEL = 'Create New';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly ConfigInterface $companyConfigInterface,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @return string[]
     */
    protected function getConfigDefaultData(): array
    {
        return [
            'value' => self::CREATE_NEW_VALUE
        ];
    }
}
