<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Controller\Adminhtml\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\AuthorizationInterface;
use Fedex\CIDPSG\Model\ImageUploader;

class Upload implements ActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    private const ADMIN_RESOURCE = 'Fedex_CIDPSG::upload';

    /**
     * Upload constructor.
     *
     * @param AuthorizationInterface $authorizationInterface
     * @param ImageUploader $imageUploader
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        protected AuthorizationInterface $authorizationInterface,
        protected ImageUploader $imageUploader,
        protected ResultFactory $resultFactory
    )
    {
    }

    /**
     * Execute method
     *
     * @return mixed
     */
    public function execute()
    {
        try {
            $result = $this->imageUploader->saveFileToTmpDir();
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }

    /**
     * Check current user permission on resource and privilege
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->authorizationInterface->isAllowed(self::ADMIN_RESOURCE);
    }
}
