<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\Api;

interface ConfigInterface
{
    /**
     * Check if the Tiger E468338 toggle is enabled.
     *
     * @return bool
     */
    public function isTigerE468338ToggleEnabled(): bool;

    /**
     * Get the title for step one.
     */
    public function getTitleStepOne(): ?string;

    /**
     * Get the description for step one.
     */
    public function getDescriptionStepOne(): ?string;

    /**
     * Get the title for step two.
     */
    public function getTitleStepTwo(): ?string;

    /**
     * Get the description for step two.
     */
    public function getDescriptionStepTwo(): ?string;

    /**
     * Get the title for step three.
     */
    public function getTitleStepThree(): ?string;

    /**
     * Get the description for step three.
     */
    public function getDescriptionStepThree(): ?string;
}
