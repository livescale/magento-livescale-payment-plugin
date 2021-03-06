<?php
/**
 * Copyright © 2021 Livescale. All rights reserved.
 * See LICENSE for license details.
 */
namespace Livescale\Payment\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\Method\Logger;

class AuthorizationHandle implements HandlerInterface
{
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $this->logger->debug(['step' => 'authorizeHandle']);
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        if ($payment->hasAdditionalInformation('ccType')) {
            $ccType = $payment->getAdditionalInformation('ccType');
            $payment->setCcType($ccType);
        }

        if ($payment->hasAdditionalInformation('ccExpirationMonth')) {
            $ccExpirationMonth = $payment->getAdditionalInformation('ccExpirationMonth');
            $payment->setCcExpMonth($ccExpirationMonth);
        }

        if ($payment->hasAdditionalInformation('ccExpirationYear')) {
            $ccExpirationYear = $payment->getAdditionalInformation('ccExpirationYear');
            $payment->setCcExpYear($ccExpirationYear);
        }

        if ($payment->hasAdditionalInformation('ccLast4')) {
            $ccLast4 = $payment->getAdditionalInformation('ccLast4');
            $payment->setCcLast4($ccLast4);
        }

        if ($payment->hasAdditionalInformation('ccHolder')) {
            $ccHolder = $payment->getAdditionalInformation('ccHolder');
            $payment->setCcOwner($ccHolder);
        }
 
        if ($payment->hasAdditionalInformation('gatewayTransactionId')) {
            $transactionId = $payment->getAdditionalInformation('gatewayTransactionId');
            $payment->setTransactionId($transactionId);
            $payment->setIsTransactionClosed(false);
        }
    }
}
