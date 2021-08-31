<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Livescale\Payment\Model\Resolver;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Payment\Model\Method\Logger;

/**
 * Class AuthorizePayment
 */
class AuthorizePayment implements ResolverInterface
{
      /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
      if (empty($args['input']['cart_id'])) {
        throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
      }
      if (empty($args['input']['gateway_transaction_id'])) {
        throw new GraphQlInputException(__('Required parameter "gateway_transaction_id" is missing'));
      }
      if (empty($args['input']['gateway_name'])) {
        throw new GraphQlInputException(__('Required parameter "gateway_name" is missing'));
      }

      $maskedCartId = $args['input']['cart_id'];

      $this->logger->debug([
        'maskedCartId' => $maskedCartId
      ]);

      $quote = $this->quoteRepository->get($maskedCartId);
      $this->logger->debug([
        'quote' => $quote
      ]);

      $payment = $quote->getPayment();
      $this->logger->debug([
        'payment' => $payment
      ]);

      $paymentId = $payment->getId();
      $this->logger->debug([
            'paymentId' => $paymentId
      ]);

      $currentUserId = $context->getUserId();
      $this->logger->debug([
        'currentUserId' => $currentUserId
      ]);

      if ($currentUserId !== 0) {
          throw new GraphQlInputException(__('The request is not allowed for logged in customers'));
      }

      $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
      $this->logger->debug([
        'storeId' => $storeId
      ]);
      $cart = $this->getCartForUser->execute($maskedCartId, $currentUserId, $storeId);
      $this->logger->debug([
        'cart' => $cart
      ]);

      return [
        'cart' => [
            'model' => $cart,
        ],
    ];
    }
}