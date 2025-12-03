<?php
/**
 * Interface ConfigInterface
 *
 * Defines methods for getting system config values.
 *
 * @category     Fedex
 * @package      Fedex_UploadToQuote
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\UploadToQuote\Api;

use Magento\Store\Model\ScopeInterface;

interface ConfigInterface
{
    public const XML_PATH_UPLOAD_TO_QUOTE_LOGIN_MODAL_HEADING =
        'fedex/upload_to_quote_config/login_modal_heading';
    public const XML_PATH_UPLOAD_TO_QUOTE_LOGIN_MODAL_COPY =
        'fedex/upload_to_quote_config/login_modal_copy';
    public const XML_PATH_4673962_WRONG_LOCATION_QUOTE =
        'environment_toggle_configuration/environment_toggle/tiger_tk4673962';

    public const XML_PATH_4674396_QUOTES_NOT_VISIBLE =
        'environment_toggle_configuration/environment_toggle/tiger_tk4674396';

    /**
     * Gets config value for the login modal heading in Upload to Quote feature.
     *
     * @param string $scope
     * @return string
     */
    public function getLoginModalHeading(
        string $scope = ScopeInterface::SCOPE_STORE
    ): string;

    /**
     * Gets config value for the login modal copy in Upload to Quote feature.
     *
     * @param string $scope
     * @return string
     */
    public function getLoginModalCopy(
        string $scope = ScopeInterface::SCOPE_STORE
    ): string;

    /**
     * Gets toggle status for TK-4673962 Retail/Commercial Quotes are not routing to the customer selected stores
     *
     * @param string $scope
     * @return bool
     */
    public function isTk4673962ToggleEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * Gets toggle status for TK-4674396 U2Q approved quote converted to order does not list the quote as Approved in Quote History (Commercial)
     *
     * @param string $scope
     * @return bool
     */
    public function isTk4674396ToggleEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;
}
