<?php
 
 namespace Drupal\cfd_research_migration\Services;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Drupal\user\Entity\User;


class CfdResearchMigrationGlobalFunction{


  function _df_list_of_states() {
      $states = [
          0 => '-Select-',
      ];
  
      // Get database connection
      $connection = Database::getConnection();
      $query = $connection->select('list_states_of_india', 'lsoi')
          ->fields('lsoi', ['state']);
      
      // Fetch the results as an associative array [state => state]
      $results = $query->execute()->fetchAllKeyed();
  
      return $states + $results;
  }
  function _df_list_of_cities()
{
    $city = array(
        0 => '-Select-',
    );
    $query = \Drupal::database()->select('list_cities_of_india');
    $query->fields('list_cities_of_india');
    $query->orderBy('city', 'ASC');
    $city_list = $query->execute();
    while ($city_list_data = $city_list->fetchObject()) {
        $city[$city_list_data->city] = $city_list_data->city;
    } //$city_list_data = $city_list->fetchObject()
    return $city;
}
function _rm_df_list_of_pincodes()
{
    $pincode = array(
        0 => '-Select-',
    );
    $query = \Drupal::database()->select('list_of_all_india_pincode');
    $query->fields('list_of_all_india_pincode');
    $query->orderBy('pincode', 'ASC');
    $pincode_list = $query->execute();
    while ($pincode_list_data = $pincode_list->fetchObject()) {
        $pincode[$pincode_list_data->pincode] = $pincode_list_data->pincode;
    } //$pincode_list_data = $pincode_list->fetchObject()
    return $pincode;
}
  
function _rm_df_list_of_states()
{
    $states = array(
        0 => '-Select-',
    );
    $query = \Drupal::database()->select('list_states_of_india');
    $query->fields('list_states_of_india');
    //$query->orderBy('', '');
    $states_list = $query->execute();
    while ($states_list_data = $states_list->fetchObject()) {
        $states[$states_list_data->state] = $states_list_data->state;
    } //$states_list_data = $states_list->fetchObject()
    return $states;
}
function cfd_research_migration_path()
{
    return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'cfd_uploads/research_migration_uploads/';
}
function _rm_list_of_simulation_types(){
    $simulation_types = array();
    $query = \Drupal::database()->select('research_migration_simulation_type');
    $query->fields('research_migration_simulation_type');
    $simulation_type_list = $query->execute();
    while ($simulation_type_data = $simulation_type_list->fetchObject()) {
        $simulation_types[$simulation_type_data->id] = $simulation_type_data->simulation_type;
    }
    return $simulation_types;
}
function _rm_list_of_solvers($simulation_id){
    $simulation_id = $simulation_id;
    $solvers = array(
        0 => '-Select-',
        );
    $query = \Drupal::database()->select('research_migration_solvers');
    $query->fields('research_migration_solvers');
    $query->condition('simulation_type_id',$simulation_id);
    $solvers_list = $query->execute();
    while($solvers_data = $solvers_list->fetchObject()){
        $solvers[$solvers_data->solver_name] = $solvers_data->solver_name;
    }
    return $solvers;
}
function _rm_df_dir_name($project, $proposar_name)
{
    $project_title = $this->ucname($project);
    $proposar_name = $this->ucname($proposar_name);
    $dir_name = $project_title . ' By ' . $proposar_name;
    $directory_name = str_replace("__", "_", str_replace(" ", "_", str_replace("/", "_", trim($dir_name))));
    return $directory_name;
}

function ucname($string)
{
$string = ucwords(strtolower($string));
foreach (array(
'-',
'\''
) as $delimiter)
{
if (strpos($string, $delimiter) !== false)
{
$string = implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
} //strpos($string, $delimiter) !== false
} //array( '-', '\'') as $delimiter
return $string;
}
function _df_sentence_case($string)
{
    $string = ucwords(strtolower($string));
    foreach (array(
        '-',
        '\'',
    ) as $delimiter) {
        if (strpos($string, $delimiter) !== false) {
            $string = implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
        } //strpos($string, $delimiter) !== false
    } //array( '-', '\'') as $delimiter
    return $string;
}
// function cfd_research_migration_get_proposal()
// {
//     // $user = \Drupal::currentUser();
//     global $user;
//     $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
//     $query = \Drupal::database()->select('research_migration_proposal');
//     $query->fields('research_migration_proposal');
//     $query->condition('uid', $user->uid);
//     $query->orderBy('id', 'DESC');
//     $query->range(0, 1);
//     $proposal_q = $query->execute();
//     $proposal_data = $proposal_q->fetchObject();
//     // if (!$proposal_data) {
//     //     \Drupal::messenger()->addError("You do not have any approved  Research Migration proposal. Please propose a Research Migration");
//     //     // drupal_goto('');
//     // } //!$proposal_data
//     switch ($proposal_data->approval_status) {
//         case 0:
//             \Drupal::messenger()->addStatus(t('Proposal is awaiting approval.'));
//             return false;
//         case 1:
//             return $proposal_data;
//         case 2:
//             \Drupal::messenger()->addError(t('Proposal has been dis-approved.'));
//             return false;
//         case 3:
//             \Drupal::messenger()->addStatus(t('Proposal has been marked as completed.'));
//             return false;
//         default:
//             \Drupal::messenger()->addError(t('Invalid proposal state. Please contact site administrator for further information.'));
//             return false;
//     } //$proposal_data->approval_status
//     // return false;
// }


public function cfd_research_migration_get_proposal() {
    $user = \Drupal::currentUser();
    
    // Fetch latest proposal for current user
    $query = \Drupal::database()->select('research_migration_proposal', 'rmp');
    $query->fields('rmp');
    $query->condition('uid', $user->id());
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();

    // Debugging log
    \Drupal::logger('research_migration')->notice('Proposal Data: <pre>' . print_r($proposal_data, TRUE) . '</pre>');

    if (!$proposal_data) {
        \Drupal::messenger()->addError("No proposal found for this user.");
        return NULL;
    }

    // Check approval status
    switch ($proposal_data->approval_status) {
        case 0:
            \Drupal::messenger()->addWarning(t('Proposal is awaiting approval.'));
            break;
        case 1:
            return $proposal_data;  // ✅ Approved proposal
        case 2:
            \Drupal::messenger()->addError(t('Proposal has been disapproved.'));
            break;
        case 3:
            \Drupal::messenger()->addStatus(t('Proposal has been marked as completed.'));
            break;
        default:
            \Drupal::messenger()->addError(t('Invalid proposal state. Contact the administrator.'));
    }
    return $proposal_data;  // ✅ Return proposal even if not approved
}


public function cfd_research_migration_check_valid_filename($file_name) {
  if (!preg_match('/^[0-9a-zA-Z\._]+$/', $file_name)) {
    return FALSE;
  }
  elseif (substr_count($file_name, '.') > 1) {
    return FALSE;
  }
  else {
    return TRUE;
  }
}
public function _rm_list_of_versions() {
  $versions = [];

  $database = Database::getConnection();
  $query = $database->select('research_migration_software_version', 'r');
  $query->fields('r');

  $version_list = $query->execute();

  foreach ($version_list as $version_data) {
    $versions[$version_data->id] = $version_data->research_migration_version;
  }

  return $versions;
}

public function default_value_for_uploaded_files($filetype, $proposal_id)
{
    $database = Database::getConnection();
    $query = $database->select('research_migration_submitted_abstracts_file', 'rmsaf')
        ->fields('rmsaf')
        ->condition('proposal_id', $proposal_id)
        ->condition('filetype', $filetype)
        ->execute()
        ->fetchObject();

    return $query ?: null; // Return null if no result found
}

// function _rm_list_of_research_migration() {
//   $existing_research_migration = [];

//   $query = "
//     SELECT rm_project_title_name 
//     FROM rm_list_of_project_titles 
//     WHERE rm_project_title_name NOT IN (
//       SELECT project_title 
//       FROM research_migration_proposal 
//       WHERE approval_status IN (:status_0, :status_1, :status_3)
//     )
//   ";

//   $connection = Database::getConnection();
//   $result = $connection->query($query, [
//     ':status_0' => 0,
//     ':status_1' => 1,
//     ':status_3' => 3,
//   ]);

//   foreach ($result as $record) {
//     $existing_research_migration[$record->rm_project_title_name] = $record->rm_project_title_name;
//   }

//   return $existing_research_migration;
// }


/**
 * Creates README.txt file for a Research Migration Project.
 */
public function CreateReadmeFileResearchMigrationProject($proposal_id) {
  $database = Database::getConnection();

  $query = $database->select('research_migration_proposal', 'r')
    ->fields('r')
    ->condition('id', $proposal_id)
    ->range(0, 1);

  $proposal_data = $query->execute()->fetchObject();

  if (!$proposal_data) {
    \Drupal::logger('cfd_research_migration')->error('Invalid proposal ID: @id', [
      '@id' => $proposal_id,
    ]);
    return FALSE;
  }

  $root_path = $this->cfd_research_migration_path();
  $directory = $root_path . $proposal_data->directory_name;

  /** @var \Drupal\Core\File\FileSystemInterface $file_system */
  $file_system = \Drupal::service('file_system');

  // Ensure directory exists.
  $file_system->prepareDirectory(
    $directory,
    FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
  );

  $file_path = $directory . '/README.txt';

  $txt  = "About the Research Migration\n\n";
  $txt .= "Title Of The Research Migration Project: " . $proposal_data->project_title . "\n";
  $txt .= "Proposer Name: " . $proposal_data->name_title . " " . $proposal_data->contributor_name . "\n";
  $txt .= "University: " . $proposal_data->university . "\n\n";
  $txt .= "Research Migration Project By FOSSEE, IIT Bombay\n";

  // Write file using Drupal file API.
  $file_system->saveData($txt, $file_path, FileSystemInterface::EXISTS_REPLACE);

  return $txt;
}

public function _rm_list_of_research_migration() {
    $existing_research_migration = [];

    $query = "
      SELECT rm_project_title_name 
      FROM rm_list_of_project_titles 
      WHERE rm_project_title_name NOT IN (
        SELECT project_title 
        FROM research_migration_proposal 
        WHERE approval_status IN (:status_0, :status_1, :status_3)
      )
    ";

    $connection = Database::getConnection();
    $result = $connection->query($query, [
      ':status_0' => 0,
      ':status_1' => 1,
      ':status_3' => 3,
    ]);

    foreach ($result as $record) {
      $existing_research_migration[$record->rm_project_title_name] = $record->rm_project_title_name;
    }

    return $existing_research_migration;
  }

  /**
   * Disable caching so form updates every time.
   */
  public function getCacheMaxAge() {
    return 0;
  }
}


 