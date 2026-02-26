<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationProposalStatusForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\user\Entity\User;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Drupal\Core\Link;

class LabMigrationProposalStatusForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_proposal_status_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    // $proposal_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();

$proposal_id = (int) $route_match->getParameter('id');
    //$proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    // var_dump($proposal_id);die;
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      }
      else {
        \Drupal::messenger()->addmessage(t('Invalid proposal selected. Please try again.'), 'error');
        // drupal_goto('lab-migration/manage-proposal');
//         $url = Url::fromRoute('lab_migration.manage_proposal')->toString();
// $response = new RedirectResponse($url);
// $response->send();

        return;
      }
    }
    else {
      \Drupal::messenger()->addmessage(t('Invalid proposal selected. Please try again.'), 'error');
      // drupal_goto('lab-migration/manage-proposal');
      return;
    }
    $form['name'] = [
      '#type' => 'item',
      // '#markup' => l($proposal_data->name_title . ' ' . $proposal_data->name, 'user/' . $proposal_data->uid),
      '#markup' => Link::fromTextAndUrl($proposal_data->name_title . ' ' . $proposal_data->name,Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid]))->toString(),

      '#title' => t('Name'),
    ];
    // var_dump($proposal_data);die;
    $form['email_id'] = [
      '#type' => 'item',
      '#markup' => User::load($proposal_data->uid)->getEmail(),
      '#title' => t('Email'),
    ];
    $form['contact_ph'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->contact_ph,
      '#title' => t('Contact No.'),
    ];
    $form['department'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->department,
      '#title' => t('Department/Branch'),
    ];
    $form['university'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->university,
      '#title' => t('University/Institute'),
    ];
    $form['lab_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->lab_title,
      '#title' => t('Title of the Lab'),
    ];
    /* get experiment details */
    $experiment_list = '<ul>';
    //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d ORDER BY id ASC", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('proposal_id', $proposal_id);
    $query->orderBy('id', 'ASC');
    $experiment_q = $query->execute();
    while ($experiment_data = $experiment_q->fetchObject()) {
      $experiment_list .= '<li>' . $experiment_data->title . '</li>Description of Experiment : ' . $experiment_data->description . '<br>';
    }
    $experiment_list .= '</ul>';
    $form['experiment'] = [
      '#type' => 'item',
      '#markup' => $experiment_list,
      '#title' => t('Experiments'),
    ];
    if ($proposal_data->solution_provider_uid == 0) {
      $solution_provider = "User will not provide solution, we will have to provide solution";
    }
    else {
      if ($proposal_data->solution_provider_uid == $proposal_data->uid) {
        $solution_provider = "Proposer will provide the solution of the lab";
      }
      else {
        $solution_provider_user_data = user_load($proposal_data->solution_provider_uid);
        if ($solution_provider_user_data) {
          //$solution_provider = "Solution will be provided by user " . l($solution_provider_user_data->name, 'user/' . $proposal_data->solution_provider_uid);
        }
        else {
          $solution_provider = "User does not exists";
        }
      }
    }
    $form['solution_provider_uid'] = [
      '#type' => 'item',
      '#title' => t('Who will provide the solution'),
      '#markup' => $solution_provider,
    ];
    /*$form['solution_display'] = array(
    '#type' => 'item',
    '#title' => t('Display the solution on the www.dwsim.fossee.in website'),
    '#markup' => ($proposal_data->solution_display == 1) ? "Yes" : "No",
    );*/
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
      default:
        $proposal_status = t('Unkown');
        break;
    }
    $form['proposal_status'] = [
      '#type' => 'item',
      '#markup' => $proposal_status,
      '#title' => t('Proposal Status'),
    ];
    // var_dump($proposal_status);die;
    if ($proposal_data->approval_status == 0) {
      $form['approve'] = [
        '#type' => 'item',
        //'#markup' => l('Click here', 'lab-migration/manage-proposal/approve/' . $proposal_id),
        '#title' => t('Approve'),
      ];
    }
   // var_dump($proposal_data);die;
    if ($proposal_data->approval_status == 1) {
      $form['completed'] = [
        '#type' => 'checkbox',
        '#title' => t('Completed'),
        '#description' => t('Check if user has provided all experiment solutions.'),
      ];
    }
    if ($proposal_data->approval_status == 2) {
      $form['message'] = [
        '#type' => 'item',
        '#markup' => $proposal_data->message,
        '#title' => t('Reason for disapproval'),
      ];
    }
    // var_dump($proposal_data);die;
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl(t('Cancel'), Url::fromUri('internal:/lab-migration/manage-proposal/all'))->toString(),
    ];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    // $proposal_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();

    $proposal_id = (int) $route_match->getParameter('id');
    
    //$proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    // var_dump($proposal_q);die;
// var_dump($proposal_id);die;
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      }
      else {
        \Drupal::messenger()->addmessage(t('Invalid proposal selected. Please try again.'), 'error');
        // drupal_goto('lab-migration/manage-proposal');
        $response = new RedirectResponse('lab_migration.proposal_pending_solution');
         $response->send(); 
        return;
      }
    }
    else {
      \Drupal::messenger()->addmessage(t('Invalid proposal selected. Please try again.'), 'error');
      // drupal_goto('lab-migration/manage-proposal');
      $response = new RedirectResponse('lab_migration.proposal_pending_solution');
      $response->send(); 
      return;
    }
  
    /* set the book status to completed */
    if ($form_state->getValue(['completed']) == 1) {
      $up_query = "UPDATE lab_migration_proposal SET approval_status = :approval_status , expected_completion_date = :expected_completion_date WHERE id = :proposal_id";
      $args = [
        ":approval_status" => '3',
        ":proposal_id" => $proposal_id,
        ":expected_completion_date" => time(),
      ];
      $result = \Drupal::database()->query($up_query, $args);
      \Drupal::service("lab_migration_global")->CreateReadmeFileLabMigration($proposal_id);
      // var_dump($proposal_id);die;
      if (!$result) {
        \Drupal::messenger()->addmessage('Error in update status', 'error');
        return;
      }
      /* sending email */
      // $user_data = user_load($proposal_data->uid);
      // $email_to = $user_data->mail;
      // $from = $config->get('lab_migration_from_email', '');
      // $bcc = $user->mail . ', ' . $config->get('lab_migration_emails', '');
      // $cc = $config->get('lab_migration_cc_emails', '');
      // $param['proposal_completed']['proposal_id'] = $proposal_id;
      // $param['proposal_completed']['user_id'] = $proposal_data->uid;
      // $param['proposal_completed']['headers'] = [
      //   'From' => $from,
      //   'MIME-Version' => '1.0',
      //   'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      //   'Content-Transfer-Encoding' => '8Bit',
      //   'X-Mailer' => 'Drupal',
      //   'Cc' => $cc,
      //   'Bcc' => $bcc,
      // ];
      // if (!drupal_mail('lab_migration', 'proposal_completed', $email_to, language_default(), $param, $from, TRUE)) {
      //   \Drupal::messenger()->addmessage('Error sending email message.', 'error');
      // }
      /*$email_to = $user->mail . ', ' . $config->get('lab_migration_emails', '');;
        if (!drupal_mail('lab_migration', 'proposal_completed', $email_to , language_default(), $param, $config->get('lab_migration_from_email', NULL), TRUE))
        \Drupal::messenger()->addmessage('Error sending email message.', 'error');*/
      \Drupal::messenger()->addmessage('Congratulations! Lab Migration proposal has been marked as completed. User has been notified of the completion.', 'status');
    }
    $response = new RedirectResponse(Url::fromRoute('lab_migration.proposal_all')->toString());
  
    // //   // Send the redirect response
      $response->send();
    return;
  }

}
?>
