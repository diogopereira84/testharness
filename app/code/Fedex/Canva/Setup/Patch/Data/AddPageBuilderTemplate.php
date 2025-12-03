<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Setup\Patch\Data;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\PageBuilder\Api\Data\TemplateInterface;
use Magento\PageBuilder\Api\Data\TemplateInterfaceFactory;
use Magento\PageBuilder\Api\TemplateRepositoryInterface;
use Fedex\Canva\Api\TemplateImageConverterInterfaceFactory;
use Fedex\Canva\Model\ContentReader;

/**
 * Class AddPageBuilderTemplate
 * This setup Creates the Design Punch-out templates
 * Based on B-744340
 *
 * @codeCoverageIgnore
 */
class AddPageBuilderTemplate implements DataPatchInterface, PatchRevertableInterface
{
    public const TEMPLATE_VAR_PROMO_CONTENT_PHOTOBOOK_BLOCK_ID = '{{TEMPLATE_VAR_PROMO_CONTENT_PHOTOBOOK_BLOCK_ID}}';
    public const TEMPLATE_VAR_PROMO_CONTENT_CALENDAR_BLOCK_ID = '{{TEMPLATE_VAR_PROMO_CONTENT_CALENDAR_BLOCK_ID}}';
    public const TEMPLATE_VAR_PROMO_CONTENT_THIRD_PARTY_BLOCK_ID = '{{TEMPLATE_VAR_PROMO_CONTENT_THIRD_PARTY_BLOCK_ID}}';// phpcs:ignore
    public const TEMPLATE_VAR_PHOTOBOOK_PRODUCT_HIGHLIGHT_2_BLOCK_ID = '{{TEMPLATE_VAR_PHOTOBOOK_PRODUCT_HIGHLIGHT_2_BLOCK_ID}}';// phpcs:ignore
    public const TEMPLATE_VAR_PHOTOBOOK_CONTENT_BLOCK_ID = '{{TEMPLATE_VAR_PHOTOBOOK_CONTENT_BLOCK_ID}}';
    public const TEMPLATE_VAR_PHOTOBOOK_HERO_BANNER_BLOCK_ID = '{{TEMPLATE_VAR_PHOTOBOOK_HERO_BANNER_BLOCK_ID}}';
    public const TEMPLATE_VAR_PHOTOBOOK_PRODUCT_HIGHLIGHT_1_BLOCK_ID = '{{TEMPLATE_VAR_PHOTOBOOK_PRODUCT_HIGHLIGHT_1_BLOCK_ID}}';// phpcs:ignore
    public const TEMPLATE_VAR_G7_MASTER_PRINTER_CERT_BLOCK_ID = '{{TEMPLATE_VAR_G7_MASTER_PRINTER_CERT_BLOCK_ID}}';
    public const TEMPLATE_BLOCK_IDS_MAP = [
        'promo-content-photobook' => self::TEMPLATE_VAR_PROMO_CONTENT_PHOTOBOOK_BLOCK_ID,
        'promo-content-calendar' => self::TEMPLATE_VAR_PROMO_CONTENT_CALENDAR_BLOCK_ID,
        'promo-content-third-party' => self::TEMPLATE_VAR_PROMO_CONTENT_THIRD_PARTY_BLOCK_ID,
        'photobook-product-highlight-2' => self::TEMPLATE_VAR_PHOTOBOOK_PRODUCT_HIGHLIGHT_2_BLOCK_ID,
        'photobook-content' => self::TEMPLATE_VAR_PHOTOBOOK_CONTENT_BLOCK_ID,
        'photobook-hero-banner' => self::TEMPLATE_VAR_PHOTOBOOK_HERO_BANNER_BLOCK_ID,
        'Photobook-product-highlight-1' => self::TEMPLATE_VAR_PHOTOBOOK_PRODUCT_HIGHLIGHT_1_BLOCK_ID,
        'g7-master-printer-certification' => self::TEMPLATE_VAR_G7_MASTER_PRINTER_CERT_BLOCK_ID,
    ];

    /**
     * @var TemplateInterfaceFactory
     */
    private TemplateInterfaceFactory $templateFactory;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ContentReader $contentReader
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     * @param BlockInterfaceFactory $blockFactory
     * @param BlockRepositoryInterface $blockRepository
     * @param TemplateInterfaceFactory $templateInterfaceFactory
     * @param TemplateRepositoryInterface $templateRepository
     * @param TemplateImageConverterInterfaceFactory $imageConverterFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private ContentReader $contentReader,
        private LoggerInterface $logger,
        private SerializerInterface $serializer,
        private BlockInterfaceFactory $blockFactory,
        private BlockRepositoryInterface $blockRepository,
        TemplateInterfaceFactory $templateInterfaceFactory,
        private TemplateRepositoryInterface $templateRepository,
        private TemplateImageConverterInterfaceFactory $imageConverterFactory
    ) {
        $this->templateFactory = $templateInterfaceFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        try {
            $blocks = $this->serializer->unserialize(trim($this->contentReader->getContent('blocks.json')));
            $blockModels = [];
            foreach ($blocks as $block) {
                $blockModel = $this->blockFactory->create([ 'data' => $block]);
                $blockModel = $this->blockRepository->save($blockModel);
                $blockModels[] = $blockModel;
            }
            $templates = $this->serializer->unserialize(
                trim($this->contentReader->getContent('templates.json'))
            );
            foreach ($templates as $template) {
                $templateModel = $this->templateFactory->create();
                $imageConverter = $this->imageConverterFactory->create();
                $templateModel->setName($template[TemplateInterface::KEY_NAME]);
                $templateModel->setPreviewImage(
                    $imageConverter
                        ->convert(
                            $template[TemplateInterface::KEY_PREVIEW_IMAGE],
                            $template[TemplateInterface::KEY_NAME]
                        )->getImagePath()
                );
                $templateCode = $template[TemplateInterface::KEY_TEMPLATE];
                foreach ($blockModels as $blockModel) {
                    $templateCode = str_replace(
                        self::TEMPLATE_BLOCK_IDS_MAP[$blockModel->getIdentifier()],
                        (string)$blockModel->getId(),
                        $templateCode
                    );
                }
                $templateModel->setCreatedFor($template[TemplateInterface::KEY_CREATED_FOR]);
                $templateModel->setTemplate($templateCode);
                $this->templateRepository->save($templateModel);
            }
        } catch (Exception $exception) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $exception->getMessage());
        }
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function revert(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }
}
