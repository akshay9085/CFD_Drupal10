<?php

namespace Drupal\cfd_case_study\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides the proposal status form.
 */
class CfdCaseStudyProposalStatusForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cfd_case_study_proposal_status_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $proposal_id = $this->getProposalId();
    // var_dump($proposal_id);die;
    if (!$proposal_id) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('cfd_case_study.proposal_all');
      return [];
    }

    $proposal_data = $this->loadProposal($proposal_id);
    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('cfd_case_study.proposal_all');
      return [];
    }

    $query = \Drupal::database()->select('case_study_submitted_abstracts_file');
    $query->fields('case_study_submitted_abstracts_file');
    $query->condition('proposal_id', $proposal_id);
    $query->condition('filetype', 'A');
    $abstract_file = $query->execute()->fetchObject();

    $account = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
    $student_email = $account ? $account->getEmail() : NULL;

    $faculty_name = $proposal_data->faculty_name ?: 'NA';
    $faculty_department = $proposal_data->faculty_department ?: 'NA';
    $faculty_email = $proposal_data->faculty_email ?: 'NA';

    $version_data = \Drupal::database()
      ->select('case_study_software_version')
      ->fields('case_study_software_version')
      ->condition('id', $proposal_data->version_id)
      ->execute()
      ->fetchObject();
    $version = $version_data ? $version_data->case_study_version : 'NA';

    $simulation_type_data = \Drupal::database()
      ->select('case_study_simulation_type')
      ->fields('case_study_simulation_type')
      ->condition('id', $proposal_data->simulation_type_id)
      ->execute()
      ->fetchObject();
    $simulation_type = $simulation_type_data ? $simulation_type_data->simulation_type : 'NA';
    $form['contributor_name'] = [
      '#type' => 'item',
      '#title' => $this->t('Student name'),
      '#markup' => Link::fromTextAndUrl(
        $proposal_data->name_title . ' ' . $proposal_data->contributor_name,
        Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid])
      )->toString(),
    ];
    $form['student_email_id'] = [
      '#type' => 'item',
      '#title' => $this->t('Student Email'),
      '#plain_text' => $student_email ?: $this->t('Unavailable'),
    ];
    $form['university'] = [
      '#type' => 'item',
      '#title' => $this->t('University/Institute'),
      '#plain_text' => (string) $proposal_data->university,
    ];
    $form['how_did_you_know_about_project'] = [
      '#type' => 'item',
      '#title' => $this->t('How did you know about the project'),
      '#plain_text' => (string) $proposal_data->how_did_you_know_about_project,
    ];
    $form['faculty_name'] = [
      '#type' => 'item',
      '#title' => $this->t('Name of the faculty'),
      '#plain_text' => $faculty_name,
    ];
    $form['faculty_department'] = [
      '#type' => 'item',
      '#title' => $this->t('Department of the faculty'),
      '#plain_text' => $faculty_department,
    ];
    $form['faculty_email'] = [
      '#type' => 'item',
      '#title' => $this->t('Email of the faculty'),
      '#plain_text' => $faculty_email,
    ];
    $form['country'] = [
      '#type' => 'item',
      '#title' => $this->t('Country'),
      '#plain_text' => (string) $proposal_data->country,
    ];
    $form['all_state'] = [
      '#type' => 'item',
      '#title' => $this->t('State'),
      '#plain_text' => (string) $proposal_data->state,
    ];
    $form['city'] = [
      '#type' => 'item',
      '#title' => $this->t('City'),
      '#plain_text' => (string) $proposal_data->city,
    ];
    $form['pincode'] = [
      '#type' => 'item',
      '#title' => $this->t('Pincode/Postal code'),
      '#plain_text' => (string) $proposal_data->pincode,
    ];
    $form['project_title'] = [
      '#type' => 'item',
      '#title' => $this->t('Title of the Case Study Project'),
      '#plain_text' => (string) $proposal_data->project_title,
    ];
    $form['version'] = [
      '#type' => 'item',
      '#title' => $this->t('Version used'),
      '#plain_text' => $version,
    ];
    $form['simulation_type'] = [
      '#type' => 'item',
      '#title' => $this->t('Simulation Type'),
      '#plain_text' => $simulation_type,
    ];
    $form['solver_used'] = [
      '#type' => 'item',
      '#title' => $this->t('Solver used'),
      '#plain_text' => (string) $proposal_data->solver_used,
    ];

   if (!empty($abstract_file->filename) && $abstract_file->filename !== 'NULL') {

  // Extract file name safely
  $resource_file = basename($abstract_file->filename);

  $form['abstract_file_path'] = [
    '#type' => 'item',
    '#title' => $this->t('Abstract file'),
    '#markup' => Link::fromTextAndUrl(
      $resource_file,
      Url::fromRoute('cfd_case_study.project_files', ['proposal_id' => $proposal_id])
    )->toString(),
  ];
}
else {
  $form['abstract_file_path'] = [
    '#type' => 'item',
    '#title' => $this->t('Abstract file'),
    '#markup' => $this->t('Not uploaded'),
  ];
}


    switch ((int) $proposal_data->approval_status) {
      case 0:
        $proposal_status = $this->t('Pending');
        break;

      case 1:
        $proposal_status = $this->t('Approved');
        break;

      case 2:
        $proposal_status = $this->t('Dis-approved');
        break;

      case 3:
        $proposal_status = $this->t('Completed');
        break;

      case 5:
        $proposal_status = $this->t('On Hold');
        break;

      default:
        $proposal_status = $this->t('Unknown');
        break;
    }

    $form['proposal_status'] = [
      '#type' => 'item',
      '#title' => $this->t('Proposal Status'),
      '#plain_text' => $proposal_status,
    ];
if ((int) $proposal_data->approval_status === 0) {
  $form['approve'] = [
    '#type' => 'item',
    '#title' => $this->t('Approve'),
    '#markup' => Link::fromTextAndUrl(
      $this->t('Click here'),
      Url::fromRoute('cfd_case_study.proposal_approval_form', ['id' => $proposal_id])
    )->toString(),
  ];
}

    if ((int) $proposal_data->approval_status === 1) {
      $form['completed'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Completed'),
        '#description' => $this->t('Check if user has provided all the required files and pdfs.'),
      ];
    }

    if ((int) $proposal_data->approval_status === 2) {
      $form['message'] = [
        '#type' => 'item',
        '#title' => $this->t('Reason for disapproval'),
        '#plain_text' => (string) $proposal_data->message,
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $proposal_id = $this->getProposalId();
    if (!$proposal_id) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('cfd_case_study.proposal_all');
      return;
    }

    $proposal_data = $this->loadProposal($proposal_id);
    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('cfd_case_study.proposal_all');
      return;
    }

    if ($form_state->getValue('completed')) {
      $updated = \Drupal::database()->update('case_study_proposal')
        ->fields([
          'approval_status' => 3,
          'actual_completion_date' => time(),
        ])
        ->condition('id', $proposal_id)
        ->execute();

      if (!$updated) {
        $this->messenger()->addError($this->t('Error updating status.'));
        return;
      }

      CreateReadmeFileCaseStudyProject($proposal_id);

      $account = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
      $email_to = $account ? $account->getEmail() : NULL;

      if ($email_to) {
        $config = \Drupal::config('cfd_case_study.settings');
        $from = $config->get('case_study_from_email') ?: \Drupal::config('system.site')->get('mail');
        if (empty($from)) {
          $from = 'no-reply@localhost';
        }
        $bcc = $config->get('case_study_emails');
        $cc = $config->get('case_study_cc_emails');

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

        $params = [];
        $params['case_study_proposal_completed']['proposal_id'] = $proposal_id;
        $params['case_study_proposal_completed']['user_id'] = $proposal_data->uid;
        $params['case_study_proposal_completed']['headers'] = $headers;

        $langcode = $account ? $account->getPreferredLangcode() : \Drupal::languageManager()->getDefaultLanguage()->getId();
        $result = \Drupal::service('plugin.manager.mail')->mail('cfd_case_study', 'case_study_proposal_completed', $email_to, $langcode, $params, $from, TRUE);

        if (empty($result['result'])) {
          $this->messenger()->addError($this->t('Error sending email message.'));
        }
      }

      $this->messenger()->addStatus($this->t('Congratulations! CFD Case Study proposal has been marked as completed. User has been notified of the completion.'));
      \Drupal\Core\Cache\Cache::invalidateTags([
        'case_study_proposal_list',
        "case_study_proposal:$proposal_id",
      ]);
    }

    $form_state->setRedirect('cfd_case_study.proposal_all');
  }

  /**
   * Loads a proposal record.
   *
   * @param int $proposal_id
   *   The proposal identifier.
   *
   * @return object|null
   *   The proposal record, or NULL if not found.
   */
  protected function loadProposal($proposal_id) {
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();

    return $proposal_q ? $proposal_q->fetchObject() : NULL;
  }

  /**
   * Returns the proposal ID from the current request.
   *
   * @return int|null
   *   The proposal identifier or NULL if not available.
   */
  protected function getProposalId() {
    $route_match = \Drupal::routeMatch();
    $proposal_id = $route_match->getParameter('id');

    if (!$proposal_id) {
      $proposal_id = \Drupal::request()->query->get('id');
    }

    return $proposal_id !== NULL ? (int) $proposal_id : NULL;
  }

}
