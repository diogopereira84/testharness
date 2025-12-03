<?php

namespace Fedex\CatalogMvp\Api;

/**
 * @codeCoverageIgnore
 */
interface ConfigInterface
{
    /**
     * Return the value of the D206810 toggle\
     *
     * @return bool|int
     */
    public function isD206810ToggleEnabled(): bool|int;

    /**
     * Return the value of the B2371268 toggle\
     *
     * @return bool|int
     */
    public function isB2371268ToggleEnabled(): bool|int;
}
