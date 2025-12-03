<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Controller\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\CustomerCanvas\Model\Service\CustomerCanvasTokenService;

class Index implements ActionInterface
{
    /**
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param CustomerCanvasTokenService $tokenService
     */
    public function __construct(
        private RequestInterface $request,
        private JsonFactory $jsonFactory,
        private CustomerCanvasTokenService $tokenService
    ) {}

    public function execute(): Json
    {
        $resultJson = $this->jsonFactory->create();
        $canvasParams =[];
        $storeFrontUserId = $this->request->getParam('storeFrontUserId');


        if (empty($storeFrontUserId)) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Invalid or missing storeFrontUserId')
            ]);
        }

        try {
            if(!empty($storeFrontUserId)){
                $canvasParams = $this->tokenService->fetchToken($storeFrontUserId);
            }


            return $resultJson->setData([
                'success' => true,
                'data' => $canvasParams
            ]);
        } catch (NoSuchEntityException) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Product not found.')
            ]);
        } catch (\Throwable $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Unexpected error: %1', $e->getMessage())
            ]);
        }
    }
}
