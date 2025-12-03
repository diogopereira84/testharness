<?php

declare(strict_types=1);

namespace Fedex\EnvironmentManager\Model;

use Fedex\EnvironmentManager\Model\Adminhtml\ToggleRetirementLogGenerator;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Config\Model\Config\Structure\Reader;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Message\ManagerInterface;

class RetiredToggleManager implements \Fedex\EnvironmentManager\Api\RetiredToggleManager
{
    public const SECTION_NAME = 'environment_toggle_configuration';
    public const GROUP_NAME = 'environment_toggle';
    public const FULL_PATH = 'environment_toggle_configuration/environment_toggle';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param Reader $reader
     * @param ToggleConfig $toggleConfig
     * @param ManagerInterface $managerInterface
     * @param ToggleRetirementLogGenerator $toggleRetirementLogGenerator
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private WriterInterface $configWriter,
        private Reader $reader,
        private ToggleConfig $toggleConfig,
        private ManagerInterface $managerInterface,
        protected ToggleRetirementLogGenerator $toggleRetirementLogGenerator
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function flushSelectedRetiredToggles($selectedRetiredToggles = []): string
    {
        $xmlEnvironmentToggleFields = array_keys($this->getSystemXmlToggleFields());
        if (!empty($xmlEnvironmentToggleFields)) {

            $retiredToggles = $this->processTogglesFlush($selectedRetiredToggles, $xmlEnvironmentToggleFields);

            return implode(', ', $retiredToggles);
        } else {
            $this->managerInterface->addWarningMessage('There are no fields within Environment Toggle Group.');
        }

        return '';
    }

    /**
     * @inheritDoc
     */
    public function flushAllRetiredToggles(): string
    {
        $xmlEnvironmentToggleFields = array_keys($this->getSystemXmlToggleFields());
        if (!empty($xmlEnvironmentToggleFields)) {

            $currentTogglesInDb = array_keys($this->getAllTogglesFromDB());

            $retiredToggles = $this->processTogglesFlush($currentTogglesInDb, $xmlEnvironmentToggleFields);

            return implode(', ', $retiredToggles);
        } else {
            $this->managerInterface->addWarningMessage('There are no fields within Environment Toggle Group.');
        }

        return '';
    }

    /**
     * @inheritDoc
     */
    public function getTogglesToBeFlushed(): string
    {
        $xmlEnvironmentToggleFields = array_keys($this->getSystemXmlToggleFields());
        if (!empty($xmlEnvironmentToggleFields)) {

            $currentTogglesInDb = array_keys($this->getAllTogglesFromDB());
            $retiredToggles = array_diff($currentTogglesInDb, $xmlEnvironmentToggleFields);
            sort($retiredToggles, SORT_STRING);

            return implode(',', $retiredToggles);
        } else {
            $this->managerInterface->addWarningMessage('There are no fields within Environment Toggle Group.');
        }

        return '';
    }

    /**
     * Process currentTogglesInDb against
     *
     * @param array $togglesToRetire
     * @param array $enviromentToggleGroupFields
     * @return array
     */
    protected function processTogglesFlush(array $togglesToRetire, array $enviromentToggleGroupFields)
    {
        $retiredToggles = array_diff($togglesToRetire, $enviromentToggleGroupFields);
        foreach ($retiredToggles as $retiredToggle) {
            $this->configWriter->delete(self::FULL_PATH.'/'. $retiredToggle);
        }

        $currentlyInUseToggles = array_intersect($togglesToRetire, $enviromentToggleGroupFields);
        if (!empty($currentlyInUseToggles)) {
            $this->managerInterface->addWarningMessage('These Toggles have not been retired because they still exist in XML file: ' . implode(', ', $currentlyInUseToggles));
        }

        $this->toggleRetirementLogGenerator->generateToggleRetirementLog($retiredToggles);

        return $retiredToggles;
    }

    /**
     * Return current Fields in system.xml files inside GROUP enviroment_toggle
     *
     * @return array|mixed
     */
    protected function getSystemXmlToggleFields()
    {
        $systemXmlFields =  $this->reader->read(\Magento\Framework\App\Area::AREA_ADMINHTML);

        return $systemXmlFields['config']['system']['sections'][self::SECTION_NAME]['children'][self::GROUP_NAME]['children'] ?? [];
    }

    /**
     * Return all Toggles under group environment_toggle that are currently inside DB
     *
     * @return mixed
     */
    protected function getAllTogglesFromDB()
    {
        return $this->scopeConfig->getValue(self::FULL_PATH);
    }
}
