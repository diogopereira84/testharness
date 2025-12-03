<?php
/**
 * @category    Fedex
 * @package     Fedex_EnvironmentManager
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Model\Frontend\Toggle;

class Composite implements ResolverInterface
{
    /**
     * @var array
     */
    private array $toggles;

    /**
     * @param array $toggles
     */
    public function __construct(array $toggles)
    {
        foreach ($toggles as $toggle) {
            if (!$toggle instanceof ResolverInterface) {
                throw new \InvalidArgumentException(
                    sprintf('Instance of the toggle resolver is expected, got %s instead.', get_class($toggle))
                );
            }
        }

        $this->toggles = $toggles;
    }

    /**
     * @inheritDoc
     */
    public function build(): string
    {
        $scripts = '';

        foreach ($this->toggles as $toggle) {
            $scripts .= $toggle->build();
        }

        return $scripts;
    }
}
