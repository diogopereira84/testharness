<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\MarketplaceCheckout\Api\EmailInterface;
use Magento\Email\Model\AbstractTemplate;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\Factory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Area;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Stdlib\DateTime\Timezone\LocalizedDateToUtcConverterInterface;

class Email implements EmailInterface
{
    private const ALLOWED_STRIP_TAGS = '<br><img><a>';

    /**
     * @param Factory $template
     * @param StoreManagerInterface $storeManager
     * @param ToggleConfig $toggleConfig
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param UrlInterface $urlBuilder
     * @param TimezoneInterface $timezone
     * @param LocalizedDateToUtcConverterInterface $utcConverter
     */
    public function __construct(
        private Factory $template,
        private StoreManagerInterface $storeManager,
        private ToggleConfig $toggleConfig,
        private ScopeConfigInterface $scopeConfigInterface,
        private UrlInterface $urlBuilder,
        private TimezoneInterface $timezone,
        private LocalizedDateToUtcConverterInterface $utcConverter
    ) {
    }

    /**
     * @param string $templateName
     * @param array $orderData
     * @return array
     * @throws NoSuchEntityException
     */
    public function getEmailHtml(string $templateName, array $orderData): array
    {
        $templateId = (int) $this->toggleConfig->getToggleConfig($templateName);

        if ($templateId == 0) {
            $templateId = str_replace('/', '_', $templateName);
        }
        $html = [];
        $template = $this->template->get($templateId, null)
            ->setVars($orderData)
            ->setOptions([
                'area' => Area::AREA_FRONTEND,
                'store' => $this->storeManager->getStore()->getId(),
            ]);
        $html['template'] = $template->processTemplate();
        $html['subject'] = html_entity_decode((string)$template->getSubject(), ENT_QUOTES);

        return $html;
    }

    /**
     * @return string
     */
    public function getEmailLogoUrl(): string
    {
        $path = $this->scopeConfigInterface->getValue(
            AbstractTemplate::XML_PATH_DESIGN_EMAIL_LOGO,
            ScopeInterface::SCOPE_STORE
        );

        if ($path) {
            $uploadDir = \Magento\Email\Model\Design\Backend\Logo::UPLOAD_DIR;
            $logoUrl = $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA])
                . $uploadDir . '/' . $path;
        }

        return $logoUrl ?? '';
    }

    /**
     * @param string $html
     * @return string
     */
    public function convertBase64(string $html): string
    {
        return 'base64:' . base64_encode($html);
    }

    /**
     * Minify HTMl
     * @param string $html
     * @return string
     */
    public function minifyHtml(string $html): string
    {
        $html = preg_replace_callback('/style=\'(.*?)\'/', function ($matches) {
            $style = str_replace('"', '', $matches[1]);
            return "style='$style'";
        }, $html);

        $search = array(
            '/(\n|^)(\x20+|\t)/',
            '/(\n|^)\/\/(.*?)(\n|$)/',
            '/\n/',
            '/\<\!--.*?-->/',
            '/(\x20+|\t)/', # Delete multispace (Without \n)
            '/\>\s+\</', # strip whitespaces between tags
            '/(\"|\')\s+\>/', # strip whitespaces between quotation ("') and end tags
            '/=\s+(\"|\')/',
            '/(")/',
            '/\\\\(2014|00A0)/',
            '/[\n\r]/'
        ); # strip whitespaces between = "'

        $replace = array(
            "\n",
            "\n",
            " ",
            "",
            " ",
            "><",
            "$1>",
            "=$1",
            "'",
            "",
            ""
        );

        return preg_replace($search, $replace, $html);
    }

    /**
     * Get date in CST format
     *
     * @param string $datetime
     * @return string
     */
    public function getFormattedCstDate(string $datetime): string
    {
        if ($this->toggleConfig->getToggleConfigValue('mazegeeks_D192133_fix')) {
            $datetime = $this->utcConverter->convertLocalizedDateToUtc($datetime);
        }

        return $this->timezone->date($datetime)->setTimezone(new \DateTimeZone('CST'))
            ->format('M d, Y \a\t h:i A \C\S\T');
    }
}
