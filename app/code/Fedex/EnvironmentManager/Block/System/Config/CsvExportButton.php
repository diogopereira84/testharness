<?php
/**
 * @category  Fedex
 * @package   Fedex_EnvironmentManager
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button as WidgetButton;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class CsvExportButton extends Field
{
    protected $_template = 'Fedex_EnvironmentManager::system/config/csv_export_button.phtml';

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        private LoggerInterface $logger,
        array $data = []
    )
    {
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * @return mixed
     */
    public function getButtonHtml(): mixed
    {
        try {
            $button = $this->getLayout()
                ->createBlock(WidgetButton::class)
                ->setData(
                    [
                        'id' => 'export_toggle_report_csv',
                        'label' => __('Export')
                    ]
                );

            if ($button === null) {
                throw new LocalizedException(__('Failed to create toggle export CSV button block.'));
            }

            return $button->toHtml();
        } catch (LocalizedException $le) {
            $this->logger->error($le->getMessage());
            return '';
        }
    }
}
