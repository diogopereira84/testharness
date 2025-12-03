<?php
/**
 * @category    Fedex
 * @package     Fedex_EnvironmentManager
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Adithya Adithya <adithya.adithya@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Model\Config;

class TrackOrderPod extends ToggleBase implements ToggleInterface
{
    /**
     * toggle path variable for track order
     */
    private const PATH = 'sgc_b1528031_track_order_pod';

    /**
     * @inheritDoc
     */
    protected function getPath(): string
    {
        return self::PATH;
    }
}