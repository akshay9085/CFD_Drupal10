<?php
namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Form\ConfigFormBase;


class LabMigrationSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_settings_form';
  }
  protected function getEditableConfigNames() {
    return [
      'lab_migration.settings',
    ];
  }
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('lab_migration.settings');

    $form['emails'] = [
      '#type' => 'textfield',
      '#title' => $this->t('(Bcc) Notification emails'),
      '#description' => $this->t('Specify emails id for Bcc option of mail system with comma separated'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('lab_migration_emails', ''),
    ];
    $form['cc_emails'] = [
      '#type' => 'textfield',
      '#title' => $this->t('(Cc) Notification emails'),
      '#description' => $this->t('Specify emails id for Cc option of mail system with comma separated'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('lab_migration_cc_emails', ''),
    ];
    $form['from_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Outgoing from email address'),
      '#description' => $this->t('Email address to be display in the from field of all outgoing messages'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('lab_migration_from_email', ''),
    ];
    $form['extensions']['proposal_problem_statement'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed extensions for the problem statement in the Proposal form'),
      '#description' => $this->t('A comma separated list WITHOUT SPACE of file extensions for the problem statement that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('lab_migration_problem_statement_extensions', ''),
    ];
    $form['extensions']['source'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed source file extensions'),
      '#description' => $this->t('A comma separated list WITHOUT SPACE of source file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('lab_migration_source_extensions', ''),
    ];
    $form['extensions']['code_submission_result_pdf'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed file extensions for results in the form of PDF'),
      '#description' => $this->t('A comma separated list WITHOUT SPACE of report file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('lab_migration_result_pdf_extensions', ''),
    ];
    $form['extensions']['result_zip'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed result file extensions in the zip format'),
      '#description' => $this->t('A comma separated list WITHOUT SPACE of result file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('lab_migration_result_extensions', ''),
    ];
    $form['extensions']['pdf'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed pdf file extensions'),
      '#description' => $this->t('A comma separated list WITHOUT SPACE of pdf file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('lab_migration_pdf_extensions', ''),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' =>$this->t('Submit'),
    ];
    return parent::buildForm($form, $form_state);
    //return;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // return;
    parent::validateForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('lab_migration.settings')

    ->set('lab_migration_emails', $form_state->getValue(['emails']))
    ->set('lab_migration_cc_emails', $form_state->getValue(['cc_emails']))
    ->set('lab_migration_from_email', $form_state->getValue(['from_email']))
    ->set('lab_migration_problem_statement_extensions', $form_state->getValue(['proposal_problem_statement']))
    ->set('lab_migration_source_extensions', $form_state->getValue(['source']))
    ->set('lab_migration_result_pdf_extensions', $form_state->getValue(['code_submission_result_pdf']))
    ->set('lab_migration_result_extensions', $form_state->getValue(['result_zip']))
    ->set('lab_migration_pdf_extensions', $form_state->getValue(['pdf']))
    ->save();
    \Drupal::messenger()->addmessage($this->t('Settings updated'), 'status');
  }

}
?>