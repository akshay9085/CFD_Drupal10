<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationCertificateParticipationEditForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class LabMigrationCertificateParticipationEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_certificate_participation_edit_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $type = arg(2);
    $action = arg(4);
    $proposal_id = arg(5);
    $certi_id = arg(6);
    if ($type == "lm-participation" && $action == "edit") {
      $query = \Drupal::database()->query("SELECT * FROM lab_migration_certificate WHERE proposal_id=:prop_id AND id=:certi_id", [
        ":prop_id" => $proposal_id,
        ":certi_id" => $certi_id,
      ]);
      $details_list = $query->fetchobject();
      if ($details_list->type == "Participant") {
        $form['name_title'] = [
          '#type' => 'select',
          '#title' => t('Title'),
          '#options' => [
            'Dr.' => 'Dr.',
            'Prof.' => 'Prof.',
            'Mr.' => 'Mr.',
            'Mrs.' => 'Mrs.',
            'Ms.' => 'Ms.',
          ],
          '#default_value' => $details_list->name_title,
        ];
        $form['name'] = [
          '#type' => 'textfield',
          '#title' => t('Name of Participant'),
          '#maxlength' => 50,
          '#default_value' => $details_list->name,
        ];
        $form['email_id'] = [
          '#type' => 'textfield',
          '#title' => t('Email'),
          '#size' => 50,
          '#default_value' => $details_list->email_id,
        ];
        $form['institute_name'] = [
          '#type' => 'textfield',
          '#title' => t('college / Institue Name'),
          '#default_value' => $details_list->institute_name,
        ];
        $form['institute_address'] = [
          '#type' => 'textfield',
          '#title' => t('college / Institue address'),
          '#default_value' => $details_list->institute_address,
        ];
        $form['lab_name'] = [
          '#type' => 'textfield',
          '#title' => t('Lab name'),
          '#default_value' => $details_list->lab_name,
        ];
        $form['department'] = [
          '#type' => 'textfield',
          '#title' => t('Department'),
          '#default_value' => $details_list->department,
        ];
        $form['semester_details'] = [
          '#type' => 'textfield',
          '#title' => t('Semester details and department'),
          '#description' => 'Eg. 5th or 2nd',
          '#default_value' => $details_list->semester_details,
        ];
        $form['proposal_id'] = [
          '#type' => 'textfield',
          '#title' => t('Lab Proposal Id'),
          '#description' => 'Note: You can find  the respective Lab Proposal Id from the url for the  completed lab. For example: The Lab Proposal Id is 64 for this completed lab. ( Url - cfd.fossee.in/lab-migration/lab-migration-run/64)',
          '#default_value' => $details_list->proposal_id,
        ];
        $form['certi_id'] = [
          '#type' => 'hidden',
          '#default_value' => $details_list->id,
        ];
        $form['submit'] = [
          '#type' => 'submit',
          '#value' => t('Submit'),
        ];
      } //$details_list->type == "Participant"
      else {
        $form['err_message'] = [
          '#type' => 'item',
          '#title' => t('Message'),
          '#markup' => 'Invalid information',
        ];
      }
    } //$type == "lm_participation" && $action == "edit"
    else {
      $form['err_message'] = [
        '#type' => 'item',
        '#title' => t('Message'),
        '#markup' => 'Invalid information',
      ];
    }
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $v = $form_state->getValues();
    $result = "UPDATE lab_migration_certificate SET
  uid=:uid, 
  name_title=:name_title, 
  name=:name, 
  email_id=:email_id, 
  institute_name=:institute_name, 
  institute_address=:institute_address, 
  lab_name=:lab_name, 
  department=:department, 
  semester_details=:semester_details,
  proposal_id=:proposal_id,
  type=:type,
  creation_date=:creation_date
  WHERE id=:certi_id";
    $args = [
      ":uid" => $user->uid,
      ":name_title" => trim($v['name_title']),
      ":name" => trim($v['name']),
      ":email_id" => trim($v['email_id']),
      ":institute_name" => trim($v['institute_name']),
      ":institute_address" => trim($v['institute_address']),
      ":lab_name" => trim($v['lab_name']),
      ":department" => trim($v['department']),
      ":semester_details" => trim($v['semester_details']),
      ":proposal_id" => trim($v['proposal_id']),
      ":type" => "Participant",
      ":creation_date" => time(),
      ":certi_id" => $v['certi_id'],
    ];
    $proposal_id = \Drupal::database()->query($result, $args);
    drupal_goto('lab-migration/certificate');
  }

}
?>
