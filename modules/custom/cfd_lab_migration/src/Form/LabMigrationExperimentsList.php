<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationExperimentsList.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\user\Entity\User;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Database\Database;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RendererInterface;

class LabMigrationExperimentsList extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_experiments_list';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $options_first = $this->_list_of_labs();
    //$options_two = $this->_ajax_get_experiments_list($lab_default_value);
    $select_two = !$form_state->getValue(['lab_experiment_list']) ? $form_state->getValue(['lab_experiment_list']) : key($options_two);
    // $url_lab_id = (int) arg(2);
    $route_match = \Drupal::routeMatch();
    $url_lab_id = (int) $route_match->getParameter('experiment_id');
    if (!$url_lab_id) {
      $selected = !$form_state->getValue(['lab']) ? $form_state->getValue(['lab']) : key($options_first);
    }
    elseif ($url_lab_id == '') {
      $selected = 0;
    }
    else {
      $selected = $url_lab_id;
      ;
    }
    $form = [];
    $form['lab'] = [
      '#type' => 'select',
      '#title' => t('Title of the lab'),
      '#options' => $this->_list_of_labs(),
      '#default_value' => $selected,
      '#ajax' => [
          'callback' => '::ajax_experiment_list_callback',
          'wrapper' => 'ajax_selected_lab'
      ]
      ];
  $form['download_lab_wrapper'] = [
    '#type' => 'container',
    '#attributes' => ['id' => 'ajax_selected_lab'],
  ];
      $lab_default_value = $form_state->getValue('lab') ?: $url_lab_id;
//var_dump($lab_default_value);die;
          
    $form['download_lab_wrapper']['selected_lab'] = [
        '#type' => 'item',
        // '#markup' => '<div id="ajax_selected_lab"></div>',
        '#markup' => 
                Link::fromTextAndUrl(
                  $this->t('Download Lab Solutions'), 
                  Url::fromUri('internal:/lab-migration/download/lab/' . $lab_default_value)
                )->toString()
      ];
     
      $query = \Drupal::database()->select('lab_migration_proposal', 'p');
      $query->fields('p');
      $query->condition('id', $lab_default_value);
      $lab_q = $query->execute();
      $lab_data = $lab_q->fetchObject();
      // Query solution files
      $query1 = \Drupal::database()->select('lab_migration_experiment', 'e');
      $query1->fields('e');
      $query1->condition('proposal_id', $lab_default_value);
      $experiment_list_q = $query1->execute();
      if ($experiment_list_q) {
        $experiment_files_rows = [];
        $solution_files_rows=[];

        while ($experiment_list_data = $experiment_list_q->fetchObject()) {
 
//var_dump($solution_list_data);die;
          // Create file download link
          $items = [
           $lab_data->lab_title,
           $experiment_list_data->title,

       Link::fromTextAndUrl('View Solution', Url::fromUri('internal:/lab-migration/lab-migration-run/' . $lab_data->id . '/' . $experiment_list_data->id))->toString(),
            
          ];
        }
      }
     
      array_push($solution_files_rows, $items);
      //var_dump($solution_rows);die;
        $form['download_lab_wrapper']['experiment_files'] = [
          '#type' => 'item',
          '#title' => t('Title of the experiment'),
        ];
        $solution_files_header = ['Title of the Lab', 'Experiment','Actions']; // Table headers

        $table = [
          '#type' => 'table',
          '#header' => $solution_files_header,
          '#rows' => $solution_files_rows,
        
        '#attributes' => [
          'style' => 'width: 100%;',
          
        ],
      ];
            // Add the table to the fieldset
$form['download_lab_wrapper']['experiment_files']['table'] = $table;
      

   
      
    // var_dump($experiment_list_data);die;
     

  
    
    
    /*
    $form['message'] = array(
    '#type' => 'textarea',
    '#title' => t('If Dis-Approved please specify reason for Dis-Approval'),
    '#prefix' => '<div id= "message_submit">',   
    '#states' => array('invisible' => array(':input[name="lab"]' => array('value' => 0,),),), 
    
    );
    
    $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),      
    '#suffix' => '</div>',
    '#states' => array('invisible' => array(':input[name="lab"]' => array('value' => 0,),),),
    
    );*/
    return $form;
  }
  public function ajax_experiment_list_callback(array &$form, FormStateInterface $form_state) {
    return $form['download_lab_wrapper']; 
  }
    
    public function _list_of_labs() {
    $lab_titles = [];
  
    // Use the Database API to create a query.
    $query = \Drupal::database()->select('lab_migration_proposal', 'lmp');
    $query->fields('lmp', ['id', 'lab_title', 'name_title', 'name']);
    // $query->condition('id', $selected);
    $query->condition('solution_display', 1);
    $query->condition('approval_status', 3);
    $query->orderBy('lab_title', 'ASC');
  
    // Execute the query.
    $lab_titles_q = $query->execute();
  
    // Fetch the results and build the $lab_titles array.
    foreach ($lab_titles_q as $lab_titles_data) {
      $lab_titles[$lab_titles_data->id] = $lab_titles_data->lab_title . ' (Proposed by ' . $lab_titles_data->name_title . ' ' . $lab_titles_data->name . ')';
    }
  
    return $lab_titles;
  }
  // function _ajax_get_experiments_list($lab_default_value = '') {
  //   $experiments = array(
  //     '0' => 'Please select...'
  //   );
  
  //   // Database query to get lab titles
  //   $connection = \Drupal::database();
  //   $query = $connection->select('lab_migration_proposal', 'lmp')
  //     ->fields('lmp')
  //     ->condition('id', $lab_default_value)
  //     ->orderBy('lab_title', 'ASC');
  //   $lab_titles_data = $query->execute()->fetchObject();
  
  //   // Database query to get experiments
  //   $query = $connection->select('lab_migration_experiment', 'lme')
  //     ->fields('lme')
  //     ->condition('proposal_id', $lab_default_value)
  //     ->orderBy('number', 'ASC');
  //   $experiments_q = $query->execute();
  
  //   // Load solution provider user data
  //   $solution_provider_user_name = '';
  //   $user_data = User::load($lab_titles_data->solution_provider_uid);
  //   if ($user_data) {
  //     $solution_provider_user_name = $user_data->getUsername();
  //   } else {
  //     $solution_provider_user_name = $lab_titles_data->name;
  //   }
  
  //   // Prepare rows for the table
  //   $pending_solution_rows = [];
  //   while ($experiments_data = $experiments_q->fetchObject()) {
  //     //var_dump($experiments_data);die;
  //     $pending_solution_rows[] = array(
  //       $lab_titles_data->lab_title,
  //       $experiments_data->title,
  //       \Drupal::l('View Solution', Url::fromRoute('lab_migration.lab_migration_run', 
  //       [ 'lab_id' => $lab_id,
  //       'experiment_id' => $experiments_data->id]))
  //     );
  //   }
  // // 'lab_id' => $lab_titles_data->id, 'experiment_id' => $experiments_data->id
  //   // Define the table header
  //   $header = array(
  //     'Title of the Lab',
  //     'Experiment',
  //     'Actions'
  //   );
  
  //   // Render the table using Drupal's theme system
  //   $output = [
  //     '#theme' => 'table',
  //     '#header' => $header,
  //     '#rows' => $pending_solution_rowsrows,
  //   ];
  
  //   return $output;
  // }
  
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
    $lab_details_markup = '<span style="color: rgb(128, 0, 0);"><strong>About the Lab</strong></span>' .
      '<ul>' .
      '<li><strong>Proposer Name:</strong> ' . $lab_details->name_title . ' ' . $lab_details->name . '</li>' .
      '<li><strong>Title of the Lab:</strong> ' . $lab_details->lab_title . '</li>' .
      '<li><strong>Department:</strong> ' . $lab_details->department . '</li>' .
      '<li><strong>University:</strong> ' . $lab_details->university . '</li>' .
      '<li><strong>Category:</strong> ' . $lab_details->category . '</li>' .
      '</ul>';
  
    // Combine lab details and solution provider into a table.
    $markup = '<table><tr>' .
      '<td>' . $lab_details_markup . '</td>' .
      '<td>' . $solution_provider . '</td>' .
      '</tr></table>';
  
    return $markup;
  }
  
 
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  }
}
?>
