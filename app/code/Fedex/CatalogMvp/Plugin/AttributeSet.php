<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin;

use \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AttributeSet as AttributeSetCore;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Framework\App\RequestInterface;
use Magento\Eav\Setup\EavSetup;

class AttributeSet
{

    /**
     * @param CatalogMvp $helper
     * @param RequestInterface $request
     * @param EavSetup $eavSetup
     */
    public function __construct(
        protected CatalogMvp $helper,
        protected RequestInterface $request,
        protected EavSetup $eavSetup
    )
    {
    }

    public function afterModifyMeta(
        AttributeSetCore $subject,
        $result
    ): array {
        if ($this->helper->isMvpCtcAdminEnable()) {
            $printOnDemandAttributeSetId = $this->eavSetup->getAttributeSetId(
                \Magento\Catalog\Model\Product::ENTITY,
                'PrintOnDemand'
            );
            $name = "product-details";

            if ($this->helper->isProductAdminRefreshToggle()) {
                $result[$name]['children']['attribute_set_id']['arguments']['data']['config']['component'] = 'Fedex_CatalogMvp/js/components/attribute-set-select-update';
            } else {
                $result[$name]['children']['attribute_set_id']['arguments']['data']['config']['component'] = 'Fedex_CatalogMvp/js/components/attribute-set-select';
            }

            if($this->request->getParam('set') == $printOnDemandAttributeSetId &&
                isset($result[$name]['children']['container_external_prod']['arguments']['data']['config'])
            ) {
                $result[$name]['children']['container_external_prod']['arguments']['data']['config']['required'] = 1;
                $result[$name]['children']['container_external_prod']['children']
                ['external_prod']['arguments']['data']['config']['required'] = 1;
                $result[$name]['children']['container_external_prod']['children']
                ['external_prod']['arguments']['data']['config']['notice'] = "Note: Please configure before save";

                $result[$name]['children']['container_external_prod']['children']['external_prod']
                ['arguments']['data']['config']['validation']['required-entry'] = 1;

               
            }
        }
        return $result;
    }
}
