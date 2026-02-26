<?php
namespace Drupal\lab_migration\Form;

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
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInterface;


class LabMigrationProposalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_proposal_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $form = [];
    // $state = \Drupal::service("lab_migration_global")->_lab_migration_list_of_states();
    // $selected_state = $form_state->getValue('all_state') ?: key($state);
    // // $selected_state = !$form_state->getValue(['all_state']) ? $form_state->getValue([
    //   // 'all_state'
    //   // :key($state);
    //   $district = \Drupal::service("lab_migration_global")->_lab_migration_list_of_district();
    //   $selected_district = $form_state->getValue('district') ?: key($district); 
    //   // $selected_district = !$form_state->getValue(['district']) ? $form_state->getValue([
    //   // 'district'
    //   // ]):key($district);
    //   $city = \Drupal::service("lab_migration_global")->_lm_list_of_cities();
    //   $selected_city = $form_state->getValue('city') ?: key($city);
    // // $selected_city = !$form_state->getValue(['city']) ? $form_state->getValue([
    //   // 'city'
    //   // ]):key($city);
    //   $pincode = \Drupal::service("lab_migration_global")->_lab_migration_list_of_city_pincode();
    //   $selected_pincode= $form_state->getValue('pincode') ?: key($pincode);
      // $selected_pincode = !$form_state->getValue(['picode'])?$form_state->getValue([
      // 'pincode'
      // ]):key($pincode);
    
    /************************ start approve book details ************************/
    if ($user->isAnonymous()) {
      // $msg = \Drupal::messenger()->addError(t('This is an error message, red in color'));
      $url = Link::fromTextAndUrl(t('login'), Url::fromRoute('user.page'))->toString();
      
      $msg = \Drupal::messenger()->addmessage(t('It is mandatory to ' . Link::fromTextAndUrl(t('login'), Url::fromRoute('user.page'))->toString() . ' on this website to access the lab proposal form. If you are new user please create a new account first.'));
      
      // RedirectResponse('lab-migration-project');
      // \Drupal::RedirectResponse('user');
  //     $redirect = new RedirectResponse($url);
  //     $redirect->send();
  // return $msg;
  // Redirect to the login page
  $response = new RedirectResponse(Url::fromRoute('user.page')->toString());

  $response->send();
  return $msg;


    }
    //
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('uid', $user->id());
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    if ($proposal_data) {
      if ($proposal_data->approval_status == 0 || $proposal_data->approval_status == 1) {
         \Drupal::messenger()->addmessage(t('We have already received your proposal.'));
        // Create a redirect response to the front page
$response = new RedirectResponse(Url::fromRoute('<front>')->toString());

// Send the redirect response
//$response->send();

      return $response;
      }
      }
    $form['#attributes'] = ['enctype' => "multipart/form-data"];
    $form['name_title'] = [
      '#type' => 'select',
      '#title' => t('Title'),
      '#options' => [
        'Dr' => 'Dr',
        'Prof' => 'Prof',
      ],
      '#required' => TRUE,
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => t('Name of the Proposer'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your full name')
        ],
      '#maxlength' => 100,
      '#required' => TRUE,
    ];
    $form['email_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#size' => 30,
      '#value' =>  $user ? $user->getEmail() : '',
      '#disabled' => TRUE,
    ];
    $form['contact_ph'] = [
      '#type' => 'textfield',
      '#title' => t('Contact No.'),
      '#size' => 30,
      '#attributes' => [
        'placeholder' => t('Enter your contact number')
        ],
      '#maxlength' => 15,
      '#required' => TRUE,
    ];
    $form['department'] = [
      '#type' => 'select',
      '#title' => t('Department/Branch'),
      '#options' =>\Drupal::service("lab_migration_global")->_lm_list_of_departments(),
      '#required' => TRUE,
    ];
    $form['university'] = [
      '#type' => 'textfield',
      '#title' => t('University/ Institute'),
      '#size' => 80,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Insert full name of your institute/ university.... '
        ],
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
      '#validated' => TRUE,
    ];
    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => t('Other than India'),
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
      '#title' => t('State other than India'),
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
      '#options' => \Drupal::service("lab_migration_global")->_lm_list_of_states(),
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
      '#options' => \Drupal::service("lab_migration_global")->_lm_list_of_cities(),
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
      '#required' => FALSE,
      '#attributes' => [
        'placeholder' => 'Enter pincode....'
        ],
    ];
    /***************************************************************************/
    $form['hr'] = [
      '#type' => 'item',
      '#markup' => '<hr>',
    ];
    $form['lab_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title of the Lab'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $first_experiemnt = TRUE;
    for ($counter = 1; $counter <= 15; $counter++) {
      if ($counter <= 1) {
        $form['lab_experiment-' . $counter] = [
          '#type' => 'textfield',
          '#attributes' => array('class' => array('form-control')),
          '#title' => t('Title of the Experiment ') . $counter,
          '#size' => 100,
          '#required' => TRUE,
        ];
        $namefield = "lab_experiment-" . $counter;
        $form['lab_experiment_description-' . $counter] = [
          '#type' => 'textarea',
          '#required' => TRUE,
          '#attributes' => [
            'placeholder' => t('Enter Description for your experiment ' . $counter),
            'cols' => 50,
            'rows' => 4,
          ],
          '#attributes' => array('class' => array('form-control')),
          '#title' => t('Description for Experiment ') . $counter,
          '#states' => [
            'invisible' => [
              ':input[name=' . $namefield . ']' => [
                'value' => ""
                ]
              ]
            ],
        ];
      }
      else {
        $form['lab_experiment-' . $counter] = [
          '#type' => 'textfield',
          '#attributes' => array('class' => array('form-control')),
          '#title' => t('Title of the Experiment ') . $counter,
          '#size' => 50,
          '#required' => FALSE,
        ];
        $namefield = "lab_experiment-" . $counter;
        $form['lab_experiment_description-' . $counter] = [
          '#type' => 'textarea',
          '#required' => FALSE,
          '#attributes' => [
            'placeholder' => t('Enter Description for your experiment ' . $counter),
            'cols' => 50,
            'rows' => 4,
          ],
          '#title' => t('Description for Experiment ') . $counter,
          '#states' => [
            'invisible' => [
              ':input[name=' . $namefield . ']' => [
                'value' => ""
                ]
              ]
            ],
        ];
      }
      $first_experiemnt = FALSE;
    }
    $form['solution_provider_uid'] = [
      '#type' => 'radios',
      '#title' => t('Do you want to provide the solution'),
      '#options' => [
        '1' => 'Yes',
        '2' => 'No',
      ],
      '#required' => TRUE,
      '#default_value' => '1',
      '#description' => 'If you dont want to provide the solution then it will be opened for the community, anyone may come forward and provide the solution.',
    ];
    $form['solution_display'] = [
      '#type' => 'hidden',
      '#title' => t('Do you want to display the solution on the www.cfd.fossee.in website'),
      '#options' => [
        '1' => 'Yes'
        ],
      '#required' => TRUE,
      '#default_value' => '1',
      '#description' => 'If yes, solutions will be made available to everyone for downloading.',
      '#disabled' => FALSE,
    ];
    $form['problem_statement'] = [
      '#type' => 'fieldset',
      '#title' => t('Problem statement for the proposed lab<span style="color:red">*</span>'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $config = $this->config('lab_migration.settings');
    $form['problem_statement']['ps_file'] = [
      '#type' => 'file',
      '#title' => t('<span style="color:red;font-weight:bold">NOTE: </span>Please upload a Problem Statement for each experiment proposed in a document format. To view the template of the document please click <a href="https://cfd.fossee.in/sites/default/files/Problem_Statement_Template.doc" target="_blank">here</a>'),
      '#size' => 48,
      '#description' => $this->t('Only alphabets and numbers are allowed as a valid filename.') . '<br />' . $this->t('Allowed file extensions: ') . 
      $config->get('lab_migration_problem_statement_extensions', ''),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!preg_match('/^[0-9\ \+]{0,15}$/', $form_state->getValue(['contact_ph']))) {
      $form_state->setErrorByName('contact_ph', t('Invalid contact phone number'));
    }
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
    for ($counter = 1; $counter <= 15; $counter++) {
      $experiment_field_name = 'lab_experiment-' . $counter;
      $experiment_description = 'lab_experiment_description-' . $counter;
      if (strlen(trim($form_state->getValue([$experiment_field_name]))) >= 1) {
        if (strlen(trim($form_state->getValue([$experiment_description]))) <= 49) {
          $form_state->setErrorByName($experiment_description, t('Description should be minimum of 50 characters'));
        }
      }
    }
   
    if (isset($_FILES['files'])) {
      /* check if atleast one source or result file is uploaded */
      if (!($_FILES['files']['name']['ps_file'])) {
        $form_state->setErrorByName('ps_file', t('Please upload file with the problem statement.'));
      }
      /* check for valid filename extensions */
      $config = $this->config('lab_migration.settings');

      foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
        if ($file_name) {
          $allowed_extensions_str = $config->get('lab_migration_problem_statement_extensions', '');
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
          // if (!lab_migration_check_valid_filename($_FILES['files']['name'][$file_form_name])) {
          //   $form_state->setErrorByName($file_form_name, t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
          // }
        } //$file_name
      } //$_FILES['files']['name'] as $file_form_name => $file_name
    }
    return;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $root_path = \Drupal::service("lab_migration_global")->lab_migration_path();
    $user = \Drupal::currentUser();
    // if (!$user->uid) {
    //   \Drupal::messenger()->addmessage('It is mandatory to login on this website to access the proposal form', 'error');
    //   return;
    // }
    $solution_provider_uid = 0;
    $solution_status = 0;
    $solution_provider_name_title = '';
    $solution_provider_name = '';
    $solution_provider_contact_ph = '';
    $solution_provider_department = '';
    $solution_provider_university = '';
    $solution_provider_city = '';
    $solution_provider_country = '';
    $solution_provider_state = '';
    $solution_provider_pincode = '';
    if ($form_state->getValue(['solution_provider_uid']) == "1") {
      $solution_provider_uid =$user->id();
      $solution_status = 1;
      $solution_provider_name_title = $form_state->getValue(['name_title']);
      $solution_provider_name = $form_state->getValue(['name']);
      $solution_provider_contact_ph = $form_state->getValue(['contact_ph']);
      $solution_provider_department = $form_state->getValue(['department']);
      $solution_provider_university = $form_state->getValue(['university']);
      $solution_provider_city = $form_state->getValue(['city']);
      $solution_provider_country = $form_state->getValue(['country']);
      $solution_provider_state = $form_state->getValue(['all_state']);
      $solution_provider_pincode = $form_state->getValue(['pincode']);
    }
    else {
      $solution_provider_uid = 0;
    }
    $solution_display = 0;
    if ($form_state->getValue(['solution_display']) == "1") {
      $solution_display = 1;
    }
    else {
      $solution_display = 1;
    }
   
    /* inserting the user proposal */
    $v = $form_state->getValues();
    $lab_title = $v['lab_title'];
    $proposar_name = $v['name_title'] . ' ' . $v['name'];
    $university = $v['university'];
    $directory_name = \Drupal::service("lab_migration_global")->_lm_dir_name($lab_title, $proposar_name, $university);
    $result = "INSERT INTO {lab_migration_proposal} 
    (uid, approver_uid, name_title, name, contact_ph, department, university, city, pincode, state, country, lab_title, approval_status, solution_status, solution_provider_uid, solution_display, creation_date, approval_date, solution_date, solution_provider_name_title, solution_provider_name, solution_provider_contact_ph, solution_provider_department, solution_provider_university, solution_provider_city, solution_provider_country, solution_provider_state, solution_provider_pincode, directory_name,problem_statement_file) VALUES
    (:uid, :approver_uid, :name_title, :name, :contact_ph, :department, :university, :city, :pincode, :state, :country,
      :lab_title, :approval_status, :solution_status, :solution_provider_uid, :solution_display, :creation_date, 
     :approval_date, :solution_date, :solution_provider_name_title, :solution_provider_name,
      :solution_provider_contact_ph, :solution_provider_department, :solution_provider_university, :solution_provider_city, :solution_provider_country, :solution_provider_state, :solution_provider_pincode, :directory_name, :problem_statement_file)";
    $args = [
      'uid' => $user->id(),
      'approver_uid' => 0,
      'name_title' => $v['name_title'],
      'name' => $v['name'],
      'contact_ph' => $v['contact_ph'],
      'department' => $v['department'],
      'university' => $v['university'],
      'city' => $v['city'],
      'pincode' => $v['pincode'],
      'state' => $v['all_state'],
      'country' => $v['country'],
      'lab_title' => $v['lab_title'],
      'approval_status' => 0,
      'solution_status' => $solution_status,
      'solution_provider_uid' => $solution_provider_uid,
      'solution_display' => $solution_display,
      'creation_date' => time(),
      'approval_date' => 0,
      'solution_date' => 0,
      'solution_provider_name_title' => $solution_provider_name_title,
      'solution_provider_name' => $solution_provider_name,
      'solution_provider_contact_ph' => $solution_provider_contact_ph,
      'solution_provider_department' => $solution_provider_department,
      'solution_provider_university' => $solution_provider_university,
      'solution_provider_city' => $solution_provider_city,
      'solution_provider_country' => $solution_provider_country,
      'solution_provider_state' => $solution_provider_state,
      'solution_provider_pincode' => $solution_provider_pincode,
      'directory_name' => $directory_name,
      'problem_statement_file' => "",
    ];
    $proposal_id = \Drupal::database()->query($result, $args, [
      'return' => Database::RETURN_INSERT_ID
      ]);
    $dest_path = $directory_name . '/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }
    /* uploading files */
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
      if ($file_name) {
        /* checking file type */
        //$file_type = 'S';
        if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
          \Drupal::messenger()->addmessage(t("Error uploading file. File !filename already exists.", [
            '!filename' => $_FILES['files']['name'][$file_form_name]
            ]), 'error');
          //unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
        } //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
            /* uploading file */
        if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
          $query = "UPDATE lab_migration_proposal SET problem_statement_file = :problem_statement_file WHERE id = :id";
          $args = [
            ":problem_statement_file" => $_FILES['files']['name'][$file_form_name],
            ":id" => $proposal_id,
          ];

          $updateresult = \Drupal::database()->query($query, $args);
          \Drupal::messenger()->addmessage($file_name . ' uploaded successfully.', 'status');
        } //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
        else {
          \Drupal::messenger()->addmessage('Error uploading file : ' . $dest_path . $file_name, 'error');
        }
      } //$file_name
    }
    if (!$proposal_id) {
      \Drupal::messenger()->addmessage(t('Error receiving your proposal. Please try again.'), 'error');
      return;
    }
    /* proposal id */
    //$proposal_id = db_last_insert_id('lab_migration_proposal', 'id');
    /* adding experiments */
    $number = 1;
    for ($counter = 1; $counter <= 15; $counter++) {
      $experiment_field_name = 'lab_experiment-' . $counter;
      $experiment_description = 'lab_experiment_description-' . $counter;
      if (strlen(trim($form_state->getValue([$experiment_field_name]))) >= 1) {
        //$query = "INSERT INTO {lab_migration_experiment} (proposal_id, directory_name, number, title,description) VALUES (:proposal_id, :directory_name, :number, :experiment_field_name,:description)";
        $query = "INSERT INTO {lab_migration_experiment} (proposal_id, number, title,description) VALUES (:proposal_id, :number, :experiment_field_name,:description)";
        $args = [
          ":proposal_id" => $proposal_id,
          // ":directory_name" => $directory_name,
                ":number" => $number,
          ":experiment_field_name" => trim($form_state->getValue([$experiment_field_name])),
          ":description" => trim($form_state->getValue([$experiment_description])),
        ];
        $result = \Drupal::database()->query($query, $args);
        if (!$result) {
          \Drupal::messenger()->addmessage(t('Could not insert Title of the Experiment : ') . trim($form_state->getValue([$experiment_field_name])), 'error');
        }
        else {
          $number++;
        }
      }
    }
    /* sending email */
    $email_to = $user->getEmail();
    // $from = $config->get('lab_migration_from_email', '');
    // $bcc = $config->get('lab_migration_emails', '');
    // $cc = $config->get('lab_migration_cc_emails', '');
    // $param['proposal_received']['proposal_id'] = $proposal_id;
    // $param['proposal_received']['user_id'] = $user->id();
    // $param['proposal_received']['headers'] = [
    //   'From' => $from,
    //   'MIME-Version' => '1.0',
    //   'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
    //   'Content-Transfer-Encoding' => '8Bit',
    //   'X-Mailer' => 'Drupal',
    //   'Cc' => $cc,
    //   'Bcc' => $bcc,
    // ];
    // if (!drupal_mail('lab_migration', 'proposal_received', $email_to, user_preferred_language($user), $param, $from, TRUE)) {
    //   \Drupal::messenger()->addmessage('Error sending email message.', 'error');
    // }
    \Drupal::messenger()->addmessage($this->t('We have received you Lab migration proposal. We will get back to you soon.'));
    // $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
    // $response->send();

    $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
return $response;
  }

}
