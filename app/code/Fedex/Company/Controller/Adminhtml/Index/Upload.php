<?php
namespace Fedex\Company\Controller\Adminhtml\Index;

use Fedex\Company\Model\ImageUploader;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Upload extends \Magento\Backend\App\Action

{

    /**
     * Upload constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Fedex\Company\Model\ImageUploader $imageUploader
     */
    public function __construct(
        Context $context,
        public ImageUploader $imageUploader
    ) {
        parent::__construct($context);
    }

    /**
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
}
