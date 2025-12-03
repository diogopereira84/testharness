<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Tax\Plugin;

use Magento\Ui\Component\Wysiwyg\ConfigInterface;
use Magento\Framework\DataObject;

class EditorConfig
{
    /**
     * Return WYSIWYG configuration
     *
     * @param ConfigInterface $configInterface
     * @param DataObject $result
     * @return DataObject
     */
    public function afterGetConfig(ConfigInterface $configInterface, DataObject $result)
    {

        $isModalEditor = $result->getData('isModalEditor');

        if ($isModalEditor) {

            $settings = $result->getData('settings');

            if (!is_array($settings)) {
                $settings = [];
            }

            $settings['toolbar'] = 'undo redo | bold italic underline | link';
            $settings['valid_elements'] = 'strong,em,span[style=text-decoration: underline;],
            a[class:tax-modal-config|href|title|target<_blank|rel<noopener]';
            $settings['valid_styles'] = ['span' => 'text-decoration'];

            $result->addData([ 'add_images' => false ]);

            $result->setData('settings', $settings);
        }

        return $result;
    }
}
