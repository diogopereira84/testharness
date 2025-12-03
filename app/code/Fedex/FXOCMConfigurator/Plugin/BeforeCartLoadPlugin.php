<?php

namespace Fedex\FXOCMConfigurator\Plugin;

use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\CartItemInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Framework\Serialize\SerializerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Request\Http;

class BeforeCartLoadPlugin
{
    const NEW_DOCUMENTS_API_IMAGE_PREVIEW = 'new_documents_api_image_preview_toggle';

    /**
     * BeforeCartLoadPlugin constructor.
     * @param CatalogDocumentRefranceApi $catalogDocumentRefranceApi
     * @param ToggleConfig   $toggleConfig
     */
    public function __construct(
        protected CatalogDocumentRefranceApi $catalogDocumentRefranceApi,
        protected SerializerInterface $serializer,
        protected LoggerInterface $logger,
        protected ToggleConfig $toggleConfig,
        protected ProductRepositoryInterface $productRepositoryInterface,
        protected Http $http
    )
    {
    }

    /**
     * Plugin method to execute before the cart loads.
     *
     * @param Quote $subject
     * @param CartItemInterface[] $items
     * @return CartItemInterface[]
     */
    public function beforeGetItems(Quote $subject, $items = [])
    {

        $fullActionName = $this->http->getFullActionName();
        if ($fullActionName === 'checkout_cart_index') {
            try {
                //Millionaires - E-398131 : Adoption of New Document Platform toggle
                $newDocumentApiImageToggle = $this->toggleConfig->getToggleConfigValue(static::NEW_DOCUMENTS_API_IMAGE_PREVIEW);
                $checkLegacyDocApiOnCartToggle = $this->toggleConfig->getToggleConfigValue('techtitans_B2353473_remove_legacy_doc_api_call_on_cart');
                if ($newDocumentApiImageToggle) {
                    // Loop through each item in the cart
                    foreach ($subject->getAllVisibleItems() as $item) {
                        $additionalOption = $item->getOptionByCode('info_buyRequest');
                        $additionalOptions = $additionalOption->getValue();
                        if ($additionalOptions) {
                            $productData = (array) $this->serializer->unserialize($additionalOptions);
                        }
                        // Loop through each item in the contentAssociations array
                        if (isset($productData['external_prod'][0]['contentAssociations'])) {
                            foreach ($productData['external_prod'][0]['contentAssociations'] as &$contentAssociation) {
                                $contentReference = $contentAssociation['contentReference'];
                                // Check if contentReference is numeric
                                $this->logger->info(__METHOD__ . ':' . __LINE__ . 'Content Reference before migration' . $contentReference);
                                if (is_numeric($contentReference) && !$checkLegacyDocApiOnCartToggle) {
                                    $product = $this->productRepositoryInterface->get($item->getSku());
                                    $customizationFields = $product->getData('customization_fields');
                                    $convertNewDocumentApi = true;
                                    //Do not migrate duncy image to new document api for ePro legacy synced custom doc
                                    if (($product->getCustomizable()) && (empty($customizationFields) || $customizationFields == [])){
                                        $convertNewDocumentApi=false;
                                    }
                                    if ($convertNewDocumentApi) {
                                        $response =  $this->catalogDocumentRefranceApi->documentLifeExtendApiCallWithDocumentId($contentReference);
                                        // Check if the array key exists before assigning the value
                                        if (!empty($response) && array_key_exists('output', $response)) {
                                            $newDocumentId = $response['output']['document']['documentId'];
                                            $this->logger->info(__METHOD__ . ':' . __LINE__ . 'Content Reference after migration' . $newDocumentId);
                                            if (!empty($newDocumentId)) {
                                                // Updated the contentReference value
                                                $contentAssociation['contentReference'] = $newDocumentId;
                                                $productData['external_prod'][0]['preview_url'] = $newDocumentId;
                                                $encodedData = $this->serializer->serialize($productData);
                                                $additionalOption->setValue($encodedData)->save();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error in processing documentID of New Document Platform: ' . $e->getMessage());
            }
            return [$items];
        }
    }
}
