<?php

/**
 * @file
 * Contains \Drupal\cfd_research_migration\Form\CfdResearchMigrationSettingsForm.
 */

namespace Drupal\cfd_research_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Form\ConfigFormBase;

class CfdResearchMigrationSettingsForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
      return 'cfd_research_migration_settings_form';
    }
    protected function getEditableConfigNames() {
      return [
        'cfd_research_migration.settings',
      ];
    }
  



  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $config = $this->config('cfd_research_migration.settings');
    $form['emails'] = [
      '#type' => 'textfield',
      '#title' => t('(Bcc) Notification emails'),
      '#description' => t('Specify emails id for Bcc option of mail system with comma separated'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('research_migration_emails', ''),
    ];
    $form['cc_emails'] = [
      '#type' => 'textfield',
      '#title' => t('(Cc) Notification emails'),
      '#description' => t('Specify emails id for Cc option of mail system with comma separated'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('research_migration_cc_emails', ''),
    ];
    $form['from_email'] = [
      '#type' => 'textfield',
      '#title' => t('Outgoing from email address'),
      '#description' => t('Email address to be display in the from field of all outgoing messages'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('research_migration_from_email', ''),
    ];
    $form['extensions']['resource_upload'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed file extensions for uploading resource files'),
      '#description' => t('A comma separated list WITHOUT SPACE of source file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('resource_upload_extensions', ''),
    ];
    $form['extensions']['abstract_upload'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed file extensions for abstract'),
      '#description' => t('A comma separated list WITHOUT SPACE of pdf file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('research_migration_abstract_upload_extensions', ''),
    ];
    $form['extensions']['research_migration_upload'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed extensions for project files'),
      '#description' => t('A comma separated list WITHOUT SPACE of pdf file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('research_migration_project_files_extensions', ''),
    ];
    $form['extensions']['list_of_available_projects_file'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed file extensions for file uploaded for available projects list'),
      '#description' => t('A comma separated list WITHOUT SPACE of file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('list_of_available_projects_file', ''),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    // return $form;
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // return;
    return;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('cfd_research_migration.settings')
    
    ->set('research_migration_emails', $form_state->getValue(['emails']))
    ->set('research_migration_cc_emails', $form_state->getValue(['cc_emails']))
    ->set('research_migration_from_email', $form_state->getValue(['from_email']))
    ->set('resource_upload_extensions', $form_state->getValue(['resource_upload']))
    ->set('research_migration_abstract_upload_extensions', $form_state->getValue(['abstract_upload']))
    ->set('research_migration_project_files_extensions', $form_state->getValue(['research_migration_upload']))
    ->set('list_of_available_projects_file', $form_state->getValue(['list_of_available_projects_file']))
    ->save();
    \Drupal::messenger()->addMessage($this->t('Settings updated'), 'status');
  }

}
?>
