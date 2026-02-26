<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationSolutionProposalForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;


class LabMigrationSolutionProposalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_solution_proposal_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    // $proposal_id = (int) arg(2);
    $route_match = \Drupal::routeMatch();

    $proposal_id = (int) $route_match->getParameter('id');
// var_dump($proposal_id);die;
       // Load current user entity.
       $current_user_id = \Drupal::currentUser()->id();
$user_entity = \Drupal\user\Entity\User::load($current_user_id);


       // Fetch proposal data.
       $proposal_data = \Drupal::database()
         ->select('lab_migration_proposal', 'lmp')
         ->fields('lmp')
         ->condition('id', $proposal_id)
         ->execute()
         ->fetchObject();
   
      //  if (!$proposal_data) {
      //    \Drupal::messenger()->addError($this->t('Invalid proposal.'));
      //    return new RedirectResponse(Url::fromRoute('<front>')->toString());
      //  }
   
       // Proposer name link.
       $name_link = Link::fromTextAndUrl(
         $proposal_data->name_title . ' ' . $proposal_data->name,
         Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid])
       )->toString();
   
       $form['name'] = [
         '#type' => 'item',
         '#markup' => $name_link,
         '#title' => $this->t('Proposer Name'),
       ];
   
       $form['lab_title'] = [
         '#type' => 'item',
         '#markup' => $proposal_data->lab_title,
         '#title' => $this->t('Title of the Lab'),
       ];
   
       // Fetch experiments.
       $experiment_q = \Drupal::database()
         ->select('lab_migration_experiment', 'lme')
         ->fields('lme')
         ->condition('proposal_id', $proposal_id)
         ->execute();
   
       $experiment_html = '';
       foreach ($experiment_q as $experiment_data) {
         $experiment_html .= $experiment_data->title . '<br/>';
       }
   
       $form['experiment'] = [
         '#type' => 'item',
         '#markup' => $experiment_html,
         '#title' => $this->t('Experiment List'),
       ];
   
       // Form fields.
       $form['solution_provider_name_title'] = [
         '#type' => 'select',
         '#title' => $this->t('Title'),
         '#options' => [
           'Mr' => 'Mr',
           'Ms' => 'Ms',
           'Mrs' => 'Mrs',
           'Dr' => 'Dr',
           'Prof' => 'Prof',
         ],
         '#required' => TRUE,
       ];
   
       $form['solution_provider_name'] = [
         '#type' => 'textfield',
         '#title' => $this->t('Name of the Solution Provider'),
         '#size' => 30,
         '#maxlength' => 50,
         '#required' => TRUE,
       ];
   
       $form['solution_provider_email_id'] = [
         '#type' => 'textfield',
         '#title' => $this->t('Email'),
         '#size' => 30,
        //  '#default_value' => $account->getEmail(),
        '#default_value' => $user_entity ? $user_entity->getEmail() : '',
         '#disabled' => TRUE,
       ];
   
       $form['solution_provider_contact_ph'] = [
         '#type' => 'textfield',
         '#title' => $this->t('Contact No.'),
         '#size' => 30,
         '#maxlength' => 15,
         '#required' => TRUE,
       ];
   
       $form['solution_provider_department'] = [
         '#type' => 'select',
         '#title' => $this->t('Department/Branch'),
         '#options' => \Drupal::service('lab_migration_global')->_lm_list_of_departments(), // Ensure this function exists and returns an array.
         '#required' => TRUE,
       ];
   
       $form['solution_provider_university'] = [
         '#type' => 'textfield',
         '#title' => $this->t('University/Institute'),
         '#size' => 30,
         '#maxlength' => 50,
         '#required' => TRUE,
       ];
   
       $form['country'] = [
         '#type' => 'select',
         '#title' => $this->t('Country'),
         '#options' => [
           'India' => 'India',
           'Others' => 'Others',
         ],
         '#required' => TRUE,
       ];
   
       $form['other_country'] = [
         '#type' => 'textfield',
         '#title' => $this->t('Other than India'),
         '#size' => 100,
         '#attributes' => ['placeholder' => $this->t('Enter your country name')],
         '#states' => [
           'visible' => [
             ':input[name="country"]' => ['value' => 'Others'],
           ],
         ],
       ];
   
       $form['other_state'] = [
         '#type' => 'textfield',
         '#title' => $this->t('State other than India'),
         '#size' => 100,
         '#attributes' => ['placeholder' => $this->t('Enter your state/region name')],
         '#states' => [
           'visible' => [
             ':input[name="country"]' => ['value' => 'Others'],
           ],
         ],
       ];
   
       $form['other_city'] = [
         '#type' => 'textfield',
         '#title' => $this->t('City other than India'),
         '#size' => 100,
         '#attributes' => ['placeholder' => $this->t('Enter your city name')],
         '#states' => [
           'visible' => [
             ':input[name="country"]' => ['value' => 'Others'],
           ],
         ],
       ];
   
       $form['all_state'] = [
         '#type' => 'select',
         '#title' => $this->t('State'),
         '#options' => \Drupal::service('lab_migration_global')->_lm_list_of_states(),
         '#states' => [
           'visible' => [
             ':input[name="country"]' => ['value' => 'India'],
           ],
         ],
       ];
   
       $form['city'] = [
         '#type' => 'select',
         '#title' => $this->t('City'),
         '#options' =>\Drupal::service('lab_migration_global')->_lm_list_of_cities(),
         '#states' => [
           'visible' => [
             ':input[name="country"]' => ['value' => 'India'],
           ],
         ],
       ];
   
       $form['pincode'] = [
         '#type' => 'textfield',
         '#title' => $this->t('Pincode'),
         '#size' => 30,
         '#maxlength' => 6,
         '#attributes' => ['placeholder' => $this->t('Enter pincode....')],
       ];
   
       $form['submit'] = [
         '#type' => 'submit',
         '#value' => $this->t('Apply for Solution'),
       ];
   
       return $form;
     }


 public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    if ($form_state->getValue(['country']) == 'Others') {
      if ($form_state->getValue(['other_country']) == '') {
        $form_state->setErrorByName('other_country', t('Enter country name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      }
      else {
        $form_state->setValue(['country'], $form_state->getValue([
          'other_country'
          ]));
      }
      if ($form_state->getValue(['other_state']) == '') {
        $form_state->setErrorByName('other_state', t('Enter state name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      }
      else {
        $form_state->setValue(['all_state'], $form_state->getValue([
          'other_state'
          ]));
      }
      if ($form_state->getValue(['other_city']) == '') {
        $form_state->setErrorByName('other_city', t('Enter city name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      }
      else {
        $form_state->setValue(['city'], $form_state->getValue(['other_city']));
      }
    }
    else {
      if ($form_state->getValue(['country']) == '') {
        $form_state->setErrorByName('country', t('Select country name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      }
      if ($form_state->getValue(['all_state']) == '') {
        $form_state->setErrorByName('all_state', t('Select state name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      }
      if ($form_state->getValue(['city']) == '') {
        $form_state->setErrorByName('city', t('Select city name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      }
    }

    if ($form_state->getValue(['version']) == 'olderversion') {
      if ($form_state->getValue(['older']) == '') {
        $form_state->setErrorByName('older', t('Please provide valid version'));
      }
    }
    return;
    //$solution_provider_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE solution_provider_uid = ".$user->uid." AND approval_status IN (0, 1) AND solution_status IN (0, 1, 2)");
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('solution_provider_uid', $user->uid);
    $query->condition('approval_status', [
      0,
      1,
    ], 'IN');
    $query->condition('solution_status', [
      0,
      1,
      2,
    ], 'IN');
    $solution_provider_q = $query->execute();
    if ($solution_provider_q->fetchObject()) {
      $form_state->setErrorByName('', t("You have already applied for a solution. Please compelete that before applying for another solution."));
      // drupal_goto('lab-migration/open-proposal');
    }
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $uid = \Drupal::currentUser()->id();

    // $proposal_id = (int) arg(2);
    $route_match = \Drupal::routeMatch();

    $proposal_id = (int) $route_match->getParameter('id');

    if ($form_state->getValue(['version']) == 'olderversion') {
      $form_state->setValue(['version'], $form_state->getValue(['older']));
    }
    //$proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    // if (!$proposal_data) {
    //   \Drupal::messenger()->addmessage("Invalid proposal.", 'error');
    //   // drupal_goto('lab-migration/open-proposal');
    // }
    if ($proposal_data->solution_provider_uid != 0) {
      \Drupal::messenger()->addmessage("Someone has already applied for solving this Lab.", 'error');
      // drupal_goto('lab-migration/open-proposal');
    }
    $user = \Drupal::currentUser();
    $query = "UPDATE {lab_migration_proposal} set solution_provider_uid = :uid, solution_status = 1, solution_provider_name_title = :solution_provider_name_title, solution_provider_name = :solution_provider_contact_name, solution_provider_contact_ph = :solution_provider_contact_ph, solution_provider_department = :solution_provider_department, solution_provider_university = :solution_provider_university , solution_provider_city = :solution_provider_city, solution_provider_pincode = :solution_provider_pincode, solution_provider_state = :solution_provider_state,solution_provider_country = :solution_provider_country WHERE id = :proposal_id";
    $args = [
      ":uid" => $user->id(),
      ":solution_provider_name_title" => $form_state->getValue(['solution_provider_name_title']),
      ":solution_provider_contact_name" => $form_state->getValue(['solution_provider_name']),
      ":solution_provider_contact_ph" => $form_state->getValue(['solution_provider_contact_ph']),
      ":solution_provider_department" => $form_state->getValue(['solution_provider_department']),
      ":solution_provider_university" => $form_state->getValue(['solution_provider_university']),
      ":solution_provider_city" => $form_state->getValue(['city']),
      ":solution_provider_pincode" => $form_state->getValue(['pincode']),
      ":solution_provider_state" => $form_state->getValue(['all_state']),
      ":solution_provider_country" => $form_state->getValue(['country']),
      ":proposal_id" => $proposal_id,
    ];

    $result = \Drupal::database()->query($query, $args);
    \Drupal::messenger()->addmessage("We have received your application. We will get back to you soon.", 'status');
    // /* sending email */
    // $email_to = $user->mail;
    // $from = $config->get('lab_migration_from_email', '');
    // $bcc = $config->get('lab_migration_emails', '');
    // $cc = $config->get('lab_migration_cc_emails', '');
    // $param['solution_proposal_received']['proposal_id'] = $proposal_id;
    // $param['solution_proposal_received']['user_id'] = $user->uid;
    // $param['solution_proposal_received']['headers'] = [
    //   'From' => $from,
    //   'MIME-Version' => '1.0',
    //   'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
    //   'Content-Transfer-Encoding' => '8Bit',
    //   'X-Mailer' => 'Drupal',
    //   'Cc' => $cc,
    //   'Bcc' => $bcc,
    // ];
    // if (!drupal_mail('lab_migration', 'solution_proposal_received', $email_to, language_default(), $param, $from, TRUE)) {
    //   \Drupal::messenger()->addmessage('Error sending email message.', 'error');
    // }
    /* sending email */
    /* $email_to = $config->get('lab_migration_emails', '');
    if (!drupal_mail('lab_migration', 'solution_proposal_received', $email_to , language_default(), $param, $config->get('lab_migration_from_email', NULL), TRUE))
    \Drupal::messenger()->addmessage('Error sending email message.', 'error');*/
    // drupal_goto('lab-migration/open-proposal');
  }

}
?>
