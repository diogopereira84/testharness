<?php

namespace Fedex\SaaSCommon\Api;

interface ConfigInterface
{
    /**
     * Check if the Tiger D200529 feature is enabled.
     *
     * @return bool
     */
    public function isTigerD200529Enabled(): bool;
}

