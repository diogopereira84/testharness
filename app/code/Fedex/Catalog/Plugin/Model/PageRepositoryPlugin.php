<?php

namespace Fedex\Catalog\Plugin\Model;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Fedex\Catalog\Helper\Breadcrumbs as BreadcrumbsHelper;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\DomDocument\DomDocumentFactory;

/**
 * Cms page repository plugin
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PageRepositoryPlugin
{
    /**
     * PageRepositoryPlugin constructor.
     * @param BreadcrumbsHelper $helper
     * @param TypeListInterface $cacheTypeList
     * @param BlockRepositoryInterface $blockRepository
     * @param DomDocumentFactory $domFactory
     */
    public function __construct(
        public BreadcrumbsHelper $helper,
        public TypeListInterface $cacheTypeList,
        public BlockRepositoryInterface $blockRepository,
        public DomDocumentFactory $domFactory
    )
    {
    }

    /**
     * After save cms page plugin to update breadcrumb system config json for product template.
     *
     * @param PageRepositoryInterface $subject
     * @param PageInterface $page
     * @return PageInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(PageRepositoryInterface $subject, PageInterface $page): PageInterface
    {
        $needle = '#Custom-Breadcrumb';
        $pageFound = false;
        $configJson = [];
        $pageContent = $page->getContent();

        if (strpos($pageContent, $needle) !== false) {
            $pageJson = $this->helper->getControlJson();
            $skus = $this->getProductSkusfromPage($pageContent);

            if ($pageJson) {
                $configJson = json_decode($pageJson, true);
                foreach ($configJson as $key => $value) {
                    if ($value['url'] === $page->getIdentifier() &&
                        $value['label'] === $page->getTitle()) {
                        $value['skus'] = $skus;
                        unset($configJson[$key]);
                        $configJson = array_merge($configJson);
                        array_push($configJson, $value);
                        $pageFound = true;
                    } elseif (($value['url'] === $page->getIdentifier() &&
                            $value['label'] != $page->getTitle()) ||
                        ($value['url'] != $page->getIdentifier() &&
                            $value['label'] === $page->getTitle())) {
                        unset($configJson[$key]);
                        $configJson = array_merge($configJson);
                        $pageFound = false;
                    }
                }
            }
            $configJson = $this->setConfigJson($configJson, $page, $pageFound, $skus);
            
            $this->helper->setControlJson(json_encode($configJson, false));

            // Clean config cache only
            $this->cacheTypeList->cleanType('config');
        }
        return $page;
    }

    /**
     * @param array $configJson
     * @param PageInterface $page
     * @param boolean $pageFound
     * @param string $skus
     * @return array
     */
    public function setConfigJson($configJson, $page, $pageFound, $skus)
    {
        if (!$pageFound) {
            $pageBreadcrumb = [
                'label' => $page->getTitle(),
                'url' => $page->getIdentifier(),
                'skus' => $skus
            ];
            array_push($configJson, $pageBreadcrumb);
        }
        return $configJson;
    }

    /**
     * Find product skus from page content to update in config json.
     *
     * @param sting $pageContent
     * @return string
     */
    public function getProductSkusfromPage($pageContent): string
    {
        $skus = [];
        $pattern = '/block_id="(.*?)"/i';
        preg_match_all($pattern, $pageContent, $matches);
        if (count($matches[1]) > 0) {
            foreach ($matches[1] as $blockId) {
                $result = $this->getProductSkusFromBlock($blockId);
                if ($result != '') {
                    $skus = array_merge($skus, explode(',', $result));
                }
            }
        }
        $skus = array_unique($skus);
        return implode(',', $skus);
    }

    /**
     * Find product skus from block content to update in config json.
     *
     * @param int $blockId
     * @return string
     */
    public function getProductSkusFromBlock($blockId): string
    {
        $block = $this->blockRepository->getById($blockId);
        $content = $block->getContent();

        $matches = [];

        $pattern = '/name="breadcrumb-skus" value="(.*?)"/i';
        preg_match_all($pattern, $content, $matches);

        if (!empty($matches[1][0])) {
            return $matches[1][0];
        }
        return '';
    }
}
