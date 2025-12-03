<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Model;

use Magento\Framework\Api\ImageContentFactory;
use Magento\Framework\Api\ImageContentValidator;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Filesystem;
use Magento\Framework\Api\ImageContent;
use Magento\Framework\Image\AdapterFactory;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Fedex\Canva\Api\TemplateImageConverterInterface;
use Psr\Log\LoggerInterface;

/**
 * Class TemplateImageConverter
 * Implementation of TemplateImageConverterInterface, that converts base64Image and save in the filesystem.
 */
class TemplateImageConverter implements TemplateImageConverterInterface
{
    /**
     * @var string
     */
    private string $imagePath;

    /**
     * @var ImageContent
     */
    private ImageContent $imageContent;

    /**
     * @param Filesystem $filesystem
     * @param ImageContentValidator $imageContentValidator
     * @param ImageContentFactory $imageContentFactory
     * @param Database $mediaStorage
     * @param AdapterFactory $imageAdapterFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private Filesystem $filesystem,
        private ImageContentValidator $imageContentValidator,
        private ImageContentFactory $imageContentFactory,
        private Database $mediaStorage,
        private AdapterFactory $imageAdapterFactory,
        protected LoggerInterface $logger
    ) {
        $this->imageContent = $this->imageContentFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function getImagePath(): string
    {
        return $this->imagePath ?? "";
    }

    /**
     * @inheritdoc
     */
    public function convert(string $base64Image, string $name): TemplateImageConverterInterface
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $image = base64_decode($base64Image);
        $imageProperties = @getimagesizefromstring($image);// phpcs:ignore
        if (!$imageProperties) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Unable to get properties from image.');
            throw new InputException(__('Unable to get properties from image.'));
        }
        $this->imageContent = $this->imageContentFactory->create();
        $this->imageContent->setBase64EncodedData($base64Image);
        $this->imageContent->setType($imageProperties['mime']);

        $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $fileName = str_replace(' ', '', uniqid(strtolower($name), true) . '.jpg');
        $this->imagePath = '.template-manager' . DIRECTORY_SEPARATOR . $fileName;
        $this->imageContent->setName($fileName);

        if ($this->imageContentValidator->isValid($this->imageContent)) {
            // Write the file to the directory
            $mediaDir->writeFile(
                $this->imagePath,
                $image
            );

            // Generate a thumbnail, called -thumb next to the image for usage in the grid
            $thumbPath = str_replace('.jpg', '-thumb.jpg', $this->imagePath);
            $imageFactory = $this->imageAdapterFactory->create();
            $imageFactory->open($mediaDir->getAbsolutePath() . $this->imagePath);
            $imageFactory->resize(350);
            $imageFactory->save($mediaDir->getAbsolutePath() . $thumbPath);
            $this->mediaStorage->saveFile($this->imagePath);
            $this->mediaStorage->saveFile($thumbPath);
        }

        return $this;
    }
}
