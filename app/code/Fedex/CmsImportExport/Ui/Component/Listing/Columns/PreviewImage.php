<?php
declare(strict_types=1);

namespace Fedex\CmsImportExport\Ui\Component\Listing\Columns;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\PageBuilder\Ui\Component\Listing\Columns\PreviewImage as ParentPreviewImage;

/**
 * Display Template preview image within grid
 */
class PreviewImage extends ParentPreviewImage
{
    const NAME = 'preview_image';

    const ALT_FIELD = 'name';

    /** @var string */
    protected $mediaPath;


    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param Filesystem $filesystem
     * @param ToggleConfig $toggleConfig
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        protected UrlInterface $urlBuilder,
        protected Filesystem $filesystem,
        protected ToggleConfig $toggleConfig,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $urlBuilder, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!$this->toggleConfig->getToggleConfigValue('xmen_remove_adobe_commerce_override')) {
            $mediaPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
            if (isset($dataSource['data']['items'])) {
                $fieldName = $this->getData('name');
                foreach ($dataSource['data']['items'] as & $item) {
                    $previewImage = strpos($item[$fieldName], 'http') !== false ?
                    $item[$fieldName] : $mediaPath . $item[$fieldName];
                    if (is_file($previewImage) || (strpos($previewImage, '../static/adminhtml') !== false)) {
                        $imageSrc = $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]) .
                        $item[$fieldName];
                    } else {
                        $imageSrc = $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]).
                        "wysiwyg/pagebuilder_template.png";
                    }
                    $item[$fieldName . '_src']      = str_replace('.jpg', '-thumb.jpg', $imageSrc);
                    $item[$fieldName . '_alt']      = $this->getAlt($item);
                    $item[$fieldName . '_link']     = null;
                    $item[$fieldName . '_orig_src'] = $imageSrc;
                }
            }

            return $dataSource;
        } else {
           return parent::prepareDataSource($dataSource);
        }
    }

    /**
     * Get Alt
     *
     * @param array $row
     *
     * @return null|string
     */
    private function getAlt($row)
    {
        $altField = $this->getData('config/altField') ?: self::ALT_FIELD;
        return $row[$altField] ?? null;
    }
}
