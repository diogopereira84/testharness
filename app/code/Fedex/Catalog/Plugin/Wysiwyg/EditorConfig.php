<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Iago Ferreira Lima <iago.lima.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Plugin\Wysiwyg;

use Fedex\Catalog\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Ui\Component\Wysiwyg\ConfigInterface;
use Magento\Framework\DataObject;

class EditorConfig
{
    /**
     * @param Config $catalogConfig
     */
    public function __construct(
        protected Config $catalogConfig
    )
    {
    }
    /**
     * Return WYSIWYG configuration
     *
     * @param ConfigInterface $configInterface
     * @param DataObject $result
     * @return DataObject
     */
    public function afterGetConfig(ConfigInterface $configInterface, DataObject $result)//NOSONAR
    {

        $attrList = $this->catalogConfig->wysiwygAttributeList();
        if ($attrList && in_array($result->getData('current_attribute_code'), $attrList)) {

            $settings = $result->getData('settings');

            if (!is_array($settings)) {
                $settings = [];
            }

            $settings['toolbar'] = $this->catalogConfig->wysiwygToolbarConfig();
            $settings['valid_elements'] = $this->catalogConfig->getWysiwygValidElements();
            $settings['extended_valid_elements'] = $this->catalogConfig->getWysiwygExtendedValidElements();
            $settings['valid_styles'] = ['*' => $this->catalogConfig->getWysiwygValidStyles()];

            $result->addData([ 'add_images' => false ]);

            $result->setData('settings', $settings);
        }

        return $result;
    }
}
