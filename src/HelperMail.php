<?php

namespace Drupal\mail;


use Drupal\commerce_order\Entity\OrderType;
use Drupal\state_machine\WorkflowManager;
use Drupal\state_machine\WorkflowManagerInterface;

class HelperMail {

  protected $workflow_manager;

  /**
   * HelperMail constructor.
   *
   * @param $workflow_manager
   */
  public function __construct(WorkflowManagerInterface $workflow_manager) {
    $this->workflow_manager = $workflow_manager;
  }

  public function getTransitions() :array
  {
    $orders_type = OrderType::loadMultiple();
    $states = [];
    foreach ($orders_type as $order_type) {
      /** @var WorkflowManager $workflow */
      $workflow = $this->workflow_manager->getDefinition($order_type->getWorkflowId());
      $states = $workflow['transitions'];
    }
    return $states;
  }

}
