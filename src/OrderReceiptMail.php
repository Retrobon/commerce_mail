<?php

namespace Drupal\mail;

use Drupal\commerce\MailHandlerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderTotalSummaryInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;


class OrderReceiptMail extends \Drupal\commerce_order\Mail\OrderReceiptMail {

  private $transitionId;

  /**
   * {@inheritdoc}
   */
  public function send(OrderInterface $order, $text = NULL, $to = NULL) {
    $config = \Drupal::config('mail.config')->get();
    $token_service = \Drupal::token();

    $subject = $token_service->replace($config[$this->getTransitionId() . '_text'], ['commerce_order' => $order]);
    $admin = $config[$this->getTransitionId() . '_mail']['admin'] ? $config['email'] : NULL;
    $user = $config[$this->getTransitionId() . '_mail']['user'] ? $order->getEmail() : NULL;

    if (!$user && !$admin) {
      return FALSE;
    }

    $body = [
      '#theme' => $config[$this->getTransitionId().'_theme'],
      '#order_entity' => $order,
      '#totals' => $this->orderTotalSummary->buildTotals($order),
    ];
    if ($billing_profile = $order->getBillingProfile()) {
      $body['#billing_information'] = $this->profileViewBuilder->view($billing_profile);
    }
    $summary = \Drupal::service('commerce_shipping.order_shipment_summary')->build($order);
    if (!empty($summary)) {
      $body['#shipping_information'] = $summary;
    }
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $order->get('payment_gateway')->entity;
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $order->get('payment_method')->entity;

    $body['#payment_method'] = NULL;
    if ($payment_method) {
      $body['#payment_method'] = [
        '#markup' => $payment_method->label(),
      ];
    }
    elseif ($payment_gateway) {
      $payment_gateway_plugin = $payment_gateway->getPlugin();
      if (!($payment_gateway_plugin instanceof SupportsStoredPaymentMethodsInterface)) {
        $body['#payment_method'] = [
          '#markup' => $payment_gateway_plugin->getDisplayLabel(),
        ];
      }
    }
    $params = [
      'id' => 'order_receipt',
      'from' => $order->getStore()->getEmail(),
      'order' => $order,
    ];
    if ($admin) {
      $this->mailHandler->sendMail($admin, $subject, $body, $params);
    }
    if ($user) {
      $this->mailHandler->sendMail($user, $subject, $body, $params);
    }
  }

  /**
   * @return mixed
   */
  public function getTransitionId() {
    return $this->transitionId;
  }

  /**
   * @param mixed $transitionId
   */
  public function setTransitionId($transitionId): void {
    $this->transitionId = $transitionId;
  }

}
