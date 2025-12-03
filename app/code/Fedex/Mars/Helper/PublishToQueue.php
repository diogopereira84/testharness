<?php
/**
 * @category    Fedex
 * @package     Fedex_Mars
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */

namespace Fedex\Mars\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Fedex\Mars\Model\ClientFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * PublishToQueue Helper
 */
class PublishToQueue extends AbstractHelper
{
    /**
     * @param Context $context
     * @param ClientFactory $clientFactory
     * @param PublisherInterface $publisher
     * @param Json $json
     */
    public function __construct(
        Context $context,
        private ClientFactory $clientFactory,
        private PublisherInterface $publisher,
        private Json $json
    ) {
        parent::__construct($context);
    }

    /**
     * PublishToQueue order
     *
     * @param int|null $id
     * @param $type
     */
    public function publish(int|null $id, $type)
    {
        $message = ['id' => $id, 'type' => $type];
        $message = $this->json->serialize($message);
        $this->publisher->publish('mars.event', $message);
    }
}
