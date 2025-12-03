<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Api;

use Exception;

/**
 * Interface TemplateImageConverter
 * Converts Base64 Images and save on the disk media folder to be used in the template
 */
interface TemplateImageConverterInterface
{
    /**
     * Get the created image path
     *
     * @return string
     */
    public function getImagePath(): string;

    /**
     * Convert Base64Image into a a disk image
     *
     * @param string $image
     * @param string $name
     * @return TemplateImageConverterInterface
     * @throws Exception
     */
    public function convert(string $image, string $name): TemplateImageConverterInterface;
}
