<?php

namespace Fedex\Cart\ViewModel;

use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOCMConfigurator\ViewModel\FXOCMHelper;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class UnfinishedProjectNotification implements ArgumentInterface
{
    public const B_2260777_ACCESS_TO_WORKSPACE = 'b_2260777_access_to_workspace';

    /**
     * Constructor
     *
     * @param ToggleConfig $toggleConfig
     * @param Session $customerSession
     * @param FXOCMHelper $fxoCMHelper
     * @param AuthHelper $authHelper
     * @param PerformanceImprovementPhaseTwoConfig $performanceImprovementPhaseTwoConfig
     */
    public function __construct(
        protected ToggleConfig                                $toggleConfig,
        protected Session                                     $customerSession,
        protected FXOCMHelper                                 $fxoCMHelper,
        protected AuthHelper                                  $authHelper,
        private readonly PerformanceImprovementPhaseTwoConfig $performanceImprovementPhaseTwoConfig,
        protected ScopeConfigInterface                        $scopeConfig
    )
    {
    }

    /**
     * Check is Cart Page and Unfinished Popup is Enabled
     *
     * @return bool true|false
     */
    public function isCartPageUnfinisedPopupEnable()
    {
        if (!$this->authHelper->isLoggedIn()
            && $this->toggleConfig->getToggleConfigValue('batch_upload_toggle')
        ) {
            return $this->isProjectAvailable();
        }
        return false;
    }

    /**
     * Check if userworkspace contain projects.
     *
     * @return bool true|false
     */
    public function isProjectAvailable()
    {
        static $return = null;
        if ($this->performanceImprovementPhaseTwoConfig->isActive()
            && $return !== null
        ) {
            return $return;
        }
        $userWorkspace = $this->fxoCMHelper->getWorkspaceData();
        if (!empty($userWorkspace)) {
            $userWorkspace = (array)json_decode($userWorkspace);
            $userWorkspace = count($userWorkspace['projects']);
            if ($userWorkspace != 0) {
                $return = true;
                return $return;
            }
        }
        $return = false;
        return $return;
    }

    /**
     * Toggle Tiger Team - B-2260777 Access to Workspace
     *
     * @return bool
     */
    public function isAccessToWorkspaceToggleEnable(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::B_2260777_ACCESS_TO_WORKSPACE);
    }

    /**
     * Get Workspace Url
     *
     * @return string
     */
    public function getWorkspaceUrl()
    {
        return $this->scopeConfig->getValue(
            'fedex/fxo_cm_content/workspace_url'
        );
    }
}
