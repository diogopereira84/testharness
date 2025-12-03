<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Api;

use Magento\Framework\Exception\NoSuchEntityException;

interface EmailInterface
{
    /**
     * Get the HTML content of an email template.
     *
     * @param string $templateName
     * @param array $orderData
     * @return array
     * @throws NoSuchEntityException
     */
    public function getEmailHtml(string $templateName, array $orderData): array;

    /**
     * Get the URL of the email logo.
     *
     * @return string
     */
    public function getEmailLogoUrl(): string;

    /**
     * Minify HTML content.
     *
     * @param string $html
     * @return string
     */
    public function minifyHtml(string $html): string;

    /**
     * Get date in CST format
     *
     * @param string $datetime
     * @return string
     */
    public function getFormattedCstDate(string $datetime): string;
}
