<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SSO\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig as ToggleManager;

class ToggleConfig
{
    /** @var string  */
    const XPATH_TOGGLE_D_201662 = 'environment_toggle_configuration/environment_toggle/d_201662_fix_return_type';

    /**
     * @param ToggleManager $toggleConfig
     */
    public function __construct(
        private readonly ToggleManager $toggleConfig
    ) {
    }

    /**
     * @return bool
     */
    public function isToggleD201662Enabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfig(self::XPATH_TOGGLE_D_201662);
    }
}
