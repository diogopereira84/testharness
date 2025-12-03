<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Company\Block\Adminhtml\Edit;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Company\Block\Adminhtml\Edit\GenericButton;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class ExportButton
 */
class ExportButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @param ToggleConfig $toggleConfig
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        protected ToggleConfig $toggleConfig,
        RequestInterface $request,
        UrlInterface $urlBuilder
    )
    {
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Get button data.
     *
     * @return array
     */
    public function getButtonData()
    {
        $companyId = $this->request->getParam('id');
        if ($companyId) {

            return [
                'label' => __('Export'),
                'class' => 'export',
                'id' => 'company-edit-export-button',
                'on_click' => sprintf("location.href = '%s';", $this->getExportUrl($companyId)),
                'sort_order' => 21,
            ];
        }

        return [];
    }

    /**
     * Get export url.
     *
     * @param int $companyId
     * @return string
     */
    public function getExportUrl($companyId)
    {
        return $this->urlBuilder->getUrl('company/index/export', ['id' => $companyId]);
    }
}
