<?php

/**
 * @file
 * Contains \Drupal\cfd_case_study\Form\CfdCaseStudyUploadAbstractCodeForm.
 */

namespace Drupal\cfd_case_study\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Database\Database;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Mail\MailManager;

class CfdCaseStudyUploadAbstractCodeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cfd_case_study_upload_abstract_code_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $form['#attributes'] = ['enctype' => "multipart/form-data"];
    /* get current proposal */
    //$proposal_id = (int) arg(3);
    $uid = $user->id();
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('uid', $uid);
    $query->condition('approval_status', '1');
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      } //$proposal_data = $proposal_q->fetchObject()
      else {
        \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
        $form_state->setRedirect('cfd_case_study.upload_abstract_code_form');
        return [];
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('cfd_case_study.abstract');
      return [];
    }
    $query = \Drupal::database()->select('case_study_submitted_abstracts');
    $query->fields('case_study_submitted_abstracts');
    $query->condition('proposal_id', $proposal_data->id);
    $abstracts_q = $query->execute()->fetchObject();
    if ($abstracts_q) {
      if ($abstracts_q->is_submitted == 1) {
        \Drupal::messenger()->addError(t('You have already submited your Case Directory, hence you can not upload any more, for any query please write to us.'));
        $form_state->setRedirect('cfd_case_study.abstract');
        return [];
      } //$abstracts_q->is_submitted == 1
    } //$abstracts_q->is_submitted == 1
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
    $existing_uploaded_S_file = $this->default_value_for_uploaded_files("S", $proposal_data->id);
    if (!$existing_uploaded_S_file) {
      $existing_uploaded_S_file = new \stdClass();
      $existing_uploaded_S_file->filename = "No file uploaded";
    } //!$existing_uploaded_S_file
    // @FIXME
    // // @FIXME
    // // This looks like another module's variable. You'll need to rewrite this call
    // // to ensure that it uses the correct configuration object.
    $form['upload_case_study_developed_process'] = array(
            '#type' => 'file',
            '#title' => t('Upload the Case Directory'),
            //'#required' => TRUE,
            '#description' => t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_S_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ')
             . \Drupal::config('cfd_case_study.settings')->get('case_study_upload_extensions') 
            . '</span>',
        );

    $form['prop_id'] = [
      '#type' => 'hidden',
      '#value' => $proposal_data->id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      // '#submit' => [
      //   'submitForm'
      //   ],
    ];
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // $form['cancel'] = array(
    //         '#type' => 'item',
    //         '#markup' => l(t('Cancel'), 'case-study-project/abstract-code'),
    //     );
$form['cancel'] = [
  '#type' => 'item',
  '#markup' => Link::fromTextAndUrl(
    t('Cancel'),
    Url::fromUserInput('/case-study-project/abstract-code')
  )->toString(),
];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $files = \Drupal::request()->files->get('files') ?? [];
    $upload = $files['upload_case_study_developed_process'] ?? NULL;
    if (!$upload || !$upload->isValid()) {
      $form_state->setErrorByName('upload_case_study_developed_process', $this->t('Please upload the abstract file'));
      return;
    }

    $allowed_extensions_str = \Drupal::config('cfd_case_study.settings')->get('case_study_upload_extensions');
    $allowed_extensions = array_filter(array_map('trim', explode(',', (string) $allowed_extensions_str)));
    $original_name = (string) $upload->getClientOriginalName();
    $temp_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    if (!empty($allowed_extensions) && !in_array($temp_extension, $allowed_extensions, TRUE)) {
      $form_state->setErrorByName('upload_case_study_developed_process', $this->t('Only file with @ext extensions can be uploaded.', ['@ext' => $allowed_extensions_str]));
    }
    if ($upload->getSize() <= 0) {
      $form_state->setErrorByName('upload_case_study_developed_process', $this->t('File size cannot be zero.'));
    }
    if (!cfd_case_study_check_valid_filename($original_name)) {
      $form_state->setErrorByName('upload_case_study_developed_process', $this->t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
    }
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $v = $form_state->getValues();
    $root_path = cfd_case_study_path();
    $proposal_data = cfd_case_study_get_proposal();
    $proposal_id = $proposal_data->id;
    if (!$proposal_data) {
      // drupal_goto('');
      return;
    } //!$proposal_data
    $proposal_id = $proposal_data->id;
    $proposal_directory = $proposal_data->directory_name;
    /* create proposal folder if not present */
    //$dest_path = $proposal_directory . '/';
    $dest_path_project_files = $proposal_directory . '/';
    $proposal_id = $proposal_data->id;
    $query_s = "SELECT * FROM {case_study_submitted_abstracts} WHERE proposal_id = :proposal_id";
    $args_s = [":proposal_id" => $proposal_id];
    $query_s_result = \Drupal::database()->query($query_s, $args_s)->fetchObject();
    if (!$query_s_result) {
      $submitted_abstract_id = \Drupal::database()->insert('case_study_submitted_abstracts')
        ->fields([
          'proposal_id' => $proposal_id,
          'approver_uid' => 0,
          'abstract_approval_status' => 0,
          'abstract_upload_date' => time(),
          'abstract_approval_date' => 0,
          'is_submitted' => 1,
        ])
        ->execute();
      \Drupal::database()->update('case_study_proposal')
        ->fields(['is_submitted' => 1])
        ->condition('id', $proposal_id)
        ->execute();
      $this->messenger()->addStatus($this->t('Abstract uploaded successfully.'));
    }
    else {
      \Drupal::database()->update('case_study_submitted_abstracts')
        ->fields([
          'abstract_upload_date' => time(),
          'is_submitted' => 1,
        ])
        ->condition('proposal_id', $proposal_id)
        ->execute();
      $submitted_abstract_id = $query_s_result->id;
      \Drupal::database()->update('case_study_proposal')
        ->fields(['is_submitted' => 1])
        ->condition('id', $proposal_id)
        ->execute();
      $this->messenger()->addStatus($this->t('Abstract updated successfully.'));
    }

    $files = \Drupal::request()->files->get('files') ?? [];
    $file_system = \Drupal::service('file_system');
    $target_dir = $root_path . $dest_path_project_files;
    $file_system->prepareDirectory($target_dir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);
    foreach ($files as $file_form_name => $upload) {
      if (!$upload || !$upload->isValid()) {
        continue;
      }
      if (!strstr($file_form_name, 'upload_case_study_developed_process')) {
        continue;
      }
      $file_type = 'S';

      $original_name = $file_system->basename($upload->getClientOriginalName());
      $target_path = $target_dir . $original_name;
      if (file_exists($target_path)) {
        $this->messenger()->addError($this->t('File @filename already exists.', ['@filename' => $original_name]));
        continue;
      }

      try {
        $upload->move($target_dir, $original_name);
      }
      catch (\Exception $e) {
        $this->messenger()->addError($this->t('Error uploading file : @filename', ['@filename' => $original_name]));
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
      if (!$query_ab_f_result) {
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
        $this->messenger()->addStatus($this->t('@filename uploaded successfully.', ['@filename' => $original_name]));
      }
      else {
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

        $this->messenger()->addStatus($this->t('@filename file updated successfully.', ['@filename' => $original_name]));
      }
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
    $langcode = $user->getPreferredLangcode() ?: \Drupal::languageManager()->getDefaultLanguage()->getId();

    $params['abstract_uploaded']['proposal_id'] = $proposal_id;
    $params['abstract_uploaded']['submitted_abstract_id'] = $submitted_abstract_id;
    $params['abstract_uploaded']['user_id'] = $user->id();
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
    $params['abstract_uploaded']['headers'] = $headers;
    if ($email_to) {
      $result = \Drupal::service('plugin.manager.mail')->mail('cfd_case_study', 'abstract_uploaded', $email_to, $langcode, $params, $from, TRUE);
      if (empty($result['result'])) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
    }

    \Drupal\Core\Cache\Cache::invalidateTags([
      'case_study_proposal_list',
      "case_study_proposal:$proposal_id",
    ]);
    $form_state->setRedirect('cfd_case_study.abstract');
  }
  function default_value_for_uploaded_files($filetype, $proposal_id)
  {
         $query = Database::getConnection()->select('case_study_submitted_abstracts_file');
      $query->fields('case_study_submitted_abstracts_file');
      $query->condition('proposal_id', $proposal_id);
      $selected_files_array = "";
      if ($filetype == "S") {
          $query->condition('filetype', $filetype);
          $filetype_q = $query->execute()->fetchObject();
          return $filetype_q;
      } elseif ($filetype == "A") {
          $query->condition('filetype', $filetype);
          $filetype_q = $query->execute()->fetchObject();
          return $filetype_q;
      }
      return;
  }
}
?>
