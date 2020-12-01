<?php

namespace Drupal\mail\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sends an email when the order transitions to Fulfillment.
 */
class OrderFulfillmentSubscriber implements EventSubscriberInterface {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $config;

  /**
   * Constructs a new OrderFulfillmentSubscriber object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   **/
  public function __construct(MailManagerInterface $mail_manager) {
    $this->mailManager = $mail_manager;
    $this->config = \Drupal::config('mail.config')->get();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_order.place.post_transition' => ['filterEvents'],
      'commerce_order.validate.post_transition' => ['filterEvents'],
      'commerce_order.fulfill.post_transition' => ['filterEvents'],
      'commerce_order.cancel.post_transition' => ['filterEvents'],
    ];
  }

  public function filterEvents(Event $event) {
    if (isset($this->config[$event->getTransition()
          ->getId()]) && $this->config[$event->getTransition()->getId()]) {

      /** @var \Drupal\mail\OrderReceiptMail $mail */
      $mail = \Drupal::service('mail.order_receipt_mail');
      $mail->setTransitionId($event->getTransition()->getId());
      $mail->send($event->getEntity());
    }
  }

}
