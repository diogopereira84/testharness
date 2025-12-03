<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CmsImportExport\Plugin\Ui\Component\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Plugin class for PreviewImage
 */
class PreviewImage
{
    /**
     * @param UrlInterface $urlBuilder
     * @param Filesystem $filesystem
     */
    public function __construct(
        protected UrlInterface $urlBuilder,
        protected Filesystem $filesystem
    ) {
    }

    /**
     * Change image path of datasource
     *
     * @param object $subject
     * @param array $result
     * @return array
     */
    public function afterPrepareDataSource($subject, $result)
    {
            $mediaPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
        if (isset($result['data']['items'])) {
            $fieldName = $subject->getData('name');
            foreach ($result['data']['items'] as & $item) {
                $previewImage = strpos($item[$fieldName], 'http') !== false ?
                $item[$fieldName] : $mediaPath . $item[$fieldName];
                $imageSrc = $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]).
                    "wysiwyg/pagebuilder_template.png";
                if (is_file($previewImage) || (strpos($previewImage, '../static/adminhtml') !== false)) {
                    $imageSrc = $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]) .
                    $item[$fieldName];
                }
                $item[$fieldName . '_src']      = str_replace('.jpg', '-thumb.jpg', $imageSrc);
                $item[$fieldName . '_orig_src'] = $imageSrc;
            }
        }

        return $result;
    }
}
