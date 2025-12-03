<?php
declare(strict_types=1);

namespace Fedex\CatalogMvp\Model\Consumer;

use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;

class CheckoutCartProductAddAfterConsumer
{
    public function __construct(
        private ManagerInterface $eventManager,
        private ProductRepository $productRepository,
        private CartRepositoryInterface $quoteRepository
    )
    {}

    /**
     * Run the observer after add the product ot the cart
     *
     * @param $message
     * @return void
     * @throws NoSuchEntityException
     */
    public function process(string $message) : void
    {

        try {
            $message = explode('|', $message);
            $product = $this->productRepository->getById(
                $message[0]
            );
            $quote = $this->quoteRepository->get($message[1]);
        } catch (NoSuchEntityException $e) {
            return;
        }

        $this->eventManager->dispatch(
            'checkout_cart_product_add_after',
            [
                'quote_item' => $quote,
                'product' => $product
            ]
        );
    }
}
