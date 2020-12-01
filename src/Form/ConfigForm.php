<?php

namespace Drupal\mail\Form;

use Drupal\commerce_order\Entity\OrderType;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigForm.
 */
class ConfigForm extends ConfigFormBase {


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mail.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mail.config');
    /** @var \Drupal\mail\HelperMail $states */
    $states = \Drupal::service('mail.helper_mail')->getTransitions();

    foreach ($states as $key => $state) {
      $form[$key] = array(
        '#type' => 'fieldset',
        '#title' => $this->t($state['label']),
      );
      $form[$key][$key] = [
        '#type' => 'checkbox',
        '#title' => '<b>'.t($state['label']).'</b>',
        '#default_value' => $config->get($key)?$config->get($key):FALSE,
      ];
      $form[$key][$key.'_text'] = [
        '#type' => 'textfield',
        '#title' => 'text '. t($state['label']),
        '#states' => array(
          'visible' => array(
            ':input[name='.$key.']' => array('checked' => TRUE),
          ),
        ),
        '#default_value' => $config->get($key.'_text'),
      ];
      $form[$key][$key.'_mail'] = [
        '#type' => 'checkboxes',
        '#title' => 'Отправлять письмо',
        '#options' => array('admin' => $this->t('admin'), 'user' => $this->t('user')),
        '#states' => array(
          'visible' => array(
            ':input[name='.$key.']' => array('checked' => TRUE),
          ),
        ),
        '#default_value' => $config->get($key.'_mail'),
      ];
    }


    $form['email'] = array(
      '#type' => 'textfield',
      '#title' => t('Email'),
      '#default_value' => $config->get('email'),
    );

    $form['token_help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['commerce_order'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $value = $form_state->getValues();
    $del = ['submit', "form_build_id", "form_token", "form_id", "op"];
    foreach ($del as $item) {
      unset($value[$item]);
    }
    $config = $this->config('mail.config');
    foreach ($value as $key => $item) {
      $config->set($key, $item);
    }
    $config->save();
  }

}
