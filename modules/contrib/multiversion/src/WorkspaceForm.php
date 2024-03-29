<?php

/**
 * @file
 * Contains \Drupal\multiversion\WorkspaceForm.
 */

namespace Drupal\multiversion;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the workspace edit forms.
 */
class WorkspaceForm extends ContentEntityForm {

  /**
   * The workspace content entity.
   *
   * @var \Drupal\multiversion\Entity\WorkspaceInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $workspace = $this->entity;
    $form = parent::form($form, $form_state, $workspace);

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit workspace %label', array('%label' => $workspace->label()));
    }
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $workspace->label(),
      '#description' => $this->t("Label for the Endpoint."),
      '#required' => TRUE,
    );

    $form['machine_name'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Workspace ID'),
      '#maxlength' => 255,
      '#default_value' => $workspace->get('machine_name')->value,
      '#machine_name' => array(
        'exists' => '\Drupal\multiversion\Entity\Workspace::load',
      ),
      '#element_validate' => array(),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditedFieldNames(FormStateInterface $form_state) {
    return array_merge(array(
      'label',
      'machine_name',
    ), parent::getEditedFieldNames($form_state));
  }

  /**
   * {@inheritdoc}
   */
  protected function flagViolations(EntityConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    // Manually flag violations of fields not handled by the form display. This
    // is necessary as entity form displays only flag violations for fields
    // contained in the display.
    $field_names = array(
      'label',
      'machine_name'
    );
    foreach ($violations->getByFields($field_names) as $violation) {
      list($field_name) = explode('.', $violation->getPropertyPath(), 2);
      $form_state->setErrorByName($field_name, $violation->getMessage());
    }
    parent::flagViolations($violations, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $workspace = $this->entity;
    $insert = $workspace->isNew();
    $workspace->save();
    $info = ['%info' => $workspace->label()];
    $context = array('@type' => $workspace->bundle(), $info);
    $logger = $this->logger('multiversion');

    if ($insert) {
      $logger->notice('@type: added %info.', $context);
      drupal_set_message($this->t('Workspace %info has been created.', $info));
    }
    else {
      $logger->notice('@type: updated %info.', $context);
      drupal_set_message($this->t('Workspace %info has been updated.', $info));
    }

    if ($workspace->id()) {
      $form_state->setValue('id', $workspace->id());
      $form_state->set('id', $workspace->id());
      $form_state->setRedirectUrl($workspace->urlInfo('collection'));
    }
    else {
      drupal_set_message($this->t('The workspace could not be saved.'), 'error');
      $form_state->setRebuild();
    }
  }

}
