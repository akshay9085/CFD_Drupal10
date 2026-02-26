<?php

/**
 * @file
 * Contains \Drupal\cfd_research_migration\Form\CfdResearchMigrationAbstractBulkApprovalForm.
 */

namespace Drupal\cfd_research_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Database\Database;
use Drupal\Component\Render\Markup;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Render\Renderer;

class CfdResearchMigrationAbstractBulkApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cfd_research_migration_abstract_bulk_approval_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $options_first = $this->_bulk_list_of_research_migration_project();
    $selected = !$form_state->getValue(['research_migration_project']) ? $form_state->getValue([
      'research_migration_project'
      ]) : key($options_first);
    $form = [];
    $form['research_migration_project'] = [
      '#type' => 'select',
      '#title' => t('Title of the Research Migration project'),
      '#options' => $this->_bulk_list_of_research_migration_project(),
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::ajax_bulk_research_migration_abstract_details_callback'
        ],
      '#suffix' => '<div id="ajax_selected_research_migration"></div><div id="ajax_selected_research_migration_pdf"></div>',
    ];
    $form['research_migration_actions'] = [
      '#type' => 'select',
      '#title' => t('Please select action for Research Migration project'),
      '#options' => $this->_bulk_list_research_migration_actions(),
      '#default_value' => 0,
      '#prefix' => '<div id="ajax_selected_research_migration_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="research_migration_project"]' => [
            'value' => 0
            ]
          ]
        ],
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => t('If Dis-Approved please specify reason for Dis-Approval'),
      '#prefix' => '<div id= "message_submit">',
      '#states' => [
        'visible' => [
          [
            ':input[name="research_migration_actions"]' => [
              'value' => 3
              ]
            ],
          'or',
          [
            ':input[name="research_migration_actions"]' => [
              'value' => 4
              ]
            ],
        ]
        ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      
    ];
    return $form;
  }


/**
 * AJAX callback for fetching research migration abstract details.
 */
function ajax_bulk_research_migration_abstract_details_callback(array &$form, FormStateInterface $form_state) {
  $response = new AjaxResponse();

  $research_migration_project_default_value = $form_state->getValue('research_migration_project');

  if ($research_migration_project_default_value != 0) {
    // Update research migration details.
    $response->addCommand(new HtmlCommand('#ajax_selected_research_migration', $this->_research_migration_details($research_migration_project_default_value)));

    // Update actions dropdown options.
    $form['research_migration_actions']['#options'] = $this->_bulk_list_research_migration_actions();
    $renderer = \Drupal::service('renderer');
    $response->addCommand(new ReplaceCommand('#ajax_selected_research_migration_action', $renderer->render($form['research_migration_actions'])));
  } 
  else {
    // Clear research migration details and update form state.
    $response->addCommand(new HtmlCommand('#ajax_selected_research_migration', ''));
    $response->addCommand(new HtmlCommand('#ajax_selected_research_migration_action', ''));
  }

  return $response;
}

  function _bulk_list_of_research_migration_project() {
    $project_titles = [
      '0' => 'Please select...'
    ];
  
    // Use Drupal's Database API to query the research_migration_proposal table.
    $query = \Drupal::database()->select('research_migration_proposal', 'r');
    $query->fields('r', ['id', 'project_title', 'contributor_name']);
    $query->condition('is_submitted', 1);
    $query->condition('approval_status', 1);
    $query->orderBy('project_title', 'ASC');
  
    $project_titles_q = $query->execute();
    
    while ($project_titles_data = $project_titles_q->fetchObject()) {
      $project_titles[$project_titles_data->id] = $project_titles_data->project_title . 
        ' (Proposed by ' . $project_titles_data->contributor_name . ')';
    }
  
    return $project_titles;
  }
  function _bulk_list_research_migration_actions(): array {
    return [
      0 => 'Please select...',
      1 => 'Approve Entire Research Migration Project',
      2 => 'Resubmit Project files',
      3 => 'Dis-Approve Entire Research Migration Project (This will delete Research Migration Project)',
      // 4 => 'Delete Entire Research Migration Project Including Proposal',
    ];
  }
  
  

function _research_migration_details($research_migration_proposal_id) {
  $return_html = "";

  // Fetch research migration proposal details
  $query_pro = \Drupal::database()->select('research_migration_proposal', 'r');
  $query_pro->fields('r');
  $query_pro->condition('r.id', $research_migration_proposal_id);
  $abstracts_pro = $query_pro->execute()->fetchObject();

  // Fetch abstract file details
  $query_pdf = \Drupal::database()->select('research_migration_submitted_abstracts_file', 'f');
  $query_pdf->fields('f');
  $query_pdf->condition('f.proposal_id', $research_migration_proposal_id);
  $query_pdf->condition('f.filetype', 'A');
  $abstracts_pdf = $query_pdf->execute()->fetchObject();

  $abstract_filename = "File not uploaded";
  if ($abstracts_pdf && !empty($abstracts_pdf->filename) && $abstracts_pdf->filename !== "NULL") {
    $abstract_filename = $abstracts_pdf->filename;
  }

  // Fetch case directory folder details
  $query_process = \Drupal::database()->select('research_migration_submitted_abstracts_file', 'p');
  $query_process->fields('p');
  $query_process->condition('p.proposal_id', $research_migration_proposal_id);
  $query_process->condition('p.filetype', 'S');
  $abstracts_query_process = $query_process->execute()->fetchObject();

  $abstracts_query_process_filename = "File not uploaded";
  if ($abstracts_query_process && !empty($abstracts_query_process->filename) && $abstracts_query_process->filename !== "NULL") {
    $abstracts_query_process_filename = $abstracts_query_process->filename;
  } else {
    $url = Link::fromTextAndUrl(
      'Upload abstract',
      Url::fromUri('internal:/research-migration-project/abstract-code/upload')
    )->toString();
  }

  // Fetch research migration submitted abstracts
  $query = \Drupal::database()->select('research_migration_submitted_abstracts', 's');
  $query->fields('s');
  $query->condition('s.proposal_id', $research_migration_proposal_id);
  $abstracts_q = $query->execute()->fetchObject();

  if ($abstracts_q && $abstracts_q->is_submitted == 0) {
    // Abstract is not submitted yet.
  }

  // Create the download link
  $download_research_migration = Link::fromTextAndUrl(
    'Download Research Migration project',
    Url::fromUri("internal:/research-migration-project/full-download/project/$research_migration_proposal_id")
  )->toString();

  // Build the return HTML
  $return_html .= '<strong>Proposer Name:</strong><br />' . $abstracts_pro->name_title . ' ' . $abstracts_pro->contributor_name . '<br /><br />';
  $return_html .= '<strong>Title of the Research Migration Project:</strong><br />' . $abstracts_pro->project_title . '<br /><br />';
  $return_html .= '<strong>Uploaded an abstract (brief outline) of the project:</strong><br />' . $abstract_filename . '<br /><br />';
  $return_html .= '<strong>Uploaded Case Directory Folder:</strong><br />' . $abstracts_query_process_filename . '<br /><br />';
  $return_html .= $download_research_migration;

  return $return_html;
}

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $msg = '';
    $root_path = \Drupal::service("cfd_research_migration_global")->cfd_research_migration_path();
    //var_dump($root_path);die;
    if ($form_state->get(['clicked_button', '#value']) == 'Submit') {
      if ($form_state->getValue(['research_migration_project']))
        //var_dump($form_state['values']['research_migration_actions']);die;
        // research_migration_abstract_del_lab_pdf($form_state['values']['research_migration_project']);
 {
        if (user_access('Research Migration bulk manage abstract')) {
          $query = \Drupal::database()->select('research_migration_proposal');
          $query->fields('research_migration_proposal');
          $query->condition('id', $form_state->getValue(['research_migration_project']));
          $user_query = $query->execute();
          $user_info = $user_query->fetchObject();
          //var_dump($user_info);die;
          $user_data = user_load($user_info->uid);
          if ($form_state->getValue(['research_migration_actions']) == 1) {
            // approving entire project //
            $query = \Drupal::database()->select('research_migration_submitted_abstracts');
            $query->fields('research_migration_submitted_abstracts');
            $query->condition('proposal_id', $form_state->getValue(['research_migration_project']));
            $abstracts_q = $query->execute();
            //var_dump($abstracts_q);die;
            $experiment_list = '';
            while ($abstract_data = $abstracts_q->fetchObject()) {
              \Drupal::database()->query("UPDATE {research_migration_submitted_abstracts} SET abstract_approval_status = 1, is_submitted = 1, approver_uid = :approver_uid WHERE id = :id", [
                ':approver_uid' => $user->uid,
                ':id' => $abstract_data->id,
              ]);
              \Drupal::database()->query("UPDATE {research_migration_submitted_abstracts_file} SET file_approval_status = 1, approvar_uid = :approver_uid WHERE submitted_abstract_id = :submitted_abstract_id", [
                ':approver_uid' => $user->uid,
                ':submitted_abstract_id' => $abstract_data->id,
              ]);
            } //$abstract_data = $abstracts_q->fetchObject()
            \Drupal::messenger()->addMessage($this->t('Approved Research Migration project.'), 'status');
            // email 

/** Prepare subject */
$email_subject = t('[@site][Research Migration Project] Your uploaded Research Migration project has been approved', [
  '@site' => \Drupal::config('system.site')->get('name'),
]);

/** Prepare body */
$email_body = t('
Dear @user_name,

Your uploaded project files for the Research Migration project have been approved.

Title of Research Migration project : @title

Best Wishes,

@site Team,
FOSSEE, IIT Bombay
', [
  '@site' => \Drupal::config('system.site')->get('name'),
  '@user_name' => $user_data->getDisplayName(),
  '@title' => $user_info->project_title,
]);

/** Mail parameters */
$params = [];
$params['subject'] = $email_subject;
$params['body'] = $email_body;

/** Recipients */
$email_to = $user_data->getEmail();
$from = \Drupal::config('research_migration.settings')->get('research_migration_from_email');
$cc = \Drupal::config('research_migration.settings')->get('research_migration_cc_emails');
$bcc = \Drupal::config('research_migration.settings')->get('research_migration_emails');

$params['headers'] = [
  'From' => $from,
  'Cc' => $cc,
  'Bcc' => $bcc,
  'MIME-Version' => '1.0',
  'Content-Type' => 'text/plain; charset=UTF-8',
  'Content-Transfer-Encoding' => '8Bit',
  'X-Mailer' => 'Drupal',
];

/** Send mail */
$mail_manager = \Drupal::service('plugin.manager.mail');
$langcode = \Drupal::currentUser()->getPreferredLangcode();

$result = $mail_manager->mail(
  'research_migration',
  'standard',
  $email_to,
  $langcode,
  $params,
  $from,
  TRUE
);

if (!$result['result']) {
  \Drupal::messenger()->addError(t('Error sending email message.'));
}
else {
  \Drupal::messenger()->addStatus(t('Approval email sent successfully.'));
}          } //$form_state['values']['research_migration_actions'] == 1
          elseif ($form_state->getValue(['research_migration_actions']) == 2) {
            //pending review entire project 
            $query = \Drupal::database()->select('research_migration_submitted_abstracts');
            $query->fields('research_migration_submitted_abstracts');
            $query->condition('proposal_id', $form_state->getValue(['research_migration_project']));
            $abstracts_q = $query->execute();
            $experiment_list = '';
            while ($abstract_data = $abstracts_q->fetchObject()) {
              \Drupal::database()->query("UPDATE {research_migration_submitted_abstracts} SET abstract_approval_status = 0, is_submitted = 0, approver_uid = :approver_uid WHERE id = :id", [
                ':approver_uid' => $user->uid,
                ':id' => $abstract_data->id,
              ]);
              \Drupal::database()->query("UPDATE {research_migration_proposal} SET is_submitted = 0, approver_uid = :approver_uid WHERE id = :id", [
                ':approver_uid' => $user->uid,
                ':id' => $abstract_data->proposal_id,
              ]);
              \Drupal::database()->query("UPDATE {research_migration_submitted_abstracts_file} SET file_approval_status = 0, approvar_uid = :approver_uid WHERE submitted_abstract_id = :submitted_abstract_id", [
                ':approver_uid' => $user->uid,
                ':submitted_abstract_id' => $abstract_data->id,
              ]);
            } //$abstract_data = $abstracts_q->fetchObject()
            \Drupal::messenger()->addMessage(t('Resubmit the project files'), 'status');
            // email 

/** Prepare subject */
$email_subject = t('[@site][Research Migration Project] Your uploaded Research Migration project has been marked as pending', [
  '@site' => \Drupal::config('system.site')->get('name'),
]);

/** Prepare body */
$email_body = t('
Dear @user_name,

Kindly resubmit the project files for the project : @title.

Best Wishes,

@site_name Team,
FOSSEE, IIT Bombay
', [
  '@site_name' => \Drupal::config('system.site')->get('name'),
  '@user_name' => $user_data->getDisplayName(),
  '@title' => $user_info->project_title,
]);

/** Mail params */
$params = [];
$params['subject'] = $email_subject;
$params['body'] = $email_body;

/** Recipients */
$email_to = $user_data->getEmail();
$from = \Drupal::config('research_migration.settings')->get('research_migration_from_email');
$cc   = \Drupal::config('research_migration.settings')->get('research_migration_cc_emails');
$bcc  = \Drupal::config('research_migration.settings')->get('research_migration_emails');

$params['headers'] = [
  'From' => $from,
  'Cc' => $cc,
  'Bcc' => $bcc,
  'MIME-Version' => '1.0',
  'Content-Type' => 'text/plain; charset=UTF-8',
  'Content-Transfer-Encoding' => '8Bit',
  'X-Mailer' => 'Drupal',
];

/** Send mail */
$mail_manager = \Drupal::service('plugin.manager.mail');
$langcode = \Drupal::currentUser()->getPreferredLangcode();

$result = $mail_manager->mail(
  'research_migration',
  'standard',
  $email_to,
  $langcode,
  $params,
  $from,
  TRUE
);

if (!$result['result']) {
  \Drupal::messenger()->addError(t('Error sending email message.'));
}
else {
  \Drupal::messenger()->addStatus(t('Pending status email sent successfully.'));
} //!drupal_mail('research_migration', 'standard', $email_to, language_default(), $params, $from, TRUE)
          } //$form_state['values']['research_migration_actions'] == 2
          
          
          elseif ($form_state->getValue(['research_migration_actions']) == 3) //disapprove and delete entire Research Migration project
 {
            if (strlen(trim($form_state->getValue(['message']))) <= 30) {
              $form_state->setErrorByName('message', t(''));
              $msg = \Drupal::messenger()->addMessage("Please mention the reason for disapproval. Minimum 30 character required", 'error');
              return $msg;
            } //strlen(trim($form_state['values']['message'])) <= 30
            if (!user_access('Research Migration bulk delete abstract')) {
              $msg = \Drupal::messenger()->addMessage(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Lab.'), 'error');
              return $msg;
            } //!user_access('research_migration bulk delete code')
            if (research_migration_abstract_delete_project($form_state->getValue(['research_migration_project']))) //////
 {
              \Drupal::messenger()->addMessage(t('Dis-Approved and Deleted Entire Research Migration project.'), 'status');

/** Prepare subject */
$email_subject = t('[!site_name][Research Migration Project] Your uploaded Research Migration project has been marked as dis-approved', [
  '!site_name' => \Drupal::config('system.site')->get('name'),
]);

/** Prepare body */
$email_body = t('
Dear @user_name,

Your uploaded Research Migration project files for the Research Migration project
Title : @title have been marked as dis-approved.

Reason for dis-approval: @reason

Best Wishes,

@site_name Team,
FOSSEE, IIT Bombay
', [
  '@site_name' => \Drupal::config('system.site')->get('name'),
  '@user_name' => $user_data->getDisplayName(),
  '@title' => $user_info->project_title,
  '@reason' => $form_state->getValue('message'),
]);

/** Recipients */
$email_to = $user_data->getEmail();
$from = \Drupal::config('research_migration.settings')->get('research_migration_from_email');
$cc   = \Drupal::config('research_migration.settings')->get('research_migration_cc_emails');
$bcc  = \Drupal::config('research_migration.settings')->get('research_migration_emails');

/** Mail params */
$params = [];
$params['subject'] = $email_subject;
$params['body'] = $email_body;
$params['headers'] = [
  'From' => $from,
  'Cc' => $cc,
  'Bcc' => $bcc,
  'MIME-Version' => '1.0',
  'Content-Type' => 'text/plain; charset=UTF-8',
  'Content-Transfer-Encoding' => '8Bit',
  'X-Mailer' => 'Drupal',
];

/** Send mail */
$mail_manager = \Drupal::service('plugin.manager.mail');
$langcode = \Drupal::currentUser()->getPreferredLangcode();

$result = $mail_manager->mail(
  'research_migration',
  'standard',
  $email_to,
  $langcode,
  $params,
  $from,
  TRUE
);

if (!$result['result']) {
  \Drupal::messenger()->addError(t('Error sending email message.'));
}
else {
  \Drupal::messenger()->addStatus(t('Dis-approval email sent successfully.'));
}
 }
 }
        }}
    }}
}
?>
