<?php
declare(strict_types=1);

namespace Fedex\Catalog\Block\Adminhtml;

use Magento\Backend\Block\Widget\Context;
use Magento\Catalog\Model\Product\TypeFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Catalog\Block\Adminhtml\Product as BaseProduct;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\RequestInterface;

class Product extends BaseProduct
{
    public function __construct(
        private readonly Context $context,
        private readonly TypeFactory $typeFactory,
        private readonly ProductFactory $productFactory,
        private readonly AttributeSet $attributeSet,
        protected ToggleConfig $toggleConfig,
        protected RequestInterface $request,
        array $data = []
    ) {
        parent::__construct($context, $typeFactory, $productFactory, $data);
    }

    
    /**
     * Retrieve product create URL by specified product type
     *
     * @param string $type
     * @return string
     */
    protected function _getProductCreateUrl($type)
    {
        if ($this->toggleConfig->getToggleConfigValue('tech_titans_e_484727')) {
            if ($type === 'commercial') {
                $attributeSetId = $this->getAttributeSetIdByName('printondemand')
                    ?? $this->productFactory->create()->getDefaultAttributeSetId();

                return $this->getUrl('catalog/*/new', [
                    'set' => $attributeSetId,
                    'type' => $type
                ]);
            }
        }

        return parent::_getProductCreateUrl($type);
    }


    /**
     * Get attribute set ID by name
     *
     * @param string $name
     * @return int|null
     */
    private function getAttributeSetIdByName(string $name): ?int
    {
        $entityTypeId = $this->productFactory->create()->getResource()->getTypeId();

        $attributeSet = $this->attributeSet->getCollection()
            ->setEntityTypeFilter($entityTypeId)
            ->addFieldToFilter('attribute_set_name', $name)
            ->getFirstItem();

        return $attributeSet->getId() ? (int) $attributeSet->getId() : null;
    }

    /**
     * Get current product type
     */
    public function getProductType()
    {
      return  $this->request->getParam('type');
    }
}
