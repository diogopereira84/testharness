<?php
declare(strict_types=1);

namespace Fedex\Company\Ui\Component\Fieldset;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Ui\Component\Form\Fieldset;

class CompanySettingToggle extends Fieldset
{
    /**
     * @var ToggleConfig
     */
    private ToggleConfig $toggleConfig;

    /**
     * @var bool|int|string
     */
    private string|int|bool $companyStoreRestructureToggle;

    /**
     * Constructor function
     *
     * @param ContextInterface $context
     * @param ToggleConfig $toggleConfig
     * @param array $components
     * @param array $data
     * @return void
     */
    public function __construct(
        ContextInterface $context,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
    }

    /**
     * Get components
     *
     * @return UiComponentInterface[]
     */
    public function getChildComponents()
    {
        if ($this->getName() == 'company_logo') {
            return $this->components;
        }

        return [];
    }

    /**
     * Visible Payment tab based on toggle value
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        $config = parent::getConfiguration();
        $config['visible'] = false;
        if ($this->getName() == 'company_logo') {
          $config['visible'] = true;
        }
        return $config;
    }
}
