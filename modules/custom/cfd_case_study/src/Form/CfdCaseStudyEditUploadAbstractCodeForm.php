<?php

/**
 * @file
 * Contains \Drupal\cfd_case_study\Form\CfdCaseStudyEditUploadAbstractCodeForm.
 */

namespace Drupal\cfd_case_study\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

class CfdCaseStudyEditUploadAbstractCodeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cfd_case_study_edit_upload_abstract_code_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $form['#attributes'] = ['enctype' => "multipart/form-data"];
    /* get current proposal */
    $route_match = \Drupal::routeMatch();
    $proposal_id = $route_match->getParameter('id');
    if (!$proposal_id) {
      $proposal_id = \Drupal::request()->query->get('id');
    }
    $proposal_id = $proposal_id !== NULL ? (int) $proposal_id : 0;
    $uid = $user->id();
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      } //$proposal_data = $proposal_q->fetchObject()
      else {
        \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
        $form_state->setRedirect('cfd_case_study.proposal_edit_file_all');
        return;
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('cfd_case_study.proposal_edit_file_all');
      return;
    }
    $query = \Drupal::database()->select('case_study_submitted_abstracts');
    $query->fields('case_study_submitted_abstracts');
    $query->condition('proposal_id', $proposal_data->id);
    $abstracts_q = $query->execute()->fetchObject();
    $form['project_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->project_title,
      '#title' => t('Title of the Case Study Project'),
    ];
    $form['contributor_name'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->contributor_name,
      '#title' => t('Contributor Name'),
    ];
    $existing_uploaded_A_file = $this->default_value_for_uploaded_files("A", $proposal_data->id);
    if (!$existing_uploaded_A_file) {
      $existing_uploaded_A_file = new stdClass();
      $existing_uploaded_A_file->filename = "No file uploaded";
    } //!$existing_uploaded_A_file
    // @FIXME
    // // @FIXME
    // // This looks like another module's variable. You'll need to rewrite this call
    // // to ensure that it uses the correct configuration object.
    // $form['upload_case_study_abstract'] = array(
    //         '#type' => 'file',
    //         '#title' => t('Upload the Case study abstract'),
    //         //'#required' => TRUE,
    //         '#description' => t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_A_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . variable_get('resource_upload_extensions', '') . '</span>',
    //     );

    $existing_uploaded_S_file = $this->default_value_for_uploaded_files("S", $proposal_data->id);
    if (!$existing_uploaded_S_file) {
      $existing_uploaded_S_file = new stdClass();
      $existing_uploaded_S_file->filename = "No file uploaded";
    } //!$existing_uploaded_S_file
    // @FIXME
    // // @FIXME
    // // This looks like another module's variable. You'll need to rewrite this call
    // // to ensure that it uses the correct configuration object.
    // $form['upload_case_study_developed_process'] = array(
    //         '#type' => 'file',
    //         '#title' => t('Upload the Case Directory'),
    //         //'#required' => TRUE,
    //         '#description' => t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_S_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . variable_get('case_study_project_files_extensions', '') . '</span>',
    //     );

    $form['prop_id'] = [
      '#type' => 'hidden',
      '#value' => $proposal_data->id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // $form['cancel'] = array(
    //         '#type' => 'item',
    //         '#markup' => l(t('Cancel'), 'case-study-project/manage-proposal/edit-upload-file'),
    //     );

    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $submitted_abstract_id = \Drupal::database()->select('case_study_submitted_abstracts')
      ->fields('case_study_submitted_abstracts', ['id'])
      ->condition('proposal_id', $proposal_id)
      ->execute()
      ->fetchField() ?: 0;

    $files = \Drupal::request()->files->get('files') ?? [];
    $abstract = $files['upload_case_study_abstract'] ?? NULL;
    $process = $files['upload_case_study_developed_process'] ?? NULL;
    if ((!$abstract || !$abstract->isValid()) && (!$process || !$process->isValid())) {
      $this->messenger()->addError($this->t('No files uploaded'));
      return;
    }
    foreach (['upload_case_study_abstract' => 'A', 'upload_case_study_developed_process' => 'S'] as $name => $file_type) {
      $upload = $files[$name] ?? NULL;
      if (!$upload || !$upload->isValid()) {
        continue;
      }
      if ($file_type === 'A') {
        $allowed_extensions_str = \Drupal::config('cfd_case_study.settings')->get('resource_upload_extensions');
      }
      else {
        $allowed_extensions_str = \Drupal::config('cfd_case_study.settings')->get('case_study_project_files_extensions');
      }
      $allowed_extensions = array_filter(array_map('trim', explode(',', (string) $allowed_extensions_str)));
      $original_name = (string) $upload->getClientOriginalName();
      $temp_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
      if (!empty($allowed_extensions) && !in_array($temp_extension, $allowed_extensions, TRUE)) {
        $form_state->setErrorByName($name, $this->t('Only file with @ext extensions can be uploaded.', ['@ext' => $allowed_extensions_str]));
      }
      if ($upload->getSize() <= 0) {
        $form_state->setErrorByName($name, $this->t('File size cannot be zero.'));
      }
      if (!cfd_case_study_check_valid_filename($original_name)) {
        $form_state->setErrorByName($name, $this->t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
      }
    }

  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $v = $form_state->getValues();
    $root_path = cfd_case_study_path();
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('id', $v['prop_id']);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    $proposal_id = $proposal_data->id;
    if (!$proposal_data) {
      $form_state->setRedirectUrl(Url::fromRoute('<front>'));
      return;
    } //!$proposal_data
    $proposal_id = $proposal_data->id;
    $proposal_directory = $proposal_data->directory_name;
    /* create proposal folder if not present */
    //$dest_path = $proposal_directory . '/';
    $dest_path_project_files = $proposal_directory . '/';
    $proposal_id = $proposal_data->id;
    $files = \Drupal::request()->files->get('files') ?? [];
    $file_system = \Drupal::service('file_system');
    $target_dir = $root_path . $dest_path_project_files;
    $file_system->prepareDirectory($target_dir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);
    foreach ($files as $file_form_name => $upload) {
      if (!$upload || !$upload->isValid()) {
        continue;
      }
      $file_type = NULL;
      if (strstr($file_form_name, 'upload_case_study_abstract')) {
        $file_type = 'A';
        $abs_file_name = $upload->getClientOriginalName();
      }
      else {
        $abs_file_name = "Not updated";
      }
      if (strstr($file_form_name, 'upload_case_study_developed_process')) {
        $file_type = 'S';
        $proj_file_name = $upload->getClientOriginalName();
      }
      else {
        $proj_file_name = "Not updated";
      }
      if (empty($file_type)) {
        continue;
      }
      $original_name = $file_system->basename($upload->getClientOriginalName());
      $target_path = $target_dir . $original_name;
      if (!$upload->move($target_dir, $original_name)) {
        $this->messenger()->addError($this->t('@filename file not updated successfully.', ['@filename' => $original_name]));
        continue;
      }
      $filemime = \Drupal::service('file.mime_type.guesser')->guessMimeType($target_path) ?: $upload->getClientMimeType();
      $filesize = filesize($target_path);

      $query_ab_f = "SELECT * FROM case_study_submitted_abstracts_file WHERE proposal_id = :proposal_id AND filetype = :filetype";
      $args_ab_f = [
        ":proposal_id" => $proposal_id,
        ":filetype" => $file_type,
      ];
      $query_ab_f_result = \Drupal::database()->query($query_ab_f, $args_ab_f)->fetchObject();
      if ($query_ab_f_result && !empty($query_ab_f_result->filename)) {
        $old_path = $target_dir . $query_ab_f_result->filename;
        if (is_file($old_path)) {
          unlink($old_path);
        }
        \Drupal::database()->update('case_study_submitted_abstracts_file')
          ->fields([
            'filename' => $original_name,
            'filepath' => $original_name,
            'filemime' => $filemime,
            'filesize' => $filesize,
            'timestamp' => time(),
          ])
          ->condition('proposal_id', $proposal_id)
          ->condition('filetype', $file_type)
          ->execute();
      }
      else {
        \Drupal::database()->insert('case_study_submitted_abstracts_file')
          ->fields([
            'submitted_abstract_id' => $submitted_abstract_id,
            'proposal_id' => $proposal_id,
            'uid' => $user->id(),
            'approvar_uid' => 0,
            'filename' => $original_name,
            'filepath' => $original_name,
            'filemime' => $filemime,
            'filesize' => $filesize,
            'filetype' => $file_type,
            'timestamp' => time(),
          ])
          ->execute();
      }
      $this->messenger()->addStatus($this->t('@filename file updated successfully.', ['@filename' => $original_name]));
    }
    /* sending email */
    $email_to = $user->getEmail();
    // @FIXME
    // // @FIXME
    // // This looks like another module's variable. You'll need to rewrite this call
    // // to ensure that it uses the correct configuration object.
    // $from = variable_get('case_study_from_email', '');

    // @FIXME
    // // @FIXME
    // // This looks like another module's variable. You'll need to rewrite this call
    // // to ensure that it uses the correct configuration object.
    // $bcc = variable_get('case_study_emails', '');

    // @FIXME
    // // @FIXME
    // // This looks like another module's variable. You'll need to rewrite this call
    // // to ensure that it uses the correct configuration object.
    // $cc = variable_get('case_study_cc_emails', '');

    $config = \Drupal::config('cfd_case_study.settings');
    $from = $config->get('case_study_from_email') ?: \Drupal::config('system.site')->get('mail');
    if (empty($from)) {
      $from = 'no-reply@localhost';
    }
    $bcc = $config->get('case_study_emails');
    $cc = $config->get('case_study_cc_emails');

    $params['abstract_edit_file_uploaded']['proposal_id'] = $proposal_id;
    $params['abstract_edit_file_uploaded']['user_id'] = $user->id();
    $params['abstract_edit_file_uploaded']['abs_file'] = $abs_file_name;
    $params['abstract_edit_file_uploaded']['proj_file'] = $proj_file_name;
    $headers = [
      'From' => $from,
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal',
    ];
    if (!empty($cc)) {
      $headers['Cc'] = $cc;
    }
    if (!empty($bcc)) {
      $headers['Bcc'] = $bcc;
    }
    $params['abstract_edit_file_uploaded']['headers'] = $headers;
    $langcode = $user->getPreferredLangcode() ?: \Drupal::languageManager()->getDefaultLanguage()->getId();
    if ($email_to) {
      $result = \Drupal::service('plugin.manager.mail')->mail('cfd_case_study', 'abstract_edit_file_uploaded', $email_to, $langcode, $params, $from, TRUE);
      if (empty($result['result'])) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
    }
    \Drupal\Core\Cache\Cache::invalidateTags([
      'case_study_proposal_list',
      "case_study_proposal:$proposal_id",
    ]);
    $form_state->setRedirect('cfd_case_study.edit_upload_abstract_code_form');
  }

  protected function default_value_for_uploaded_files($filetype, $proposal_id) {
    $query = \Drupal::database()->select('case_study_submitted_abstracts_file');
    $query->fields('case_study_submitted_abstracts_file');
    $query->condition('proposal_id', $proposal_id);
    if ($filetype === 'S' || $filetype === 'A') {
      $query->condition('filetype', $filetype);
      return $query->execute()->fetchObject();
    }
    return NULL;
  }

}
?>
