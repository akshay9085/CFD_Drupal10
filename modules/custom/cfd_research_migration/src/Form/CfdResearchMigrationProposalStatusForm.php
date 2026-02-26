<?php

/**
 * @file
 * Contains \Drupal\cfd_research_migration\Form\CfdResearchMigrationProposalStatusForm.
 */

namespace Drupal\cfd_research_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\user\Entity\User;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Drupal\Core\Link;
class CfdResearchMigrationProposalStatusForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cfd_research_migration_proposal_status_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    // $proposal_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();

    $proposal_id = (int) $route_match->getParameter('id');
    // var_dump($proposal_id);die;
    $query = \Drupal::database()->select('research_migration_proposal');
    $query->fields('research_migration_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    $query_abstract = \Drupal::database()->select('research_migration_submitted_abstracts_file');
    $query_abstract->fields('research_migration_submitted_abstracts_file');
    $query_abstract->condition('proposal_id', $proposal_id);
    $query_abstract->condition('filetype', 'A');
    $query_abstract_pdf = $query_abstract->execute()->fetchObject();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      } //$proposal_data = $proposal_q->fetchObject()
      else {
        \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
        // drupal_goto('research-migration-project/manage-proposal');
        // $response = new RedirectResponse(Url::fromRoute('cfd_research_migration.proposal_all')->toString());
// $response->send();
        return;
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
      // drupal_goto('research-migration-project/manage-proposal');
      // $response = new RedirectResponse(Url::fromRoute('cfd_research_migration.proposal_all')->toString());
// $response->send();
      return;
    }
    // var_dump($proposal_data);die;
    if ($proposal_data->faculty_name == '') {
      $faculty_name = 'NA';
    }
    else {
      $faculty_name = $proposal_data->faculty_name;
    }
    if ($proposal_data->faculty_department == '') {
      $faculty_department = 'NA';
    }
    else {
      $faculty_department = $proposal_data->faculty_department;
    }
    if ($proposal_data->faculty_email == '') {
      $faculty_email = 'NA';
    }
    else {
      $faculty_email = $proposal_data->faculty_email;
    }
    $query = \Drupal::database()->select('research_migration_software_version');
    $query->fields('research_migration_software_version');
    $query->condition('id', $proposal_data->version_id);
    $version_data = $query->execute()->fetchObject();
    if (!$version_data) {
      $version = 'NA';
    }
    else {
      $version = $version_data->research_migration_version;
    }
    $query = \Drupal::database()->select('research_migration_simulation_type');
    $query->fields('research_migration_simulation_type');
    $query->condition('id', $proposal_data->simulation_type_id);
    $simulation_type_data = $query->execute()->fetchObject();
    if (!$simulation_type_data) {
      $simulation_type = 'NA';
    }
    else {
      $simulation_type = $simulation_type_data->simulation_type;
    }
    $form['contributor_name'] = [
      '#type' => 'item',
      // '#markup' => l($proposal_data->name_title . ' ' . $proposal_data->contributor_name, 'user/' . $proposal_data->uid),
      
      '#markup' => Link::fromTextAndUrl(
        $proposal_data->name_title . ' ' . $proposal_data->contributor_name,
        Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid])
      )->toString(),
      
      '#title' => t('Student name'),
    ];
    $form['student_email_id'] = [
      '#title' => t('Student Email'),
      '#type' => 'item',
      // '#markup' => User::load($proposal_data->uid)->mail,
      '#markup' =>  $user->getEmail(),

      '#title' => t('Email'),
    ];
    $form['university'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->university,
      '#title' => t('University/Institute'),
    ];
    $form['how_did_you_know_about_project'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->how_did_you_know_about_project,
      '#title' => t('How did you know about the project'),
    ];
    $form['faculty_name'] = [
      '#type' => 'item',
      '#markup' => $faculty_name,
      '#title' => t('Name of the faculty'),
    ];
    $form['faculty_department'] = [
      '#type' => 'item',
      '#markup' => $faculty_department,
      '#title' => t('Department of the faculty'),
    ];
    $form['faculty_email'] = [
      '#type' => 'item',
      '#markup' => $faculty_email,
      '#title' => t('Email of the faculty'),
    ];
    $form['country'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->country,
      '#title' => t('Country'),
    ];
    $form['all_state'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->state,
      '#title' => t('State'),
    ];
    $form['city'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->city,
      '#title' => t('City'),
    ];
    $form['pincode'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->pincode,
      '#title' => t('Pincode/Postal code'),
    ];
    $form['project_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->project_title,
      '#title' => t('Title of the Research Migration Project'),
    ];
    $form['version'] = [
      '#type' => 'item',
      '#markup' => $version,
      '#title' => t('Version used'),
    ];
    $form['simulation_type'] = [
      '#type' => 'item',
      '#markup' => $simulation_type,
      '#title' => t('Simulation Type'),
    ];
    $form['solver_used'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->solver_used,
      '#title' => t('Solver used'),
    ];
    /************************** reference link filter *******************/
    $url = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i';
    $reference = preg_replace($url, '<a href="$0" target="_blank" title="$0">$0</a>', $proposal_data->reference);
    /******************************/
    /*$form['reference'] = array(
    '#type' => 'item',
    '#markup' => $reference,
    '#title' => t('References')
    );*/
    if (($query_abstract_pdf->filename != "") && ($query_abstract_pdf->filename != 'NULL')) {
      $str = substr($query_abstract_pdf->filename, strrpos($query_abstract_pdf->filename, '/'));
      $resource_file = ltrim($str, '/');

      $form['abstract_file_path'] = [
        '#type' => 'item',
        '#title' => t('Synopsis file '),
        // '#markup' => l($resource_file, 'research-migration-project/download/project-file/' . $proposal_id) . "",
         '#markup' => Link::fromTextAndUrl($resource_file,Url::fromUserInput('/research-migration-project/download/project-file/' . $proposal_id)
  )->toString(),
];

      
    } //$proposal_data->user_defined_compound_filepath != ""
    else {
      $form['abstract_file_path'] = [
        '#type' => 'item',
        '#title' => t('Synopsis file '),
        '#markup' => "Not uploaded<br><br>",
      ];
    }
    $proposal_status = '';
    switch ($proposal_data->approval_status) {
      case 0:
        $proposal_status = t('Pending');
        break;
      case 1:
        $proposal_status = t('Approved');
        break;
      case 2:
        $proposal_status = t('Dis-approved');
        break;
      case 3:
        $proposal_status = t('Completed');
        break;
      case 5:
        $approval_status = t('On Hold');
        break;
      default:
        $proposal_status = t('Unkown');
        break;
    }
    $form['proposal_status'] = [
      '#type' => 'item',
      '#markup' => $proposal_status,
      '#title' => t('Proposal Status'),
    ];
    if ($proposal_data->approval_status == 0) {
      $form['approve'] = [
        '#type' => 'item',
        // '#markup' => l('Click here', 'research-migration-project/manage-proposal/approve/' . $proposal_id),

'#markup' => Link::fromTextAndUrl(
  $this->t('Click here'),
  Url::fromUserInput('/research-migration-project/manage-proposal/approve/' . $proposal_id)
)->toString(),

        '#title' => t('Approve'),
      ];
    } //$proposal_data->approval_status == 0
    if ($proposal_data->approval_status == 1) {
      $form['completed'] = [
        '#type' => 'checkbox',
        '#title' => t('Completed'),
        '#description' => t('Check if user has provided all the required files and pdfs.'),
      ];
    } //$proposal_data->approval_status == 1
    if ($proposal_data->approval_status == 2) {
      $form['message'] = [
        '#type' => 'item',
        '#markup' => $proposal_data->message,
        '#title' => t('Reason for disapproval'),
      ];
    } //$proposal_data->approval_status == 2
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      // '#markup' => l(t('Cancel'), 'research-migration-project/manage-proposal/all'),
      
    ];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    // $proposal_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();

    $proposal_id = (int) $route_match->getParameter('id');
    //$proposal_q = \Drupal::database()->query("SELECT * FROM {research_migration_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('research_migration_proposal');
    $query->fields('research_migration_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      } //$proposal_data = $proposal_q->fetchObject()
      else {
        \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
        // drupal_goto('research-migration-project/manage-proposal');
        return;
      }

    } //$proposal_q
    // else {
    //   \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
    //   // drupal_goto('research-migration-project/manage-proposal');
    //   return;
    // }
    /* set the book status to completed */
    if ($form_state->getValue(['completed']) == 1) {
      $up_query = "UPDATE research_migration_proposal SET approval_status = :approval_status , actual_completion_date = :expected_completion_date WHERE id = :proposal_id";
      $args = [
        ":approval_status" => '3',
        ":proposal_id" => $proposal_id,
        ":expected_completion_date" => time(),
      ];
      $result = \Drupal::database()->query($up_query, $args);
      \Drupal::service("cfd_research_migration_global")->CreateReadmeFileResearchMigrationProject($proposal_id);
      if (!$result) {
        \Drupal::messenger()->addMessage('Error in update status', 'error');
        return;
      } //!$result
        /* sending email */
$user_data = \Drupal\user\Entity\User::load($proposal_data->uid);

if ($user_data && $user_data->getEmail()) {

  $email_to = $user_data->getEmail();

  $config = \Drupal::config('research_migration.settings');
  $site_mail = \Drupal::config('system.site')->get('mail');

  // SAFETY: Never allow NULL email headers
  $from = $config->get('research_migration_from_email') ?: $site_mail;
  $cc   = $config->get('research_migration_cc_emails') ?: '';
  $extra_bcc = $config->get('research_migration_emails') ?: '';

  // Build safe BCC list
  $bcc_list = array_filter([
    \Drupal::currentUser()->getEmail(),
    $extra_bcc,
  ]);

  $bcc = implode(', ', $bcc_list);

  $params['research_migration_proposal_completed']['proposal_id'] = $proposal_id;
  $params['research_migration_proposal_completed']['user_id'] = $proposal_data->uid;

  $params['research_migration_proposal_completed']['headers'] = [
    'From' => $from,
  ];

  if (!empty($cc)) {
    $params['research_migration_proposal_completed']['headers']['Cc'] = $cc;
  }

  if (!empty($bcc)) {
    $params['research_migration_proposal_completed']['headers']['Bcc'] = $bcc;
  }

  /** @var \Drupal\Core\Mail\MailManagerInterface $mail_manager */
  $mail_manager = \Drupal::service('plugin.manager.mail');

  $result = $mail_manager->mail(
    'research_migration',
    'research_migration_proposal_completed',
    $email_to,
    $user_data->getPreferredLangcode(),
    $params,
    $from,
    TRUE
  );

  if (!$result['result']) {
    \Drupal::messenger()->addError(t('Error sending email message.'));
  }
} 
      \Drupal::messenger()->addMessage('Congratulations! CFD research migration proposal has been marked as completed. User has been notified of the completion.', 'status');
    }
    // drupal_goto('research-migration-project/manage-proposal');

    $form_state->setRedirectUrl(
  Url::fromUserInput('/research-migration-project/manage-proposal/all')
);
    return;

  }

}
?>
