<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationRunForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Database\Database;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RendererInterface;

class LabMigrationRunForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_run_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $options_two = $this->_ajax_get_experiment_list();
    // $select_two = !$form_state->getValue(['lab_experiment_list']) ? $form_state->getValue(['lab_experiment_list']) : key($options_two);
    // $url_lab_id = (int) arg(2);
    $route_match = \Drupal::routeMatch();
    $url_lab_id = (int) $route_match->getParameter('lab_id');
    // $url_experiment_id = (int) arg(3);
    
    $route_match = \Drupal::routeMatch();
    $url_experiment_id = (int) $route_match->getParameter('experiment_id');

    $options_first = $this->_list_of_labs($url_lab_id);
    if (!$url_lab_id) {
      $selected = !$form_state->getValue(['lab']) ? $form_state->getValue(['lab']) : key($options_first);
    }
    elseif ($url_lab_id == '') {
      $selected = 0;
    }
    else {
      $selected = $url_lab_id;
    }
    if (!$url_experiment_id) {
      $selected_experiment = !$form_state->getValue(['lab_experiment_list']) ? $form_state->getValue(['lab_experiment_list']) : key($options_two);
    }
    elseif ($url_experiment_id == '') {
      $selected_experiment = 0;
    }
    else {
      $selected_experiment = $url_experiment_id;
    }
    $form = [];
    $form['lab'] = [
        '#type' => 'select',
        '#title' => t('Title of the lab'),
        '#options' => $this->_list_of_labs($selected),
        '#default_value' => $selected,
    ];
        
   $lab_default_value = $form_state->getValue('lab') ?: $url_lab_id;

   $experiment_default_value = $form_state->getValue('experiment') ?:$url_experiment_id;

   $form['selected_lab'] = [
    '#type' => 'item',
    '#markup' => 
    Link::fromTextAndUrl(
      $this->t('Download Lab Solutions'), 
      Url::fromUri('internal:/lab-migration/download/lab/' . $lab_default_value)
    )->toString()
  ];
// download experiment
$form['download_lab_wrapper']['lab_experiment_list'] = array(
  '#type' => 'select',
  '#title' => t('Title of the experiment'),
  '#options' => $this->_ajax_get_experiment_list($lab_default_value),
  // '#default_value' => isset($form_state['values']['lab_experiment_list']) ? $form_state['values']['lab_experiment_list'] : '',
  '#ajax' => [
      'callback' => '::ajax_solution_list_callback',
      'wrapper'  => 'ajax_download_experiments'
    ],
  '#prefix' => '<div id="ajax_selected_experiment">',
  '#suffix' => '</div>',
  '#states' => array(
      'invisible' => array(
          ':input[name="lab"]' => array(
              'value' => 0
          )
      )
  )
);
$form['download_experiment_wrapper'] = [
'#type' => 'container',
'#attributes' => ['id' => 'ajax_download_experiments'],
];
$form['download_experiment_wrapper']['download_experiment'] = [
  '#type' => 'item',
  '#markup' => Link::fromTextAndUrl('Download Experiment', Url::fromUri('internal:/lab-migration/download/experiment/' . $form_state->getValue('lab_experiment_list')))->toString()
];

//download solution
$form['download_experiment_wrapper']['solution_list'] = [
  '#type' => 'select',
    '#title' => t('Title of the solution'),
    '#options' => $this->_ajax_get_solution_list($form_state->getValue('lab_experiment_list')),
    '#ajax' => [
        'callback' => '::ajax_solution_files_callback',
        'wrapper'  => 'ajax_download_solution_file'
      ],
];
//for download solution

$form['download_solution_wrapper'] = [
  '#type' => 'container',
  '#attributes' => ['id' => 'ajax_download_solution_file'],
];
$form['download_solution_wrapper']['download_solution'] = [
  '#type' => 'item',
  '#markup' => Link::fromTextAndUrl('Download Solution', Url::fromUri('internal:/lab-migration/download/solution/' . $form_state->getValue('solution_list')))->toString()
];

$query = \Drupal::database()->select('lab_migration_solution_files', 's');
      $query->fields('s');
      $query->condition('solution_id', $form_state->getValue('solution_list'));
      $solution_list_q = $query->execute();
      if ($solution_list_q) {
        $solution_files_rows = [];
        $solution_files_rows = [];

        while ($solution_list_data = $solution_list_q->fetchObject()) {
            $solution_file_type = '';
            switch ($solution_list_data->filetype) {
                case 'S':
                    $solution_file_type = 'Source or Main file';
                    break;
                case 'R':
                    $solution_file_type = 'Result file';
                    break;
                case 'X':
                    $solution_file_type = 'xcos file';
                    break;
                default:
                    $solution_file_type = 'Unknown';
                    break;
            }
        
            $Link = Link::fromTextAndUrl(
                $solution_list_data->filename,
                Url::fromUri('internal:/lab-migration/download/file/' . $solution_list_data->id)
            )->toString();
        
            $solution_files_rows[] = [$Link, $solution_file_type]; // Add row
        }
         
          
// Create file download link
$Link = Link::fromTextAndUrl($solution_list_data->filename, Url::fromUri('internal:/lab-migration/download/file/' . $solution_list_data->id))->toString();
$items = [
         
  // Link::fromTextAndUrl($solution_list_data->filename, Url::fromUri('internal:/lab-migration/download/file/' . $solution_list_data->id))->toString(),
  $Link,
 $solution_file_type,
];
}

// array_push($solution_files_rows, $items);
//var_dump($solution_rows);die;
$form['download_solution_wrapper']['solution_files'] = [
'#type' => 'fieldset',
'#title' => t('List of solution files'),
];
$solution_files_header = ['Filename', 'Type']; // Table headers

$table = [
'#type' => 'table',
'#header' => $solution_files_header,
'#rows' => $solution_files_rows,

'#attributes' => [
'style' => 'width: 100%;',

],
];
 // Add the table to the fieldset
$form['download_solution_wrapper']['solution_files']['table'] = $table;

$form['lab_details'] = array(
            '#type' => 'item',
            '#markup' => '<div id="ajax_lab_details">' . $this->_lab_details($lab_default_value) . '</div>'
        );
        $form['back_to_completed_labs'] = array(
          '#type' => 'item',
          '#markup' => Link::fromTextAndUrl('Back to Completed Labs', Url::fromRoute('lab_migration.completed_labs_all'))->toString(),
        );

    return $form;
  }
  

  public function ajax_experiment_list_callback(array &$form, FormStateInterface $form_state) {
    return $form['download_lab_wrapper'];

  }
  public function ajax_solution_list_callback(array &$form, FormStateInterface $form_state) {
    return $form['download_experiment_wrapper'];
  }
  public function ajax_solution_files_callback(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

    return $form['download_solution_wrapper'];
  }

  public function _ajax_get_experiment_list($lab_default_value = '')
  {
    $experiments = array(
        '0' => 'Please select...'
    );
    //$experiments_q = db_query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d ORDER BY number ASC", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('proposal_id', $lab_default_value);
    $query->orderBy('number', 'ASC');
    $experiments_q = $query->execute();
    while ($experiments_data = $experiments_q->fetchObject())
      {
        $experiments[$experiments_data->id] = $experiments_data->number . '. ' . $experiments_data->title;
      }
    return $experiments;
  }
  
public function _list_of_labs($selected)
  {
    
    //$lab_titles_q = db_query("SELECT * FROM {lab_migration_proposal} WHERE solution_display = 1 ORDER BY lab_title ASC");
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $selected);
    $query->orderBy('lab_title', 'ASC');
    $lab_titles_q = $query->execute();
    while ($lab_titles_data = $lab_titles_q->fetchObject())
      {
        $lab_titles[$lab_titles_data->id] = $lab_titles_data->lab_title . ' (Proposed by ' . $lab_titles_data->name_title .' '.$lab_titles_data->name . ')';
      }
    return $lab_titles;
  }
  public function _lab_information($proposal_id) {
    // Create the database query.
    $query = \Drupal::database()->select('lab_migration_proposal', 'lmp');
    $query->fields('lmp');
    $query->condition('id', $proposal_id);
    $query->condition('approval_status', 3);
  
    // Execute the query and fetch the result.
    $lab_data = $query->execute()->fetchObject();
  
    // Return the data if found, otherwise return NULL.
    return $lab_data ?: NULL;
  }
  
  public function _ajax_get_solution_list($lab_experiment_list = '') {
    $solutions = [
      '0' => 'Please select...'
    ];
  
    // Use the Database API to create the query.
    $query = \Drupal::database()->select('lab_migration_solution', 'lms');
    $query->fields('lms', ['id', 'code_number', 'caption']);
    $query->condition('experiment_id', $lab_experiment_list);
  
    // Execute the query and fetch the results.
    $solutions_q = $query->execute();
    foreach ($solutions_q as $solutions_data) {
      $solutions[$solutions_data->id] = $solutions_data->code_number . ' (' . $solutions_data->caption . ')';
    }
  
    return $solutions;
  }
  function _lab_details($lab_default_value) {
    // Get lab details using a custom function.
    $lab_details = $this->_lab_information($lab_default_value);
  
    if ($lab_default_value == 0 || !$lab_details) {
      // Redirect to a specific route if lab details are missing.
      $url = Url::fromRoute('lab_migration.run_form');
      return \Drupal::service('redirect.destination')->set($url->toString());
    }
  
    // Solution provider information.
    if ($lab_details->solution_provider_uid > 0) {
      // Load the solution provider user entity.
      $user_solution_provider = User::load($lab_details->solution_provider_uid);
      if ($user_solution_provider) {
        $solution_provider = '<span style="color: rgb(128, 0, 0);"><strong>Solution Provider</strong></span>' .
          '<ul>' .
          '<li><strong>Solution Provider Name:</strong> ' . $lab_details->solution_provider_name_title . ' ' . $lab_details->solution_provider_name . '</li>' .
          '<li><strong>Department:</strong> ' . $lab_details->solution_provider_department . '</li>' .
          '<li><strong>University:</strong> ' . $lab_details->solution_provider_university . '</li>' .
          '</ul>';
      } else {
        $solution_provider = '<span style="color: rgb(128, 0, 0);"><strong>Solution Provider</strong></span>' .
          '<ul>' .
          '<li><strong>Solution Provider:</strong> (Open)</li>' .
          '</ul>';
      }
    } else {
      $solution_provider = '<span style="color: rgb(128, 0, 0);"><strong>Solution Provider</strong></span>' .
        '<ul>' .
        '<li><strong>Solution Provider:</strong> (Open)</li>' .
        '</ul>';
    }
  
    // Lab details markup.
  //   $lab_details_markup = '<table><td><tr><span style="color: rgb(128, 0, 0);"><strong>About the Lab</strong></span></tr></td></table>' .
  //     '<ul>' .
  //     '<li><strong>Proposer Name:</strong> ' . $lab_details->name_title . ' ' . $lab_details->name . '</li>' .
  //     '<li><strong>Title of the Lab:</strong> ' . $lab_details->lab_title . '</li>' .
  //     '<li><strong>Department:</strong> ' . $lab_details->department . '</li>' .
  //     '<li><strong>University:</strong> ' . $lab_details->university . '</li>' .
  //     '<li><strong>Category:</strong> ' . $lab_details->category . '</li>' .
  //     '</ul></td><td>';
  
  //   // Combine lab details and solution provider into a table.
  //   $markup = '<table><tr>' .
  //     '<td>' . $lab_details_markup . '</td>' .
  //     '<td>' . $solution_provider . '</td>' .
  //     '</tr></table>';
  // // =========
  //  return $markup;
  $form['lab_details']['#markup'] = '<table><tr><td><span style="color: rgb(128, 0, 0);"><strong>About the Lab</strong></span>' . '<ul>' . '<li><strong>Proposer Name:</strong> ' . $lab_details->name_title . ' ' . $lab_details->name . '</li>' . '<li><strong>Title of the Lab:</strong> ' . $lab_details->lab_title . '</li>' . '<li><strong>Department:</strong> ' . $lab_details->department . '</li>' . '<li><strong>University:</strong> ' . $lab_details->university . '</li>' . '<li><strong>Category:</strong> ' . $lab_details->category . '</li>' . '</ul></td><td>' . $solution_provider . '</td></tr></table>';
  $details = $form['lab_details']['#markup'];
  return $details;
  }
  public function bootstrap_table_format(array $headers, array $rows) {
    // Define the table header and rows.
    $table_header = [];
    foreach ($headers as $header) {
      $table_header[] = ['data' => $header, 'header' => TRUE];
    }
  
    // Define the table rows.
    $table_rows = [];
    foreach ($rows as $row) {
      $table_row = [];
      foreach ($row as $data) {
        $table_row[] = ['data' => $data];
      }
      $table_rows[] = $table_row;
    }
  
    // Create a table render array with Drupal's table theming.
    $table = [
      '#type' => 'table',
      '#header' => $table_header,
      '#rows' => $table_rows,
      '#attributes' => ['class' => ['table', 'table-bordered', 'table-hover']],
    ];
  
    // Render the table using Drupal's renderer.
    $renderer = \Drupal::service('renderer');
    return $renderer->render($table);
  }
  
  
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  }
}
?>