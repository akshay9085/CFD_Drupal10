<?php

namespace Drupal\cfd_research_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;

class AddProjectTitleForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_project_title_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['new_project_title_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the name of the project title'),
      '#size' => 250,
      '#attributes' => [
        'placeholder' => $this->t('Enter the name of the project title displayed to the contributor'),
      ],
      '#maxlength' => 250,
      '#required' => TRUE,
    ];

    $form['project_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the Link of the project'),
      '#size' => 250,
      '#attributes' => [
        'placeholder' => $this->t('Enter the Link of the project displayed to the contributor'),
      ],
      '#maxlength' => 250,
      '#required' => TRUE,
    ];

    // $form['project_title_resource_file'] = [
    //   '#type' => 'managed_file',
    //   '#title' => $this->t('Upload resource file'),
    //   '#description' => $this->t('Allowed file extensions: pdf, doc, docx, txt'),
    //   '#upload_location' => 'public://project_titles/',
    //   '#upload_validators' => [
    //     'file_validate_extensions' => ['pdf doc docx txt'],
    //   ],
    // ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Additional validation if needed
    if (strlen($form_state->getValue('new_project_title_name')) < 3) {
      $form_state->setErrorByName('new_project_title_name', $this->t('Project title must be at least 3 characters.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Insert project details
    $connection = Database::getConnection();
    $id = $connection->insert('rm_list_of_project_titles')
      ->fields([
        'rm_project_title_name' => $values['new_project_title_name'],
        'rm_project_link' => $values['project_link'],
      ])
      ->execute();

    // Handle file upload
    if (!empty($values['project_title_resource_file'])) {
      $fid = reset($values['project_title_resource_file']);
      if ($file = File::load($fid)) {
        $file->setPermanent();
        $file->save();

        // Update DB with file path
        $connection->update('rm_list_of_project_titles')
          ->fields([
            'filepath' => $file->getFilename(),
          ])
          ->condition('id', $id)
          ->execute();
      }
    }

    $this->messenger()->addStatus($this->t('Project title added successfully.'));
  }
}
