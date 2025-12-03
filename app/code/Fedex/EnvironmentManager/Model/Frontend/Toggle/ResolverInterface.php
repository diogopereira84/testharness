<?php
/**
 * @category    Fedex
 * @package     Fedex_EnvironmentManager
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Model\Frontend\Toggle;

interface ResolverInterface
{
    /**
     * Build the toggle status
     *
     * @return string
     */
    public function build(): string;
}
