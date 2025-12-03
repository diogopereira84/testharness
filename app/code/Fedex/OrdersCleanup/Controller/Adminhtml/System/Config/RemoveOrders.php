<?php
/**
 * @category    Fedex
 * @package     Fedex_OrdersCleanup
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\OrdersCleanup\Controller\Adminhtml\System\Config;

use Fedex\OrdersCleanup\Helper\RemoveOrders as RemoveOrdersHelper;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\StripTags;
use Fedex\OrdersCleanup\Model\Config;

class RemoveOrders extends Action implements HttpPostActionInterface
{

    public const ADMIN_RESOURCE = 'Fedex_OrdersCleanup::config_fedex_orderscleanup';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param StripTags $tagFilter
     * @param RemoveOrdersHelper $removeOrdersHelper
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly StripTags $tagFilter,
        private readonly Config $config,
        private readonly RemoveOrdersHelper $removeOrdersHelper
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = [
            'success' => false,
            'errorMessage' => '',
        ];
        $options = $this->getRequest()->getParams();

        try {
            if (empty($options['date']) || !$this->validateDate($options['date'])) {
                throw new LocalizedException(
                    __('The provided date is either missing or has an invalid format. The right format is YYYY-MM-DD.')
                );
            }

            if (!$this->config->isSgcOrderCleanupProcessEnabled()) {
                throw new LocalizedException(__('SGC-E-455559 Order Cleanup Process is disabled.'));
            }            
            $inputDate = $options['date'];

            $loggedInLastDate = $this->removeOrdersHelper->getLoggedInUserLastDate();
            $guestLastDate = $this->removeOrdersHelper->getGuestUserLastDate();

            if (!$loggedInLastDate && !$guestLastDate) {
                throw new LocalizedException(__('Order cleanup is disabled for both guest and logged-in users.'));
            }

            if (
                ($loggedInLastDate && $inputDate > $loggedInLastDate) &&
                ($guestLastDate && $inputDate > $guestLastDate)
            ) {
                throw new LocalizedException(
                    __('The date you provided must be earlier than or equal to %1 (logged-in) or %2 (guest).', $loggedInLastDate, $guestLastDate)
                );
            }

            $this->removeOrdersHelper->removeOrders($options['date']);
            $result['success'] = true;
        } catch (LocalizedException $e) {
            $result['errorMessage'] = $e->getMessage();
        } catch (\Exception $e) {
            $message = __($e->getMessage());
            $result['errorMessage'] = $this->tagFilter->filter($message);
        }

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($result);
    }

    /**
     * Validate date
     *
     * @param $date
     * @param string $format
     * @return bool
     */
    protected function validateDate($date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}