<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationUploadCodeForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;


class LabMigrationUploadCodeForm extends FormBase {



  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_upload_code_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $config = \Drupal::config('lab_migration.settings'); // Load the configuration
    $user = \Drupal::currentUser();

    $proposal_data = \Drupal::service("lab_migration_global")->lab_migration_get_proposal();
    if (!$proposal_data) {
      // RedirectResponse('');
      $response = new RedirectResponse(Url::fromRoute('lab_migration.proposal_form')->toString());
$response->send();
      return;
      // var_dump($response);die;
    }

    /* add javascript for dependency selection effects */
    $dep_selection_js = "(function ($) {
  //alert('ok');
    $('#edit-existing-depfile-dep-lab-title').change(function() {
      var dep_selected = '';   
 
      /* showing and hiding relevant files */
     $('.form-checkboxes .option').hide();
      $('.form-checkboxes .option').each(function(index) {
        var activeClass = $('#edit-existing-depfile-dep-lab-title').va\Drupal\Core\Link;
        consloe.log(activeClass);
        if ($(this).children().hasClass(activeClass)) {
          $(this).show();
        }
        if ($(this).children().attr('checked') == true) {
          dep_selected += $(this).children().next().text() + '<br />';
        }
      });
      /* showing list of already existing dependencies */
      $('#existing_depfile_selected').html(dep_selected);
    });

    $('.form-checkboxes .option').change(function() {
      $('#edit-existing-depfile-dep-lab-title').trigger('change');
    });
    $('#edit-existing-depfile-dep-lab-title').trigger('change');
  }(jQuery));";
    #attached($dep_selection_js, 'inline', 'header');

    $form['#attributes'] = ['enctype' => "multipart/form-data"];

    $form['lab_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->lab_title,
      '#title' => t('Title of the Lab'),
    ];
    $form['name'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->name_title . ' ' . $proposal_data->name,
      '#title' => t('Proposer Name'),
    ];

    /* get experiment list */
    $experiment_rows = [];
    //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d ORDER BY id ASC", $proposal_data->id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('proposal_id', $proposal_data->id);
    $query->orderBy('id', 'ASC');
    $experiment_q = $query->execute();
    while ($experiment_data = $experiment_q->fetchObject()) {
      $experiment_rows[$experiment_data->id] = $experiment_data->number . '. ' . $experiment_data->title;
    }
    
    $form['experiment'] = [
      '#type' => 'select',
      '#title' => t('Title of the Experiment'),
      '#options' => $experiment_rows,
      '#multiple' => FALSE,
      '#size' => 1,
      '#required' => TRUE,
    ];
// var_dump($form);die;
    $form['code_number'] = [
      '#type' => 'textfield',
      '#title' => t('Code No'),
      '#size' => 5,
      '#maxlength' => 10,
      '#description' => t(""),
      '#required' => TRUE,
    ];
    $form['code_caption'] = [
      '#type' => 'textfield',
      '#title' => t('Caption'),
      '#size' => 40,
      '#maxlength' => 255,
      '#description' => t(''),
      '#required' => TRUE,
    ];
    $form['os_used'] = [
      '#type' => 'select',
      '#title' => t('Operating System used'),
      '#options' => [
        'Linux' => 'Linux',
        'Windows' => 'Windows',
        'Mac' => 'Mac',
      ],
      '#required' => TRUE,
    ];
    
     /*$form['dwsim_version'] = array(
    '#type' => 'select',
    '#title' => t('DWSIM version used'),
    '#options' => _lm_list_of_software_version(),
    '#required' => TRUE,
  );*/
  $form['version'] = array(
    '#type' => 'hidden',        
    '#value' => 'Not available',
    
);

    $form['toolbox_used'] = [
      '#type' => 'hidden',
      '#title' => t('Toolbox used (If any)'),
      '#default_value' => 'none',
    ];
    $form['code_warning'] = [
      '#type' => 'item',
      '#title' => t('Upload all the OpenFOAM project files in .zip format'),
      '#prefix' => '<div style="color:red">',
      '#suffix' => '</div>',
    ];
    $form['sourcefile'] = [
      '#type' => 'fieldset',
      '#title' => t('Main or Source Files'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $extensions = $config->get('lab_migration_source_extensions') ?? '';
    $form['sourcefile']['sourcefile1'] = [
      '#type' => 'file',
      '#title' => t('Upload main or source file'),
      '#size' => 48,
      '#description' => t('Only alphabets and numbers are allowed as a valid filename.') . '<br />' . t('Allowed file extensions: ') .$extensions
,
    ];

    /*$form['result'] = array(
    '#type' => 'fieldset',
    '#title' => t('Result Files'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  );
  $form['result']['result1'] = array(
      '#type' => 'file',
      '#title' => t('Upload result file'),
      '#size' => 48,
      '#description' => t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' .
      t('Allowed file extensions: ') . $config->get('lab_migration_result_extensions', ''),
  );
  $form['result']['result2'] = array(
      '#type' => 'file',
      '#title' => t('Upload result file'),
      '#size' => 48,
      '#description' => t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' .
      t('Allowed file extensions: ') . $config->get('lab_migration_result_extensions', ''),
  );

  $form['xcos'] = array(
    '#type' => 'fieldset',
    '#title' => t('XCOS Files'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  );
  $form['xcos']['xcos1'] = array(
      '#type' => 'file',
      '#title' => t('Upload xcos file'),
      '#size' => 48,
      '#description' => t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' .
      t('Allowed file extensions: ') . $config->get('lab_migration_xcos_extensions', ''),
  );
  $form['xcos']['xcos2'] = array(
      '#type' => 'file',
      '#title' => t('Upload xcos file'),
      '#size' => 48,
      '#description' => t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' .
      t('Allowed file extensions: ') . $config->get('lab_migration_xcos_extensions', ''),
  );
*/

$form['result'] = [
  '#type' => 'fieldset',
  '#title' => $this->t('Result Files'),
  '#collapsible' => FALSE,
  '#collapsed' => FALSE,
];
$extensions = $config->get('lab_migration_result_pdf_extensions') ?? '';
//var_dump($extensions);die;
$form['result']['result1'] = [
  '#type' => 'file',
  '#title' => $this->t('Upload result file. To view the template for result submission please click <a href="https://cfd.fossee.in/sites/default/files/Template_For_Result.doc" target="_blank">here</a>.'),
  '#size' => 48,
  '#description' => $this->t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' .
                    $this->t('Allowed file extensions: ') . \Drupal::config('lab_migration.settings')->get('lab_migration_result_pdf_extensions'),
];
$extensions = $config->get('lab_migration_result_extensions') ?? '';
$form['result']['result2'] = [
  '#type' => 'file',
  '#title' => $this->t('Upload result file'),
  '#size' => 48,
  '#description' => $this->t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' .
                    $this->t('Allowed file extensions: ') . $extensions,
];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];


$form['cancel_link'] = [
  '#type' => 'markup',
  '#markup' => Link::fromTextAndUrl(t('Cancel'), Url::fromRoute('lab_migration.list_experiments'))->toString(),
];
    return $form;
    
  }
  private function lab_migration_check_code_number($code_number) {
    return preg_match('/^[0-9]+$/', $code_number); // Example regex for numeric-only check
  }
  private function lab_migration_check_name($caption) {
    // Allows only alphabets, numbers, and spaces
    return preg_match('/^[a-zA-Z0-9 ]+$/', $caption);
  }
  public function validateForm(array &$form , FormStateInterface $form_state) {
    if (!$this->lab_migration_check_code_number($form_state->getValue(['code_number']))) {
      $form_state->setErrorByName('code_number', t('Invalid Code Number. Code Number can contain only numbers.'));
    }

    if (!$this->lab_migration_check_name($form_state->getValue(['code_caption']))) {
      $form_state->setErrorByName('code_caption', t('Caption can contain only alphabets, numbers and spaces.'));
    }

    

    if (isset($_FILES['files'])) {
      /* check if atleast one source or result file is uploaded */
      if (!($_FILES['files']['name']['sourcefile1'] )) {
        $form_state->setErrorByName('sourcefile1', t('Please upload atleast one main or source file.'));
      }

      /* check for valid filename extensions */
      foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
        if ($file_name) {
          /* checking file type */
          if (strstr($file_form_name, 'source')) {
            $file_type = 'S';
          }
          else {
            if (strstr($file_form_name, 'result1')) {
              $file_type = 'P';
            }
            else {
              if (strstr($file_form_name, 'result2')) {
                $file_type = 'R';
              }
              else {
                $file_type = 'U';
              }
            }
          }
          $config = $this->config('lab_migration.settings');
          $allowed_extensions_str = '';
          switch ($file_type) {
            case 'S':
              $allowed_extensions_str = $config->get('lab_migration_source_extensions') ?? '';
              break;
            case 'P':
              $allowed_extensions_str = $config->get('lab_migration_result_pdf_extensions') ?? '';
              break;
            case 'R':
              $allowed_extensions_str = $config->get('lab_migration_result_extensions' ) ?? '';
              break;
          }
          $allowed_extensions = explode(',', $allowed_extensions_str);
          $tmp_ext = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
          $temp_extension = end($tmp_ext);
          if (!in_array($temp_extension, $allowed_extensions)) {
            $form_state->setErrorByName($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
          }
          if ($_FILES['files']['size'][$file_form_name] <= 0) {
            $form_state->setErrorByName($file_form_name, t('File size cannot be zero.'));
          }
          $config = $this->config('lab_migration.settings');

          /* check if valid file name */
          // if (!lab_migration_check_valid_filename($_FILES['files']['name'][$file_form_name])) {
          //   $form_state->setErrorByName($file_form_name, t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
          // }
        }
      }
    

    /* add javascript dependency selection effects */
    $dep_selection_js = " (function ($) {
 
    $('#edit-existing-depfile-dep-lab-title').change(function() {
    console.log('ok');
      var dep_selected = ''; 
      /* showing and hiding relevant files */
    $('.form-checkboxes .option').hide();
      
      $('.form-checkboxes .option').each(function(index) {
        var activeClass = $('#edit-existing-depfile-dep-lab-title').va\Drupal\Core\Link;
        if ($(this).children().hasClass(activeClass)) {
          $(this).show();
        }
        if ($(this).children().attr('checked') == true) {
          dep_selected += $(this).children().next().text() + '<br />';
        }
      });
      /* showing list of already existing dependencies */
     $('#existing_depfile_selected').html(dep_selected);
    });

    $('.form-checkboxes .option').change(function() {
      $('#edit-existing-depfile-dep-lab-title').trigger('change');
    });
    $('#edit-existing-depfile-dep-lab-title').trigger('change');
  })(jQuery);";
    #attached($dep_selection_js, 'inline', 'header');

    // #attached('jQuery(document).ready(function () { alert("Hello!"); });', 'inline');
    // drupal_static_reset('#attached') ;
  }
}

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();

    $root_path =  \Drupal::service("lab_migration_global")->lab_migration_path();

    $proposal_data = \Drupal::service("lab_migration_global")->lab_migration_get_proposal();
    if (!$proposal_data) {
      // RedirectResponse('');
      return new RedirectResponse(Url::fromRoute('lab_migration.proposal_form')->toString());
      return;
    }

    $proposal_id = $proposal_data->id;
    $proposal_drectory = $proposal_data->directory_name;

    /************************ check experiment details ************************/
    $experiment_id = (int) $form_state->getValue(['experiment']);
    //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE id = %d AND proposal_id = %d LIMIT 1", $experiment_id, $proposal_id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('id', $experiment_id);
    $query->condition('proposal_id', $proposal_id);
    $query->range(0, 1);
    $experiment_q = $query->execute();
    $experiment_data = $experiment_q->fetchObject();
    if (!$experiment_data) {
      \Drupal::messenger()->addmessage("Invalid experiment seleted", 'error');
      // RedirectResponse('lab-migration/code');
      $response = new RedirectResponse(Url::fromRoute('lab_migration.list_experiments')->toString());
$response->send();
    }

    /* create proposal folder if not present */
    $dest_path = $proposal_drectory . '/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }

    /*  get solution details - dont allow if already solution present */
    $code_number = $experiment_data->number . '.' . $form_state->getValue(['code_number']);

    //$cur_solution_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution} WHERE experiment_id = %d AND code_number = '%s'", $experiment_id, $experiment_data->number . '.' . $form_state['values']['code_number']);
    $query = \Drupal::database()->select('lab_migration_solution');
    $query->fields('lab_migration_solution');
    $query->condition('experiment_id', $experiment_id);
    $query->condition('code_number', $code_number);
    $cur_solution_q = $query->execute();
    if ($cur_solution_d = $cur_solution_q->fetchObject()) {
      if ($cur_solution_d->approval_status == 1) {
        \Drupal::messenger()->addmessage(t("Solution already approved. Cannot overwrite it."), 'error');
        // RedirectResponse('lab-migration/code');
        return;
      }
      else {
        if ($cur_solution_d->approval_status == 0) {
          \Drupal::messenger()->addmessage(t("Solution is under pending review. Delete the solution and reupload it."), 'error');
          // RedirectResponse('lab-migration/code');
          return new RedirectResponse(Url::fromUserInput('/lab-migration/code')->toString());
          return;
        }
        else {
          \Drupal::messenger()->addmessage(t("Error uploading solution. Please contact administrator."), 'error');
          RedirectResponse('lab-migration/code');
          return;
        }
      }
    }

    /* creating experiment directories */
    $dest_path .= 'EXP' . $experiment_data->number . '/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }

    /* creating code directories */
    $dest_path .= 'CODE' . $experiment_data->number . '.' . $form_state->getValue(['code_number']) . '/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }
    /* creating file path */
    $file_path = 'EXP' . $experiment_data->number . '/' . 'CODE' . $experiment_data->number . '.' . $form_state->getValue(['code_number']) . '/';
    /* creating solution database entry */
    $query = "INSERT INTO {lab_migration_solution} (experiment_id, approver_uid, code_number, caption, approval_date, approval_status, timestamp, os_used,version, toolbox_used) VALUES (:experiment_id, :approver_uid, :code_number, :caption, :approval_date, :approval_status, :timestamp, :os_used, :version,:toolbox_used)";
    $args = [
      ":experiment_id" => $experiment_id,
      ":approver_uid" => 0,
      ":code_number" => $experiment_data->number . '.' . $form_state->getValue(['code_number']),
      ":caption" => $form_state->getValue(['code_caption']),
      ":approval_date" => 0,
      ":approval_status" => 0,
      ":timestamp" => time(),
      ":os_used" => $form_state->getValue(['os_used']),
      ":version" => $form_state->getValue(['version']),
      ":toolbox_used" => $form_state->getValue(['toolbox_used']),
    
    ];
    $solution_id = \Drupal::database()->query($query, $args, [
      'return' => Database::RETURN_INSERT_ID
      ]);
    // var_dump('solution id= '.$solution_id.  '&&& dep file = '.array_filter($form_state['values']['existing_depfile']['dep_experiment_files']));

    //die;
  /* linking existing dependencies */
    /* foreach ($form_state['values']['existing_depfile']['dep_experiment_files'] as $row)
  {
    if ($row > 0)
    {*/
    /* insterting into database */
    /* $query = "INSERT INTO {lab_migration_solution_dependency} (solution_id, dependency_id)
        VALUES (:solution_id, :dependency_id)";
      $args = array(
                    ":solution_id" => $solution_id,
                    ":dependency_id" => $row
               );  
      \Drupal::database()->query( $query,$args);
    }
  }*/

    /* uploading files */
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
      if ($file_name) {
        /* checking file type */
        if (strstr($file_form_name, 'source')) {
          $file_type = 'S';
        }
        else {
          if (strstr($file_form_name, 'result')) {
            $file_type = 'R';
          }
          else {
            if (strstr($file_form_name, 'xcos')) {
              $file_type = 'X';
            }
            else {
              $file_type = 'U';
            }
          }
        }

        if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
          \Drupal::database()->addmessage(t("Error uploading file. File !filename already exists.", [
            '!filename' => $_FILES['files']['name'][$file_form_name]
            ]), 'error');
          return;
        }

        /* uploading file */
        if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
          /* for uploaded files making an entry in the database */
          $query = "INSERT INTO {lab_migration_solution_files} (solution_id, filename, filepath, filemime, filesize, filetype, timestamp)
          VALUES (:solution_id, :filename, :filepath, :filemime, :filesize, :filetype, :timestamp)";
          $args = [
            ":solution_id" => $solution_id,
            ":filename" => $_FILES['files']['name'][$file_form_name],
            ":filepath" => $file_path . $_FILES['files']['name'][$file_form_name],
            ":filemime" => mime_content_type($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]),
            ":filesize" => $_FILES['files']['size'][$file_form_name],
            ":filetype" => $file_type,
            ":timestamp" => time(),
          ];
          \Drupal::database()->query($query, $args);
          \Drupal::messenger()->addmessage($file_name . ' uploaded successfully.', 'status');
        }
        else {
          \Drupal::messenger()->addmessage('Error uploading file: ' . $dest_path . $file_name, 'error');
        }
      }
    }
    \Drupal::messenger()->addmessage('Solution uploaded successfully.', 'status');

    // /* sending email */
    // $email_to = $user->mail;
    // $from = $this->configFactory->get('lab_migration.settings')->get('lab_migration_from_email');
    // // $from = $config->get('lab_migration_from_email', '');
    // // $bcc = $config->get('lab_migration_emails', '');
    // $bcc = $this->configFactory->get('lab_migration.settings')->get('lab_migration_email');
    // $cc = $this->configFactory->get('lab_migration.settings')->get('lab_migration_cc_email');

    // // $cc = $config->get('lab_migration_cc_emails', '');
    // $param['solution_uploaded']['solution_id'] = $solution_id;
    // $param['solution_uploaded']['user_id'] = $user->uid;
    // $param['solution_uploaded']['headers'] = [
    //   'From' => $from,
    //   'MIME-Version' => '1.0',
    //   'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
    //   'Content-Transfer-Encoding' => '8Bit',
    //   'X-Mailer' => 'Drupal',
    //   'Cc' => $cc,
    //   'Bcc' => $bcc,
    // ];

    // if (!drupal_Email('lab_migration', 'solution_uploaded', $email_to, language_default(), $param, $from, TRUE)) {
    //   \Drupal::database()->addmessage('Error sending email message.', 'error');
    // }
    $response = new RedirectResponse(Url::fromRoute('lab_migration.upload_code_form')->toString());
   // Send the redirect response
      $response->send();
      }
    // RedirectResponse('lab-migration/code/upload');
  }


?>