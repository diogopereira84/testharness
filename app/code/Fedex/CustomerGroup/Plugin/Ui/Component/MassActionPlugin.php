<?php
namespace Fedex\CustomerGroup\Plugin\Ui\Component;

use Magento\Ui\Component\AbstractComponent;
use Magento\Ui\Component\MassAction;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class MassActionPlugin extends AbstractComponent
{
    const NAME = 'massaction';
    /**
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected ToggleConfig $toggleConfig
    )
    {
    }
    /**
     * @param MassAction $subject
     * @param array $result
     * @return array $result
     */
    public function afterPrepare(\Magento\Ui\Component\MassAction $subject, $result)
    {
        $allowedActions = [];
        $config = $subject->getConfiguration();
        foreach ($config['actions'] as $action) {
            if ('assign_to_group' != $action['type']) {
                $allowedActions[] = $action;
            }
        }

        $config['actions'] = $allowedActions;
        $subject->setData('config', $config);

        return $result;
    }
    /**
     * Get component name
     *
     * @return string
     */
    public function getComponentName()
    {
        return static::NAME;
    }
}
