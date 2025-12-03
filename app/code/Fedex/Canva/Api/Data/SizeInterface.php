<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Api\Data;

interface SizeInterface
{
    /**
     * Record id.
     *
     * @return string
     */
    public function getRecordId(): string;

    /**
     * Set record id.
     *
     * @param string $recordId
     *
     * @return $this
     */
    public function setRecordId(string $recordId): SizeInterface;

    /**
     * Default.
     *
     * @return string
     */
    public function getDefault(): string;

    /**
     * Set default.
     *
     * @param string $default
     *
     * @return $this
     */
    public function setDefault(string $default): SizeInterface;

    /**
     * Product Mapping.
     *
     * @return string
     */
    public function geProductMappingId(): string;

    /**
     * Set product mapping id.
     *
     * @param string $productMappingId
     *
     * @return $this
     */
    public function seProductMappingId(string $productMappingId): SizeInterface;

    /**
     * Display Width.
     *
     * @return string
     */
    public function getDisplayWidth(): string;

    /**
     * Set display width.
     *
     * @param string $displayWidth
     *
     * @return $this
     */
    public function setDisplayWidth(string $displayWidth): SizeInterface;

    /**
     * Display Height.
     *
     * @return string
     */
    public function getDisplayHeight(): string;

    /**
     * Set display height.
     *
     * @param string $displayHeight
     *
     * @return $this
     */
    public function setDisplayHeight(string $displayHeight): SizeInterface;

    /**
     * Orientation.
     *
     * @return string
     */
    public function getOrientation(): string;

    /**
     * Set orientation.
     *
     * @param string $orientation
     *
     * @return $this
     */
    public function setOrientation(string $orientation): SizeInterface;

    /**
     * Position.
     *
     * @return string
     */
    public function getPosition(): string;

    /**
     * Set position.
     *
     * @param string $position
     *
     * @return $this
     */
    public function setPosition(string $position): SizeInterface;
}
