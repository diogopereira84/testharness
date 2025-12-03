<?php
/**
 * @category  Fedex
 * @package   Fedex_InBranch
 * @author    Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\InBranch\Model;

/**
 * @codeCoverageIgnore
 */
interface InBranchValidationInterface
{
    /**
     * Check isbranch product is already exist
     *
     * @param object $productInfo
     * @param bool $jsonObject
     * @return mixed
     */
    public function isInBranchValid($productInfo, $jsonObject);

    /**
     * Check Isbranch product is already exist for reorder
     *
     * @param array $productInfo
     * @return mixed
     */
    public function isInBranchValidReorder($productInfo);

    /**
     * Get Allowed InBranch Location
     *
     * @return string
     */
    public function getAllowedInBranchLocation();

    /**
     * Check in-branch location exist and contentAssociation is empty
     *
     * @return bool
     */
    public function isInBranchProductWithContentAssociationsEmpty($items);

     /**
     * Check in-branch contentAssociations if empty
     *
     * @return bool
     */
    public function isProductContentAssociationsEmpty($item);
}
