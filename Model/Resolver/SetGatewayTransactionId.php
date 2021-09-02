<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Livescale\Payment\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\QuoteGraphQl\Model\Cart\CheckCartCheckoutAllowance;

use Magento\Payment\Model\Method\Logger;

/**
 * @inheritdoc
 */
class SetGatewayTransactionId implements ResolverInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CheckCartCheckoutAllowance
     */
    private $checkCartCheckoutAllowance;

    /**
     * @var PaymentMethodManagementInterface
     */
    private $paymentMethodManagement;

    /**
     * @param GetCartForUser $getCartForUser
     * @param CartManagementInterface $cartManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param CheckCartCheckoutAllowance $checkCartCheckoutAllowance
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     */
    public function __construct(
        Logger $logger,
        GetCartForUser $getCartForUser,
        CartManagementInterface $cartManagement,
        OrderRepositoryInterface $orderRepository,
        CheckCartCheckoutAllowance $checkCartCheckoutAllowance,
        PaymentMethodManagementInterface $paymentMethodManagement
    ) {
        $this->logger = $logger;
        $this->getCartForUser = $getCartForUser;
        $this->cartManagement = $cartManagement;
        $this->orderRepository = $orderRepository;
        $this->checkCartCheckoutAllowance = $checkCartCheckoutAllowance;
        $this->paymentMethodManagement = $paymentMethodManagement;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        // if (empty($args['input']['order_number'])) {
        //     throw new GraphQlInputException(__('Required parameter "order_number" is missing'));
        // }

        if (empty($args['input']['gateway_transaction_id'])) {
            throw new GraphQlInputException(__('Required parameter "gateway_transaction_id" is missing'));
        }

        // $orderNumber = $args['input']['order_number'];
        $gatewayTransactionId = $args['input']['gateway_transaction_id'];

        // $this->logger->debug([
        //     'orderNumber' => $orderNumber,
        //     'gateway_transaction_id' => $gatewayTransactionId
        //   ]);

        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }
        $maskedCartId = $args['input']['cart_id'];

        $currentUserId = $context->getUserId();
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $cart = $this->getCartForUser->execute($maskedCartId, $currentUserId, $storeId);

        try {
            // $order = $this->orderRepository->get($orderNumber);

            // $payment = $order->getPayment();
            $payment = $cart->getPayment();
            $payment->setAdditionalInformation('gatewayTransactionId', $gatewayTransactionId);
            $payment->setAdditionalData('gatewayTransactionId', $gatewayTransactionId);
            // $payment->setTransactionId($gatewayTransactionId);
            // $payment->setAmountAuthorized();
            // $payment->setCcExpMonth();
            // $payment->setCcExpYear();
            // $payment->setCcLast4();
            // $payment->setCcOwner();
            // $payment->setCcOwner();
            // $payment->setIsTransactionClosed(false);
            $payment->save();
            // $order->setPayment($payment);

            $info = $payment->getAdditionalInformation('gatewayTransactionId');
    
            $this->logger->debug(['info in set' => $info]);

            return [
                'succeed' => true,
            ];
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__('Unable to place order: %message', ['message' => $e->getMessage()]), $e);
        }
    }
}