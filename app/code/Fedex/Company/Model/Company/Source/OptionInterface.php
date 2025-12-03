<?php
/**
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Company\Model\Company\Source;

interface OptionInterface
{
    /**
     * Get option label
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Get option value
     *
     * @return string|null
     */
    public function getValue(): ?string;
}
