<?php
/**
 * @category Fedex
 * @package  Fedex_Company
 * @copyright   Copyright (c) 2023 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Ondemand\Api\Data;

/**
 * Interface ConfigInterface
 */
interface ConfigInterface
{
    /**
     * Global Constant for Account Tab Names
     **/
    public const MY_ACCOUNT_TAB_NAME_TITLE = 'My Account | FedEx Office';

    /**
     * Global Constant for Manage Users Tab Name
     **/
    public const MANAGE_USERS_TAB_NAME_TITLE = 'Manage Users';

    /**
     * Global Constant for Company Users Tab Name
     **/
    public const COMPANY_USERS_TAB_NAME_TITLE = 'Company Users';

    /**
     * Global Constant for Site Level Payments Tab Name
     **/
    public const SITE_PAYMENTS_TAB_NAME_TITLE = 'Site Level Payments';

    /**
     * Global Constant for FedEx Office Homepage Tab Name
     **/
    public const HOMEPAGE_TAB_NAME_TITLE = 'Homepage | FedEx Office';

    /**
     * Global Constant for Ondemand Homepage Tab Name
     **/
    public const ONDEMAND_HOMEPAGE_TAB_NAME_TITLE = 'Ondemand Home Page';

    /**
     * Global Constant for Browse Print Products Tab Name
     **/
    public const BROWSE_PRINT_PRODUCTS_TAB_NAME_TITLE = 'Browse All Print Products | FedEx Office';

    /**
     * Global Constant for Fedex Office Shared Catalog Tab Name
     **/
    public const FEDEX_SHARED_CATALOG_TAB_NAME_TITLE = 'Shared Catalog | FedEx Office';

    /**
     * Global Constant for Shared Catalog Tab Name
     **/
    public const SHARED_CATALOG_TAB_NAME_TITLE = 'Shared Catalog';

    /**
     * Global Constant for My Orders Tab Name
     **/
    public const ORDERS_TAB_NAME_TITLE = 'My Orders';

    /**
     * Global Constant for Shared Orders Tab Name
     **/
    public const SHARED_ORDERS_TAB_NAME_TITLE = 'Shared Orders';

    /**
     * Return the B2B Default Store ID
     *
     * @return string|int|null
     **/
    public function getB2bDefaultStore(): string|int|null;

    /**
     * Return the B2B Print Products Category
     *
     * @return string|int|null
     **/
    public function getB2bPrintProductsCategory(): string|int|null;

    /**
     * Return the B2B Office Supplies Category
     *
     * @return string|int|null
     **/
    public function getB2bOfficeSuppliesCategory(): string|int|null;

    /**
     * Return the B2B Office Supplies Category Label
     *
     * @return string
     **/
    public function getB2bOfficeSuppliesCategoryLabel(): string;

    /**
     * Return the B2B Shipping, Packing and Mailing Supplies Category
     *
     * @return string|int|null
     **/
    public function getB2bSPMSuppliesCategory(): string|int|null;

    /**
     * Return the B2B Shipping, Packing and Mailing Supplies Category Label
     *
     * @return string
     **/
    public function getB2bSPMSuppliesCategoryLabel(): string;

    /**
     * Return the Global B2B Categories
     *
     * @return array
     **/
    public function getGlobalB2BCategories(): array;

    /**
     * Return the configured Shared Catalog
     *
     * @return string|int|null
     **/
    public function getDefaultSharedCatalog(): string|int|null;

    /**
     * Return the Tiger D239305 Toggle Value
     *
     * @return string|bool|int|null
     **/
    public function isTigerD239305ToggleEnabled(): string|bool|int|null;
}
