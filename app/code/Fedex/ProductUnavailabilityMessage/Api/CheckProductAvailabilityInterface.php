<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Fedex\ProductUnavailabilityMessage\Api;

interface CheckProductAvailabilityInterface
{
    /**
     * Check if the E-441563 toggle is enabled.
     *
     * @return bool
     */
    public function isE441563ToggleEnabled();

    /**
     * Check if the D-228743 toggle is enabled.
     *
     * @return bool
     */
    public function isTigerTeamD228743ToggleEnabled();
    /**
     * get PDP error message
     *
     * @return string
     */
    public function getProductPDPErrorMessage();
    /**
     * get cart error message
     *
     * @return string
     */
    public function getProductCartlineErrorMessage();
}
