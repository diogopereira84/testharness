<?php
/**
 * @category Fedex
 * @package  Fedex_Cart
 * @copyright   Copyright (c) 2022 Fedex
 */

declare(strict_types=1);

namespace Fedex\Cart\Model;

use Exception;
use Fedex\Cart\Model\Quote\Product\ContentAssociationsResolver;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Fedex\FXOCMConfigurator\Helper\Data as FxocmHelperData;

class BuyRequestBuilder
{
    protected const PRODUCT = 'product';
    protected const PRODUCT_CONFIG = 'productConfig';
    protected const FXO_PRODUCT_INSTANCE = 'fxoProductInstance';
    protected const FXO_MENU_ID = 'fxoMenuId';
    protected const INTEGRATOR_PRODUCT_REF = 'integratorProductReference';

    /**
     * Initialize dependencies.
     * @param ToggleConfig $toggleConfig
     * @param UploadToQuoteViewModel $uploadToQuoteViewModel ,
     * @param FxocmHelperData $fxocmhelperdata
     * @param RequestQueryValidator $requestQueryValidator
     * @param ContentAssociationsResolver $contentAssociationsResolver
     */
    public function __construct(
        protected ToggleConfig $toggleConfig,
        protected UploadToQuoteViewModel $uploadToQuoteViewModel,
        protected FxocmHelperData $fxocmhelperdata,
        private RequestQueryValidator $requestQueryValidator,
        private ContentAssociationsResolver $contentAssociationsResolver
    )
    {
    }

    /**
     * @param array $requestData
     * @return array
     * @throws Exception
     */
    public function build($requestData): array
    {
        $isGraphQlRequest = $this->requestQueryValidator->isGraphQl();

        if (
            $this->fxocmhelperdata->getFxoCMToggle() &&
            !$isGraphQlRequest &&
            isset($requestData[self::INTEGRATOR_PRODUCT_REF])
        ) {
            $productConfig = $requestData;
            $contentReference = $this->contentAssociationsResolver->getContentReference(
                $productConfig[self::PRODUCT]['contentAssociations'] ?? null
            );
            if ($contentReference) {
                $productConfig[self::PRODUCT]['preview_url'] = $contentReference;
            }
            $productConfig[self::PRODUCT]['isEditable'] = $productConfig['isEditable'];
            $productConfig[self::PRODUCT]['isEdited'] = false;
            $productConfig[self::PRODUCT][self::FXO_MENU_ID] = $requestData[self::INTEGRATOR_PRODUCT_REF];
            $infoBuyRequest = [];
            $infoBuyRequest['external_prod'] = [$productConfig[self::PRODUCT]];
            if ($this->uploadToQuoteViewModel->isUploadToQuoteEnable() &&
                isset($productConfig[self::PRODUCT]['contentAssociations'])) {
                $infoBuyRequest['originalFiles'] = [$productConfig[self::PRODUCT]['contentAssociations']];
            }
            $infoBuyRequest[self::PRODUCT_CONFIG] = $productConfig ?? '';
            unset($infoBuyRequest[self::PRODUCT_CONFIG][self::PRODUCT]);
            $infoBuyRequest['productRateTotal'] = $requestData[self::FXO_PRODUCT_INSTANCE]['productRateTotal'] ?? [];
            $infoBuyRequest['quantityChoices'] = $requestData[self::FXO_PRODUCT_INSTANCE]['quantityChoices'] ?? [];
            $infoBuyRequest['fileManagementState'] = $requestData[self::FXO_PRODUCT_INSTANCE]['fileManagementState'] ?? [];
            $infoBuyRequest[self::FXO_MENU_ID] = $requestData[self::FXO_MENU_ID] ?? '';
            if (isset($requestData['productType']) && ($requestData['productType'] == "COMMERCIAL_PRODUCT")) {
                $infoBuyRequest['external_prod'][0]['productionContentAssociations'] = [];
                if (!isset($infoBuyRequest['external_prod'][0]['userProductName'])) {
                    $infoBuyRequest['external_prod'][0]['userProductName'] = $requestData['fxoProductInstance']['name'] ?? "";
                }
                $infoBuyRequest['expressCheckout'] = $requestData['fxoProductInstance']['expressCheckout'] ?? false;
                $infoBuyRequest['isEditable'] = $requestData['fxoProductInstance']['isEditable'] ?? false;
                $infoBuyRequest['catalogDocumentMetadata'] = $requestData['fxoProductInstance']['catalogDocumentMetadata'] ?? [];
                $infoBuyRequest['isEdited'] = $requestData['fxoProductInstance']['isEdited'] ?? false;
                $infoBuyRequest['customDocState'] = $requestData['fxoProductInstance']['customDocState'] ?? [];
            }
            if (isset($productConfig[self::PRODUCT]['designId']) && !empty($productConfig[self::PRODUCT]['designId'])) {
                $infoBuyRequest[self::PRODUCT_CONFIG]['designProduct']['designId'] = $productConfig[self::PRODUCT]['designId'];
            }
            if (isset($productConfig[self::PRODUCT]['partnerProductId']) && !empty($productConfig[self::PRODUCT]['partnerProductId'])) {
                $infoBuyRequest[self::PRODUCT_CONFIG]['designProduct']['partnerProductId'] = $productConfig[self::PRODUCT]['partnerProductId'];
            }
            return $infoBuyRequest;
        } else {
            $productConfig = $requestData[self::FXO_PRODUCT_INSTANCE][self::PRODUCT_CONFIG];
            $contentReference = $this->contentAssociationsResolver->getContentReference(
                $productConfig[self::PRODUCT]['contentAssociations'] ?? null
            );
            if ($contentReference) {
                $productConfig[self::PRODUCT]['preview_url'] = $contentReference;
            }
            $productConfig[self::PRODUCT]['isEditable'] = $requestData[self::FXO_PRODUCT_INSTANCE]['isEditable'];
            $productConfig[self::PRODUCT]['isEdited'] = $requestData[self::FXO_PRODUCT_INSTANCE]['isEdited'];
            $productConfig[self::PRODUCT][self::FXO_MENU_ID] = $requestData[self::FXO_MENU_ID] ?? '';
            $infoBuyRequest = [];
            $infoBuyRequest['external_prod'] = [$productConfig[self::PRODUCT]];
            if ($this->uploadToQuoteViewModel->isUploadToQuoteEnable() &&
                isset($productConfig[self::PRODUCT]['contentAssociations'])) {
                $infoBuyRequest['originalFiles'] = [$productConfig[self::PRODUCT]['contentAssociations']];
            }
            $infoBuyRequest[self::PRODUCT_CONFIG] = $productConfig ?? '';
            unset($infoBuyRequest[self::PRODUCT_CONFIG][self::PRODUCT]);
            $infoBuyRequest['productRateTotal'] = $requestData[self::FXO_PRODUCT_INSTANCE]['productRateTotal'] ?? [];
            $infoBuyRequest['quantityChoices'] = $requestData[self::FXO_PRODUCT_INSTANCE]['quantityChoices'] ?? [];
            $infoBuyRequest['fileManagementState'] = $requestData[self::FXO_PRODUCT_INSTANCE]['fileManagementState'] ?? [];
            $infoBuyRequest[self::FXO_MENU_ID] = $requestData[self::FXO_MENU_ID] ?? '';
            if (isset($requestData['productType']) && ($requestData['productType'] == "COMMERCIAL_PRODUCT")) {
                $infoBuyRequest['external_prod'][0]['productionContentAssociations'] = [];
                if (!isset($infoBuyRequest['external_prod'][0]['userProductName'])) {
                    $infoBuyRequest['external_prod'][0]['userProductName'] = $requestData['fxoProductInstance']['name'] ?? "";
                }
                $infoBuyRequest['expressCheckout'] = $requestData['fxoProductInstance']['expressCheckout'] ?? false;
                $infoBuyRequest['isEditable'] = $requestData['fxoProductInstance']['isEditable'] ?? false;
                $infoBuyRequest['catalogDocumentMetadata'] = $requestData['fxoProductInstance']['catalogDocumentMetadata'] ?? [];
                $infoBuyRequest['isEdited'] = $requestData['fxoProductInstance']['isEdited'] ?? false;
                $infoBuyRequest['customDocState'] = $requestData['fxoProductInstance']['customDocState'] ?? [];
            }
            return $infoBuyRequest;
        }
    }
}
