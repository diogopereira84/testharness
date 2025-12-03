<?php
namespace Fedex\EnvironmentManager\Api;

interface RetiredToggleManager
{

    /**
     * Delete selected Retired Toggles from core_config_data table
     *
     * @param $selectedRetiredToggles
     * @return string
     */
    public function flushSelectedRetiredToggles($selectedRetiredToggles = []): string;

    /**
     * Delete all Retired Toggles from core_config_data table
     *
     * @return string
     */
    public function flushAllRetiredToggles(): string;

    /**
     * Get Fields that can be flushed
     * @return string
     */
    public function getTogglesToBeFlushed(): string;
}
