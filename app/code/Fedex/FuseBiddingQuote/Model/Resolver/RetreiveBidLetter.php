<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Fedex\FuseBiddingQuote\Helper\FuseBidGraphqlHelper;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\View\LayoutInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\FuseBiddingQuote\Block\EmailTemplate;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * RetreiveBidLetter class graphql API
 */
class RetreiveBidLetter implements ResolverInterface
{
    /**
     * InitializeNegotiableQuote constructor.
     *
     * @param FuseBidGraphqlHelper $fuseBidGraphqlHelper
     * @param QuoteIdMask $quoteIdMaskResource
     * @param FilterProvider $filterProvider
     * @param LayoutInterface $layout
     * @param CartRepositoryInterface $quoteRepository
     * @param Dompdf $dompdf
     * @param Options $options
     */
    public function __construct(
        protected FuseBidGraphqlHelper $fuseBidGraphqlHelper,
        protected QuoteIdMask $quoteIdMaskResource,
        protected FilterProvider $filterProvider,
        protected LayoutInterface $layout,
        protected CartRepositoryInterface $quoteRepository,
        protected Dompdf $dompdf,
        protected Options $options
    )
    {
    }

    /**
     * Resolve method for retrieving cart UID from quote ID.
     *
     * @param Field $field
     * @param mixed $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|null
     * @throws GraphQlInputException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): ?array {
        $this->fuseBidGraphqlHelper->validateCartUid($args);
        $this->fuseBidGraphqlHelper->validateTemplate($args);
        $maskedCartId = $args['uid'];
        $quoteId = $this->quoteIdMaskResource->getUnmaskedQuoteId($maskedCartId);
        $quote = $this->quoteRepository->get($quoteId);
        $nbc = false;
        if ($args['template'] == 'NBC_SUPPORT') {
            $nbc = true;
        }
        $emailData = [
            'quote_data' => [
                'quote_id' => $quote->getId(),
                'status' => NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN,
                'is_bid' => true,
                'nbc' => $nbc
            ],
            'cutomer_email' => $quote->getCustomerEmail()
        ];
        $emailhtml = $this->layout->createBlock(EmailTemplate::class,'',['data' => $emailData])
        ->setData('area','frontend')
        ->setTemplate('Fedex_FuseBiddingQuote::pdf/quote_email.phtml')->toHtml();
        $finalpdf = $this->filterProvider->getBlockFilter()->filter($emailhtml);
        $this->options->set('isRemoteEnabled', TRUE);
        $this->dompdf->setOptions($this->options);
        $this->dompdf->setHttpContext(
            stream_context_create([
                'ssl' => [
                    'allow_self_signed'=> TRUE,
                    'verify_peer' => FALSE,
                    'verify_peer_name' => FALSE,
                ]
            ])
        );
        $this->dompdf->load_html($finalpdf);
        $this->dompdf->setPaper('A4', 'portrait');
        $this->dompdf->render();
        $output = base64_encode($this->dompdf->output());

        return ['data' => $output];
    }
}
