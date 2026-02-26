<?php

/**
 * @file
 * Contains \Drupal\cfd_research_migration\Form\CfdResearchMigrationUploadAbstractCodeForm.
 */

namespace Drupal\cfd_research_migration\Form;

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
use Drupal\Core\StringTranslation\TranslatableMarkup;
// use Drupal\Component\Render\Markup;
use Drupal\Core\Render\Markup; 

class CfdResearchMigrationUploadAbstractCodeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cfd_research_migration_upload_abstract_code_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $form['#attributes'] = ['enctype' => "multipart/form-data"];
    /* get current proposal */
    //$proposal_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();

    $proposal_id = (int) $route_match->getParameter('id');
    
    $uid = $user->uid;
//     $query = \Drupal::database()->select('research_migration_proposal');
//     $query->fields('research_migration_proposal');
//     $query->condition('uid', $uid);
//     $query->condition('approval_status', '1');
//     $proposal_q = $query->execute();

$uid = $user->id();
$query = \Drupal::database()->select('research_migration_proposal', 'rmp')
    ->fields('rmp', ['id', 'project_title', 'contributor_name'])
    ->condition('uid', $uid)
    ->condition('approval_status', 1)
    ->execute()
    ->fetchObject();

if (!$query) {
    \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
    return new RedirectResponse(Url::fromUri('internal:/research-migration-project/abstract-code')->toString());
}

// Store in $proposal_data
$proposal_data = $query;

// //     if ($proposal_q) {
//       if ($proposal_data = $proposal_q->fetchObject()) {
//         /* everything ok */
//       } //$proposal_data = $proposal_q->fetchObject()
//       else {
//         \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
//         // drupal_goto('research-migration-project/abstract-code');
//         $response = new RedirectResponse(Url::fromUri('internal:/research-migration-project/abstract-code')->toString());
// $response->send();
//         return;
//       }
//     } //$proposal_q
//     else {
//       \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
//       // drupal_goto('research-migration-project/abstract-code');
//       $response = new RedirectResponse(Url::fromUri('internal:/research-migration-project/abstract-code')->toString());
// $response->send();
//       return;
//     }
    $query = \Drupal::database()->select('research_migration_submitted_abstracts');
    $query->fields('research_migration_submitted_abstracts');
    $query->condition('proposal_id', $proposal_data->id);
    $abstracts_q = $query->execute()->fetchObject();
    if ($abstracts_q) {
      if ($abstracts_q->is_submitted == 1) {
        \Drupal::messenger()->addMessage(t('You have already submited your Case Directory, hence you can not upload any more, for any query please write to us.'), 'error', $repeat = FALSE);
        // drupal_goto('research-migration-project/abstract-code');
        // $response = new RedirectResponse(Url::fromUri('internal:/research-migration-project/abstract-code')->toString());
// $response->send();
        //return;
      } //$abstracts_q->is_submitted == 1
    } //$abstracts_q->is_submitted == 1
    // $form['project_title'] = [
    //   '#type' => 'item',
    //   '#markup' => $proposal_data->project_title,
    //   '#title' => t('Title of the Research Migration Project'),
    // ];
    // $form['contributor_name'] = [
    //   '#type' => 'item',
    //   '#markup' => $proposal_data->contributor_name,
    //   '#title' => t('Contributor Name'),
    // ];

    $form['project_title'] = [
      '#type' => 'item',
      '#markup' => Markup::create($proposal_data->project_title),
      '#title' => $this->t('Title of the Research Migration Project'),
  ];
  
  $form['contributor_name'] = [
      '#type' => 'item',
      '#markup' => Markup::create($proposal_data->contributor_name),
      '#title' => $this->t('Contributor Name'),
  ];
  
    $existing_uploaded_S_file =  \Drupal::service("cfd_research_migration_global")->default_value_for_uploaded_files("S", $proposal_data->id);
    if (!$existing_uploaded_S_file) {
      $existing_uploaded_S_file = new \stdClass();
      $existing_uploaded_S_file->filename = "No file uploaded";
    } //!$existing_uploaded_S_file
    $form['upload_research_migration_developed_process'] = [
      '#type' => 'file',
      '#title' => t('Upload the Case Directory'),
      //'#required' => TRUE,
        '#description' => t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_S_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . \Drupal::config('cfd_research_migration.settings')->get('research_migration_project_files_extensions', '') . '</span>',
    ];
    $form['prop_id'] = [
      '#type' => 'hidden',
      '#value' => $proposal_data->id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#submit' => [
        '::submitForm'
        ],
    ];
    $form['cancel'] = [
      '#type' => 'item',
      // '#markup' => l(t('Cancel'), 'research-migration-project/abstract-code'),
'#markup' => Link::fromTextAndUrl(
    t('Cancel'), 
    Url::fromUserInput('/research-migration-project/abstract-code')
)->toString(),

    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    //var_dump($form);die;
    if (isset($_FILES['files'])) {
      /* check if atleast one source or result file is uploaded */
      if (!($_FILES['files']['name']['upload_research_migration_developed_process'])) {
        $form_state->setErrorByName('upload_research_migration_developed_process', t('Please upload the abstract file'));
      }

      /* check for valid filename extensions */
      foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
        if ($file_name) {
          /* checking file type */
          $allowed_extensions_str = \Drupal::config('cfd_research_migration.settings')->get('research_migration_project_files_extensions', '');
          $allowed_extensions = explode(',', $allowed_extensions_str);
          $fnames = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
          $temp_extension = end($fnames);
          if (!in_array($temp_extension, $allowed_extensions)) {
            $form_state->setErrorByName($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
          }

          if ($_FILES['files']['size'][$file_form_name] <= 0) {
            $form_state->setErrorByName($file_form_name, t('File size cannot be zero.'));
          }

          /* check if valid file name */
          if (!\Drupal::service("cfd_research_migration_global")->cfd_research_migration_check_valid_filename($_FILES['files']['name'][$file_form_name])) {
            $form_state->setErrorByName($file_form_name, t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
          }

        } //$file_name
      } //$_FILES['files']['name'] as $file_form_name => $file_name
    }
    return $form_state;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $v = $form_state->getValues();
    $root_path = \Drupal::service("cfd_research_migration_global")->cfd_research_migration_path();
    $proposal_data = \Drupal::service("cfd_research_migration_global")->cfd_research_migration_get_proposal();
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
    $query_s = "SELECT * FROM {research_migration_submitted_abstracts} WHERE proposal_id = :proposal_id";
    $args_s = [":proposal_id" => $proposal_id];
    $query_s_result = \Drupal::database()->query($query_s, $args_s)->fetchObject();
    if (!$query_s_result) {
      /* creating solution database entry */
      $query = "INSERT INTO {research_migration_submitted_abstracts} (
	proposal_id,
	approver_uid,
	abstract_approval_status,
	abstract_upload_date,
	abstract_approval_date,
	is_submitted) VALUES (:proposal_id, :approver_uid, :abstract_approval_status,:abstract_upload_date, :abstract_approval_date, :is_submitted)";
      $args = [
        ":proposal_id" => $proposal_id,
        ":approver_uid" => 0,
        ":abstract_approval_status" => 0,
        ":abstract_upload_date" => time(),
        ":abstract_approval_date" => 0,
        ":is_submitted" => 1,
      ];
      $submitted_abstract_id = \Drupal::database()->query($query, $args, [
        'return' => Database::RETURN_INSERT_ID
        ]);
      $query1 = "UPDATE {research_migration_proposal} SET is_submitted = :is_submitted WHERE id = :id";
      $args1 = [
        ":is_submitted" => 1,
        ":id" => $proposal_id,
      ];
      \Drupal::database()->query($query1, $args1);
      \Drupal::messenger()->addMessage('Synopsis Submission uploaded successfully.', 'status');
    } //!$query_s_result
    else {
      $query = "UPDATE {research_migration_submitted_abstracts} SET


	abstract_upload_date =:abstract_upload_date,
	is_submitted= :is_submitted
	WHERE proposal_id = :proposal_id
	";
      $args = [
        ":abstract_upload_date" => time(),
        ":is_submitted" => 1,
        ":proposal_id" => $proposal_id,
      ];
      $submitted_abstract_id = \Drupal::database()->query($query, $args, [
        'return' => Database::RETURN_INSERT_ID
        ]);
      $query1 = "UPDATE {research_migration_proposal} SET is_submitted = :is_submitted WHERE id = :id";
      $args1 = [
        ":is_submitted" => 1,
        ":id" => $proposal_id,
      ];
      \Drupal::database()->query($query1, $args1);
      \Drupal::messenger()->addMessage('Synopsis Submission updated successfully.', 'status');
    }
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
      if ($file_name) {
        /* checking file type */
        if (strstr($file_form_name, 'upload_research_migration_developed_process')) {
          $file_type = 'S';
        } //strstr($file_form_name, 'upload_research_migration_developed_process')
        switch ($file_type) {
          case 'S':

            if (file_exists($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name])) {
              //unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
              \Drupal::messenger()->addMessage(t("File !filename already exists hence overwirtten the exisitng file ", [
                '!filename' => $_FILES['files']['name'][$file_form_name]
                ]), 'error');
            } //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
                    /* uploading file */
            else {
              if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name])) {
                /* for uploaded files making an entry in the database */
                $query_abstracts = "SELECT * FROM research_migration_submitted_abstracts WHERE proposal_id = :proposal_id";
                $query_abstracts_args = [":proposal_id" => $proposal_id];
                $query_abstracts_result = \Drupal::database()->query($query_abstracts, $query_abstracts_args)->fetchObject();
                $submitted_abstract_id = $query_abstracts_result->id;
                $query_ab_f = "SELECT * FROM research_migration_submitted_abstracts_file WHERE proposal_id = :proposal_id AND filetype =
				:filetype";
                $args_ab_f = [
                  ":proposal_id" => $proposal_id,
                  ":filetype" => $file_type,
                ];
                $query_ab_f_result = \Drupal::database()->query($query_ab_f, $args_ab_f)->fetchObject();
                if (!$query_ab_f_result) {
                  $query = "INSERT INTO {research_migration_submitted_abstracts_file} (submitted_abstract_id, proposal_id, uid, approvar_uid, filename, filepath, filemime, filesize, filetype, timestamp)
          VALUES (:submitted_abstract_id, :proposal_id, :uid, :approvar_uid, :filename, :filepath, :filemime, :filesize, :filetype, :timestamp)";
                  $args = [
                    ":submitted_abstract_id" => $submitted_abstract_id,
                    ":proposal_id" => $proposal_id,
                    ":uid" => $user->uid,
                    ":approvar_uid" => 0,
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => $_FILES['files']['size'][$file_form_name],
                    ":filetype" => $file_type,
                    ":timestamp" => time(),
                  ];
                  \Drupal::database()->query($query, $args, [
                    'return' => Database::RETURN_INSERT_ID
                    ]);
                  \Drupal::messenger()->addMessage($file_name . ' uploaded successfully.', 'status');
                } //!$query_ab_f_result
                else {
                  unlink($root_path . $dest_path_project_files . $query_ab_f_result->filename);
                  $query = "UPDATE {research_migration_submitted_abstracts_file} SET filename = :filename, filepath=:filepath, filemime=:filemime, filesize=:filesize, timestamp=:timestamp WHERE proposal_id = :proposal_id AND filetype = :filetype";
                  $args = [
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $file_path . $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => $_FILES['files']['size'][$file_form_name],
                    ":timestamp" => time(),
                    ":proposal_id" => $proposal_id,
                    ":filetype" => $file_type,
                  ];
                  \Drupal::database()->query($query, $args, [
                    'return' => Database::RETURN_INSERT_ID
                    ]);

                  \Drupal::messenger()->addMessage($file_name . ' file updated successfully.', 'status');
                }
              } //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
              else {
                \Drupal::messenger()->addMessage('Error uploading file : ' . $dest_path_project_files . $file_name, 'error');
              }
            }
            break;
        } //$file_type
      } //$file_name
    } //$_FILES['files']['name'] as $file_form_name => $file_name
    /* sending email */

// Email to user
$email_to = $user->getEmail();

// Load config
$config = \Drupal::config('research_migration.settings');

$from_email = $config->get('research_migration_from_email');
$bcc        = $config->get('research_migration_emails');
$cc         = $config->get('research_migration_cc_emails');

// Fallback safety (prevents Symfony null error)
$site_mail  = \Drupal::config('system.site')->get('mail');

$from_email = !empty($from_email) ? $from_email : $site_mail;
$cc         = !empty($cc) ? $cc : '';
$bcc        = !empty($bcc) ? $bcc : '';

// Params
$params['abstract_uploaded']['proposal_id'] = $proposal_id;
$params['abstract_uploaded']['submitted_abstract_id'] = $submitted_abstract_id;
$params['abstract_uploaded']['user_id'] = $user->id();

// Build headers safely
$headers = [
  'From' => $from_email,
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

// Send mail
$mail_manager = \Drupal::service('plugin.manager.mail');

$result = $mail_manager->mail(
  'research_migration',
  'abstract_uploaded',
  $email_to,
  \Drupal::languageManager()->getDefaultLanguage()->getId(),
  $params,
  $from_email,
  TRUE
);

if (!$result['result']) {
  \Drupal::messenger()->addError(t('Error sending email message.'));
}
    $response = new RedirectResponse(Url::fromUri('internal:/research-migration-project/abstract-code')->toString());
$response->send();

  }

}
?>
