<?php

/**
 * @file
 * Contains \Drupal\cfd_research_migration\Form\CfdResearchMigrationProposalEditForm.
 */

namespace Drupal\cfd_research_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\Core\Link;

class CfdResearchMigrationProposalEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cfd_research_migration_proposal_edit_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    // $proposal_id = (int) arg(3);

    $route_match = \Drupal::routeMatch();

    $proposal_id = (int) $route_match->getParameter('proposal_id');
    

    //$proposal_q = \Drupal::database()->query("SELECT * FROM {research_migration_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('research_migration_proposal');
    $query->fields('research_migration_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    /*if ($proposal_q) {
        if ($proposal_data = $proposal_q->fetchObject()) {
            /* everything ok 
        } //$proposal_data = $proposal_q->fetchObject()
        else {
            \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
            drupal_goto('research-migration-project/manage-proposal');
            return;
        }
    } //$proposal_q
    else {
        \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
        drupal_goto('research-migration-project/manage-proposal');
        return;
    }*/
    $user_data = User::load($proposal_data->uid);
    $form['name_title'] = [
      '#type' => 'select',
      '#title' => t('Title'),
      '#options' => [
        'Dr' => 'Dr',
        'Prof' => 'Prof',
        'Mr' => 'Mr',
        'Ms' => 'Ms',
      ],
      '#required' => TRUE,
      '#default_value' => $proposal_data->name_title,
    ];
    $form['contributor_name'] = [
      '#type' => 'textfield',
      '#title' => t('Name of the Proposer'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $proposal_data->contributor_name,
    ];
    $form['student_email_id'] = [
      '#type' => 'item',
      '#title' => t('Email'),
      '#markup' => $user->getEmail(),
    ];
    $form['university'] = [
      '#type' => 'textfield',
      '#title' => t('University/Institute'),
      '#size' => 200,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#default_value' => $proposal_data->university,
    ];
    $form['institute'] = [
      '#type' => 'textfield',
      '#title' => t('Institute'),
      '#size' => 80,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#default_value' => $proposal_data->institute,
    ];
    $form['how_did_you_know_about_project'] = [
      '#type' => 'textfield',
      '#title' => t('How did you come to know about the Research Migration Project?'),
      '#default_value' => $proposal_data->how_did_you_know_about_project,
      '#required' => TRUE,
    ];
    $form['faculty_name'] = [
      '#type' => 'textfield',
      '#title' => t('Name of the Faculty'),
      '#size' => 50,
      '#maxlength' => 50,
      '#validated' => TRUE,
      '#default_value' => $proposal_data->faculty_name,
    ];
    $form['faculty_department'] = [
      '#type' => 'textfield',
      '#title' => t('Department of the Faculty'),
      '#size' => 50,
      '#maxlength' => 50,
      '#validated' => TRUE,
      '#default_value' => $proposal_data->faculty_department,
    ];
    $form['faculty_email'] = [
      '#type' => 'textfield',
      '#title' => t('Email id of the Faculty'),
      '#size' => 255,
      '#maxlength' => 255,
      '#validated' => TRUE,
      '#default_value' => $proposal_data->faculty_email,
    ];
    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => ['India' => 'India', 'Others' => 'Others'],
      '#required' => TRUE,
      ];
      $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other than India'),
      '#states' => ['visible' => [':input[name="country"]' => ['value' => 'Others']]],
      ];
      
      $form['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => [
      'India' => 'India',
      'Others' => 'Others',
      ],
      '#required' => TRUE,
      '#tree' => TRUE,
      ];
      $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => t('Other Country'),
      '#size' => 100,
      '#attributes' => [
      'placeholder' => t('Enter your country name')
      ],
      '#states' => [
      'visible' => [
      ':input[name="country"]' => [
      'value' => 'Others'
      ]
      ]
      ],
      ];
      $form['other_state'] = [
      '#type' => 'textfield',
      '#title' => t('State'),
      '#size' => 100,
      '#attributes' => [
      'placeholder' => t('Enter your state/region name')
      ],
      '#states' => [
      'visible' => [
      ':input[name="country"]' => [
      'value' => 'Others'
      ]
      ]
      ],
      ];
    
    $form['other_city'] = [
      '#type' => 'textfield',
      '#title' => t('City other than India'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your city name')
        ],
      '#default_value' => $proposal_data->city,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['all_state'] = [
      '#type' => 'select',
      '#title' => t('State'),
      '#options' => \Drupal::service("cfd_research_migration_global")->_df_list_of_states(),
      '#default_value' => $proposal_data->state,
      '#validated' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['city'] = [
      '#type' => 'select',
      '#title' => t('City'),
      '#options' => \Drupal::service("cfd_research_migration_global")->_df_list_of_cities(),
      '#default_value' => $proposal_data->city,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['pincode'] = [
      '#type' => 'textfield',
      '#title' => t('Pincode'),
      '#size' => 30,
      '#maxlength' => 6,
      '#default_value' => $proposal_data->pincode,
      '#attributes' => [
        'placeholder' => 'Insert pincode of your city/ village....'
        ],
    ];
    $form['project_title'] = [
      '#type' => 'textfield',
      '#title' => t('Title of the Research Migration Project'),
      '#size' => 300,
      '#maxlength' => 350,
      '#required' => TRUE,
      '#default_value' => $proposal_data->project_title,
    ];
    $version_options = \Drupal::service("cfd_research_migration_global")->_rm_list_of_versions();
    $form['version'] = [
      '#type' => 'select',
      '#title' => t('Version used'),
      '#options' => $version_options,
      '#default_value' => $proposal_data->version_id,
    ];
    $simulation_type_options = \Drupal::service("cfd_research_migration_global")->_rm_list_of_simulation_types();
    $form['simulation_type'] = [
      '#type' => 'select',
      '#title' => t('Simulation Type used'),
      '#options' => $simulation_type_options,
      '#default_value' => $proposal_data->simulation_type_id,
      '#ajax' => [
        'callback' => 'ajax_solver_used_callback'
        ],
    ];
    // $simulation_id = !$form_state->getValue(['simulation_type']) ? $form_state->getValue([
    //   'simulation_type'
    //   ]) : $proposal_data->simulation_type_id;

    // $form['solver_used'] = [
    //   '#type' => 'select',
    //   '#title' => t('Select the Solver to be used'),
    //   '#options' => \Drupal::service("cfd_research_migration_global")->_rm_list_of_solvers($simulation_id),
    //   '#prefix' => '<div id="ajax-solver-replace">',
    //   '#suffix' => '</div>',
    //   '#states' => [
    //     'invisible' => [
    //       ':input[name="simulation_type"]' => [
    //         'value' => 19
    //         ]
    //       ]
    //     ],
    //   //'#required' => TRUE
    //     '#default_value' => $proposal_data->solver_used,
    // ];

    // $form['solver_used_text'] = [
    //   '#type' => 'textfield',
    //   '#title' => t('Enter the Solver to be used'),
    //   '#size' => 100,
    //   '#description' => t('Maximum character limit is 50'),
    //   //'#required' => TRUE,
    //     '#prefix' => '<div id="ajax-solver-text-replace">',
    //   '#suffix' => '</div>',
    //   '#states' => [
    //     'visible' => [
    //       ':input[name="simulation_type"]' => [
    //         'value' => 19
    //         ]
    //       ]
    //     ],
    //   '#default_value' => $proposal_data->solver_used,
    // ];

     $simulation_id = $form_state->hasValue('simulation_type') ? $form_state->getValue('simulation_type') : key($simulation_type_options);
  
  if ($simulation_id < 19) {
  $form['solver_used'] = [
  '#type' => 'select',
  '#title' => t('Select the Solver to be used'),
  '#options' => \Drupal::service("cfd_research_migration_global")->_rm_list_of_solvers($simulation_id),
  '#default_value' => 0,
  '#prefix' => '<div id="ajax-solver-replace">',
  '#suffix' => '</div>',
  '#states' => [
  'invisible' => [
  ':input[name="simulation_type"]' => ['value' => 19]
  ]
  ],
  '#required' => TRUE,
  ];
  }
  
  $form['solver_used_text'] = [
  '#type' => 'textfield',
  '#title' => t('Enter the Solver to be used'),
  '#size' => 100,
  '#description' => t('Maximum character limit is 50'),
  '#prefix' => '<div id="ajax-solver-text-replace">',
  '#suffix' => '</div>',
  '#states' => [
  'visible' => [
  ':input[name="simulation_type"]' => ['value' => 19]
  ]
  ],
  ];
 
    /* $form['solver_used'] = array(
        '#type' => 'textfield',
        '#title' => t('Solver to be used'),
        '#size' => 50,
        '#maxlength' => 50,
        '#required' => true,
        '#default_value' => $proposal_data->solver_used,
    );*/
    $form['date_of_proposal'] = [
      '#type' => 'textfield',
      '#title' => t('Date of Proposal'),
      '#default_value' => date('d/m/Y', $proposal_data->creation_date),
      '#disabled' => TRUE,
    ];
    $form['delete_proposal'] = [
      '#type' => 'checkbox',
      '#title' => t('Delete Proposal'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'item',
      // '#markup' => l(t('Cancel'), 'research-migration-project/manage-proposal'),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->getValue(['simulation_type']) < 19) {
      if ($form_state->getValue(['solver_used']) == '0') {
        $form_state->setErrorByName('solver_used', t('Please select an option'));
      }
    }
    else {
      if ($form_state->getValue(['simulation_type']) == 19) {
        if ($form_state->getValue(['solver_used_text']) != '') {
          if (strlen($form_state->getValue(['solver_used_text'])) > 100) {
            $form_state->setErrorByName('solver_used_text', t('Maximum charater limit is 100 charaters only, please check the length of the solver used'));
          } //strlen($form_state['values']['project_title']) > 250
          else {
            if (strlen($form_state->getValue(['solver_used_text'])) < 7) {
              $form_state->setErrorByName('solver_used_text', t('Minimum charater limit is 7 charaters, please check the length of the solver used'));
            }
          } //strlen($form_state['values']['project_title']) < 10
        }
        else {
          $form_state->setErrorByName('solver_used_text', t('Solver used cannot be empty'));
        }
      }
    }
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    // $proposal_id = (int) arg(3);
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
    else {
      \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
      // drupal_goto('research-migration-project/manage-proposal');
      return;
    }
    /* delete proposal */
    if ($form_state->getValue(['delete_proposal']) == 1) {
      /* sending email */
$user_data = User::load($proposal_data->uid);

if ($user_data && $user_data->getEmail()) {

  $email_to = $user_data->getEmail();

  $config = \Drupal::config('research_migration.settings');

  $from = $config->get('research_migration_from_email');
  $bcc  = $config->get('research_migration_emails');
  $cc   = $config->get('research_migration_cc_emails');

  $params['research_migration_proposal_deleted']['proposal_id'] = $proposal_id;
  $params['research_migration_proposal_deleted']['user_id'] = $proposal_data->uid;

  $params['research_migration_proposal_deleted']['headers'] = [
    'From' => $from,
    'Cc'   => $cc,
    'Bcc'  => $bcc,
  ];

  /** @var MailManagerInterface $mail_manager */
  $mail_manager = \Drupal::service('plugin.manager.mail');

  $result = $mail_manager->mail(
    'research_migration',
    'research_migration_proposal_deleted',
    $email_to,
    $user_data->getPreferredLangcode(),
    $params,
    $from,
    TRUE
  );

  if (!$result['result']) {
    \Drupal::messenger()->addMessage(t(' Sending email message.'));
  }
}
      \Drupal::messenger()->addMessage(t('research migration proposal has been deleted.'), 'status');
      if (_rm_rrmdir_project($proposal_id) == TRUE) {
        $query = db_delete('research_migration_proposal');
        $query->condition('id', $proposal_id);
        $num_deleted = $query->execute();
        \Drupal::messenger()->addMessage(t('Proposal Deleted'), 'status');
        // drupal_goto('research-migration-project/manage-proposal');
        return;
      } //rrmdir_project($proposal_id) == TRUE
    } //$form_state['values']['delete_proposal'] == 1
    /* update proposal */
    $v = $form_state->getValues();
    $project_title = $v['project_title'];
    $proposar_name = $v['name_title'] . ' ' . $v['contributor_name'];
    $university = $v['university'];
    $directory_names = _rm_df_dir_name($project_title, $proposar_name);
    if (_rm_DF_RenameDir($proposal_id, $directory_names)) {
      $directory_name = $directory_names;
    } //LM_RenameDir($proposal_id, $directory_names)
    else {
      return;
    }
    $simulation_id = $v['simulation_type'];
    if ($simulation_id < 13) {
      $solver = $v['solver_used'];
    }
    else {
      $solver = $v['solver_used_text'];
    }
    $query = "UPDATE research_migration_proposal SET
				name_title=:name_title,
				contributor_name=:contributor_name,
				university=:university,
				institute=:institute,
				how_did_you_know_about_project = :how_did_you_know_about_project,
				faculty_name = :faculty_name,
				faculty_department = :faculty_department,
				faculty_email = :faculty_email,
				city=:city,
				pincode=:pincode,
				state=:state,
				project_title=:project_title,
                version_id=:version_id,
                simulation_type_id=:simulation_type_id,
				solver_used=:solver_used,
				directory_name=:directory_name
				WHERE id=:proposal_id";
    $args = [
      ':name_title' => $v['name_title'],
      ':contributor_name' => $v['contributor_name'],
      ':university' => $v['university'],
      ":institute" => $v['institute'],
      ":how_did_you_know_about_project" => $v['how_did_you_know_about_project'],
      ":faculty_name" => $v['faculty_name'],
      ":faculty_department" => $v['faculty_department'],
      ":faculty_email" => $v['faculty_email'],
      ':city' => $v['city'],
      ':pincode' => $v['pincode'],
      ':state' => $v['all_state'],
      ':project_title' => $project_title,
      ':version_id' => $v['version'],
      ':simulation_type_id' => $simulation_id,
      ":solver_used" => $solver,
      ':directory_name' => $directory_name,
      ':proposal_id' => $proposal_id,
    ];
    $result = \Drupal::database()->query($query, $args);
    \Drupal::messenger()->addMessage(t('Proposal Updated'), 'status');
  }

}
?>
