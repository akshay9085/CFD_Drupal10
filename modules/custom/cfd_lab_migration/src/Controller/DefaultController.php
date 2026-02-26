<?php /**
 * @file
 * Contains \Drupal\lab_migration\Controller\DefaultController.
 */

namespace Drupal\lab_migration\Controller;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Service;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\Markup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
/**
 * Default controller for the lab_migration module.
 */
class DefaultController extends ControllerBase {

  public function lab_migration_proposal_pending() {
    /* get pending proposals to be approved */
    $pending_rows = [];
    //$pending_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE approval_status = 0 ORDER BY id DESC");
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('approval_status', 0);
    $query->orderBy('id', 'DESC');
    $pending_q = $query->execute();
    while ($pending_data = $pending_q->fetchObject()) {
      $approval_url = Link::fromTextAndUrl('Approve', Url::fromRoute('lab_migration.proposal_approval_form',['id'=>$pending_data->id]))->toString();
      $edit_url =  Link::fromTextAndUrl('Edit', Url::fromRoute('lab_migration.proposal_edit_form',['id'=>$pending_data->id]))->toString();
      $mainLink = t('@linkApprove | @linkReject', array('@linkApprove' => $approval_url, '@linkReject' => $edit_url));
      $pending_rows[$pending_data->id] = [
        date('d-m-Y', $pending_data->creation_date),

        // Create the link with the user's name as the link text.
       Link::fromTextAndUrl($pending_data->name, Url::fromRoute('entity.user.canonical', ['user' => $pending_data->uid])),
      

       // Link::fromTextAndUrl($pending_data->name, 'user/' . $pending_data->uid),
       $pending_data->lab_title,
       $pending_data->department,
       $mainLink 
     
      ];
    }
    /* check if there are any pending proposals */
    // if (!$pending_rows) {
    //   \Drupal::messenger()->addmessage(t('There are no pending proposals.'), 'status');
    //   return '';
    // }
    $pending_header = [
      'Date of Submission',
      'Name',
      'Title of the Lab',
      'Department',
      'Action',
    ];
    //$output = theme_table($pending_header, $pending_rows);
    $output =  [
      '#type' => 'table',
      '#header' => $pending_header,
      '#rows' => $pending_rows,
      '#empty' => 'no rows found',
    ];
    return $output;
  }

  public function lab_migration_solution_proposal_pending() {
    /* get list of solution proposal where the solution_provider_uid is set to some userid except 0 and solution_status is also 1 */
    $pending_rows = [];
    //$pending_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE solution_provider_uid != 0 AND solution_status = 1 ORDER BY id DESC");
    $query = \Drupal::database()->select('lab_migration_proposal', 'lmp');
$query->fields('lmp');
$query->condition('solution_provider_uid', 0, '!=');
$query->condition('solution_status', 1);
$query->orderBy('creation_date', 'DESC');
$query->orderBy('id', 'DESC');
$pending_q = $query->execute();

    while ($pending_data = $pending_q->fetchObject()) {
      $approval_url = Link::fromTextAndUrl('Approve', Url::fromRoute('lab_migration.proposal_approval_form',['id'=>$pending_data->id]))->toString();
      $edit_url =  Link::fromTextAndUrl('Edit', Url::fromRoute('lab_migration.proposal_edit_form',['id'=>$pending_data->id]))->toString();
      $mainLink = t('@linkApprove | @linkReject', array('@linkApprove' => $approval_url, '@linkReject' => $edit_url));
      $pending_rows[$pending_data->id] = [
        date('d-m-Y', $pending_data->creation_date),
        
       // Create the link with the user's name as the link text.
       Link::fromTextAndUrl($pending_data->name, Url::fromRoute('entity.user.canonical', ['user' => $pending_data->uid])),


        // Link::fromTextAndUrl($pending_data->name, 'user/' . $pending_data->uid),
        $pending_data->lab_title,
        $pending_data->department,
        $mainLink 
      ];
    }
    /* check if there are any pending proposals */
    // if (!$pending_rows) {
    //   \Drupal::messenger()->addmessage(t('There are no pending solution proposals.'), 'status');
    //   return '';
    // }
    $pending_header = [
      'Proposer Name',
      'Title of the Lab',
      'Action',
    ];
    $output = [
      '#type' => 'table',
      '#header' => $pending_header,
      '#rows' => $pending_rows,
      '#empty' => 'No rows found',
      '#cache' => [
        'max-age' => 0,
      ],
    ];
        return $output;
  }

  public function lab_migration_proposal_pending_solution() {
    /* get pending proposals to be approved */
    $pending_rows = [];
    //$pending_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE approval_status = 1 ORDER BY id DESC");
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('approval_status', 1);
    $query->orderBy('id', 'DESC');
    $pending_q = $query->execute();
    while ($pending_data = $pending_q->fetchObject()) {
      $link = Link::fromTextAndUrl($pending_data->name, Url::fromRoute('entity.user.canonical', ['user' => $pending_data->uid]))->toString();
      $pending_rows[$pending_data->id] = [
        date('d-m-Y', $pending_data->creation_date),
        date('d-m-Y', $pending_data->approval_date),
        // Link::fromTextAndUrl($pending_data->name, 'user/' . $pending_data->uid),
       $link,
        $pending_data->lab_title,
        $pending_data->department,
        Link::fromTextAndUrl('Status', Url::fromRoute('lab_migration.proposal_status_form', ['id' => $pending_data->id]))->toString(),
        // Link::fromTextAndUrl('Status', 'internal:/lab-migration/manage-proposal/status/' . $pending_data->id),
        
      ];
    }
    /* check if there are any pending proposals */
    // if (!$pending_rows) {
    //   \Drupal::messenger()->addMessage($this->t('There are no proposals pending for solutions.'), 'status');
    //   return new Response('');
    // }
    $pending_header = [
      'Date of Submission',
      'Date of Approval',
      'Name',
      'Title of the Lab',
      'Department',
      'Action',
    ];
    // var_dump($pending_header);die;
    $output =  [
      '#type' => 'table',
      '#header' => $pending_header,
      '#rows' => $pending_rows,
    ];
    
    return $output;
  }

  public function lab_migration_proposal_all() {
    /* get pending proposals to be approved */
    $proposal_rows = [];
    //$proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} ORDER BY id DESC");
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->orderBy('id', 'DESC');
    $proposal_q = $query->execute();
    while ($proposal_data = $proposal_q->fetchObject()) {
      // var_dump($proposal_data);die;
      $approval_status = '';
      switch ($proposal_data->approval_status) {
        case 0:
          $approval_status = 'Pending';
          break;
        case 1:
          $approval_status = 'Approved';
          break;
        case 2:
          $approval_status = 'Dis-approved';
          break;
        case 3:
          $approval_status = 'Solved';
          break;
        default:
          $approval_status = 'Unknown';
          break;
      }
// var_dump($proposal_data);die;
      $approval_url =  Link::fromTextAndUrl('Status', Url::fromRoute('lab_migration.proposal_status_form', ['id' => $proposal_data->id]))->toString();
      //var_dump($approval_url);die;
      // $edit_url =  Link::fromTextAndUrl('Edit', Url::fromUri('internal:/lab-migration/manage-proposal/edit/',['id'=>$proposal_data->id]))->toString();
      // $mainLink = t('@linkApprove | @linkReject', array('@linkApprove' => $approval_url, '@linkReject' => $edit_url));
      
      
        // $proposal_rows[] = array(
        //   date('d-m-Y', $proposal_data->creation_date),
        //   // $uid_url = Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid]),
        //   //  $link = Link::fromTextAndUrl($proposal_data->name, $uid_url)->toString(),
         
        //  Link::fromTextAndUrl($proposal_data->name, Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid])),
      

          // Link::fromTextAndUrl($pending_data->name, 'user/' . $pending_data->uid),
          // $proposal_data->lab_title,
          // $proposal_data->department,
          // $approval_status,
          // $mainLink 

          // Generate links as strings
    $user_url = Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid]);
    $user_link = Link::fromTextAndUrl($proposal_data->name, $user_url)->toString();

    $proposal_rows[] = [
      date('d-m-Y', $proposal_data->creation_date),  // Format the date
      $user_link,                                   // Use the rendered user link
      $proposal_data->lab_title,
      $proposal_data->department,
      $approval_status,
      $approval_url,
    ];
  }
        
    
    // var_dump($proposal_data);die;

    /* check if there are any pending proposals */
    // if (!$proposal_rows) {
    //   \Drupal::messenger()->addmessage(t('There are no proposals.'), 'status');
    //   return '';
    // }
    $proposal_header = [
      'Date of Submission',
      'Name',
      'Title of the Lab',
      'Department',
      'Status',
      'Action',
    ];
    
    $output = [
      '#type' => 'table',
      '#header' => $proposal_header,
      '#rows' => $proposal_rows,
  ];
    return $output;
  }

  public function lab_migration_category_all() {
    /* get pending proposals to be approved */
    $proposal_rows = [];
    // $proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} ORDER BY id DESC");
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->orderBy('id', 'DESC');
    $proposal_q = $query->execute();
    while ($proposal_data = $proposal_q->fetchObject()) {
      $category_edit_url =  Link::fromTextAndUrl('Edit category', Url::fromRoute('lab_migration.category_edit_form',['id'=>$proposal_data->id]))->toString();
     
        $proposal_rows[] = [
         
            date('d-m-Y', $proposal_data->creation_date),
            // $link = Link::fromTextAndUrl(
            //   $proposal_data->name,
            //   Url::fromUri('internal:/lab_migration/proposal' . $proposal_data->uid)
            // )->toRenderable(),
          // l($proposal_data->name, 'user/' . $proposal_data->uid),
          Link::fromTextAndUrl($proposal_data->name, Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid])),

            $proposal_data->lab_title,
            $proposal_data->department,
            $proposal_data->category,
            $category_edit_url,
        ];
    }
    $proposal_header = [
      'Date of Submission',
      'Name',
      'Title of the Lab',
      'Department',
      'Category',
      'Action',
    ];
    
    $output = [
      '#type' => 'table',
      '#header' => $proposal_header,
      '#rows' => $proposal_rows,
      
  ];
    return $output;
  }

  public function lab_migration_proposal_open() {
    $user = \Drupal::currentUser();
    /* get open proposal list */
    $proposal_rows = [];
    //$proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE approval_status = 1 AND solution_provider_uid = 0");
    $query = \Drupal::database()->select('lab_migration_proposal', 'lmp');
    $query->fields('lmp');
    $query->condition('approval_status', 1);
    $query->condition('solution_provider_uid', 0);
    $proposal_q = $query->execute();
    $proposal_data_array = $proposal_q->fetchAll();
  
    $proposal_q = $query->execute();
$proposal_data_array = $proposal_q->fetchAll();

if (!empty($proposal_data_array)) {
    foreach ($proposal_data_array as $proposal_data) {
        if ($proposal_data->problem_statement_file == '') {
            $problem_statement_file = "NA";
        } else {
            $problem_statement_file = Link::fromTextAndUrl(
                'View',
                Url::fromUri('internal:/lab-migration/download/problem-statement/' . $proposal_data->id)
            );
        }

        $proposal_rows[] = [
            Link::fromTextAndUrl(
                $proposal_data->lab_title,
                Url::fromUri('internal:/lab-migration/show-proposal/' . $proposal_data->id)
            ),
            $problem_statement_file,
            Link::fromTextAndUrl(
                'Apply',
                Url::fromUri('internal:/lab-migration/show-proposal/' . $proposal_data->id)
            ),
        ];
    }
}
  $proposal_header = [
        'Title of the Lab',
        'Problem Statement',
        'Actions',
      ];
      // Render table if proposals are available
      $return_html = [
        '#type' => 'table',
        '#header' => $proposal_header,
        '#rows' => $proposal_rows,
        '#empty' => t('No proposals are available'), // Optional message if the table is empty
    ];
//  else {
//     // Render a message if no proposals are available
//     $return_html = [
//         '#markup' => t('No proposals are available'),
//     ];
// }
    //$return_html = theme_table($proposal_header, $proposal_rows);
    return $return_html;
  }

  public function lab_migration_code_approval() {
    /* get a list of unapproved solutions */
    //$pending_solution_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution} WHERE approval_status = 0");
    $query = \Drupal::database()->select('lab_migration_solution');
    $query->fields('lab_migration_solution');
    $query->condition('approval_status', 0);
    $pending_solution_q = $query->execute();
    if (!$pending_solution_q) {
      \Drupal::messenger()->addmessage(t('There are no pending code approvals.'), 'status');
      return '';
    }
    $pending_solution_rows = [];
    while ($pending_solution_data = $pending_solution_q->fetchObject()) {
      /* get experiment data */
      //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE id = %d", $pending_solution_data->experiment_id);
      $query = \Drupal::database()->select('lab_migration_experiment');
      $query->fields('lab_migration_experiment');
      $query->condition('id', $pending_solution_data->experiment_id);
      $experiment_q = $query->execute();
      $experiment_data = $experiment_q->fetchObject();
      /* get proposal data */
      // $proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $experiment_data->proposal_id);
      $query = \Drupal::database()->select('lab_migration_proposal');
      $query->fields('lab_migration_proposal');
      $query->condition('id', $experiment_data->proposal_id);
      $proposal_q = $query->execute();
      $proposal_data = $proposal_q->fetchObject();
      /* get solution provider details */
      $solution_provider_user_name = '';
      $user_data = User::load($proposal_data->solution_provider_uid);
      if ($user_data) {
        $solution_provider_user_name = $user_data->name;
      }
      else {
        $solution_provider_user_name = '';
      }
      /* setting table row information */
      $url = Url::fromRoute('lab_migration.code_approval_form', ['solution_id' => $pending_solution_data->id]);
      //     Generate the URL using the route and passing the parameter for solution_id.
// Create the link with Link::fromTextAndUrl and translate the text.
$link = Link::fromTextAndUrl(t('Edit'), $url)->toString();
      $pending_solution_rows[] = [
        $proposal_data->lab_title,
        $experiment_data->title,
        $proposal_data->name,
        $proposal_data->solution_provider_name,
        $link
      ];
    }
    /* check if there are any pending solutions */
    // if (!$pending_solution_rows) {
    //   \Drupal::messenger()->addmessage(t('There are no pending solutions'), 'status');
    //   return '';
    // }
    $header = [
      'Title of the Lab',
      'Experiment',
      'Proposer',
      'Solution Provider',
      'Actions',
    ];
    $output =  [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $pending_solution_rows,
    ];
    return $output;
  }

//   public function lab_migration_list_experiments() {
//     $user = \Drupal::currentUser();

//     $proposal_data = \Drupal::service('lab_migration_global')->lab_migration_get_proposal();
//     //var_dump($proposal_data);die;
//     if (!$proposal_data) {
//       // drupal_goto('');
//       return new RedirectResponse(Url::fromRoute('<front>')->toString());

//       return;
//     }
// // Prepare return HTML with lab and proposer information.
// $return_html = [
//   '#markup' => '<strong>Title of the Lab:</strong><br />' . $proposal_data->lab_title . '<br /><br />' .
//                '<strong>Proposer Name:</strong><br />' . $proposal_data->name_title . ' ' . $proposal_data->name . '<br /><br />'
// ];
//     // $return_html = '<strong>Title of the Lab:</strong><br />' . $proposal_data->lab_title . '<br /><br />';
//     // $return_html .= '<strong>Proposer Name:</strong><br />' . $proposal_data->name_title . ' ' . $proposal_data->name . '<br /><br />';
//     // $return_html .= $link =Link::fromTextAndUrl('Upload Solution', 'lab-migration/code/upload') . '<br />';
//     //  Link to 'Upload Solution' page.
//     $upload_solution_url = Url::fromRoute('lab_migration.upload_code_form');
//     $return_html['#markup'] .= Link::fromTextAndUrl('Upload Solution', $upload_solution_url)->toString() . '<br />';
  
//     // Prepare experiment table header.
//     $experiment_header = ['No. Title of the Experiment', 'Type', 'Status', 'Actions'];
//     $experiment_rows = [];
  
//     /* get experiment list */
//     // $experiment_rows = [];
//     //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d ORDER BY number ASC", $proposal_data->id);
//     $query = \Drupal::database()->select('lab_migration_experiment');
//     $query->fields('lab_migration_experiment');
//     $query->condition('proposal_id', $proposal_data->id);
//     $query->orderBy('number', 'ASC');
//     $experiment_q = $query->execute();

//     while ($experiment_data = $experiment_q->fetchObject()) {


//       $experiment_rows[] = [
//         $experiment_data->number . ') ' . $experiment_data->title,
//         '',
//         '',
//         '',
//       ];
//       /* get solution list */
//       //$solution_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution} WHERE experiment_id = %d ORDER BY id ASC", $experiment_data->id);
//       $query = \Drupal::database()->select('lab_migration_solution');
//       $query->fields('lab_migration_solution');
//       $query->condition('experiment_id', $experiment_data->id);
//       $query->orderBy('id', 'ASC');
//       $solution_q = $query->execute();
//       if ($solution_q) {
//         while ($solution_data = $solution_q->fetchObject()) {
//           $solution_status = '';
//           switch ($solution_data->approval_status) {
//             case 0:
//               $solution_status = "Pending";
//               break;
//             case 1:
//               $solution_status = "Approved";
//               break;
//             default:
//               $solution_status = "Unknown";
//               break;
//           }
//           if ($solution_data->approval_status == 0) {
//             $url = Url::fromUri('internal:/lab-migration/code/delete/' . $solution_data->id);

// // Create the Link object.
// $link = Link::fromTextAndUrl('Delete', $url);
//             $experiment_rows[] = [
//               '',
//               $solution_status,
//               // $link =Link::fromTextAndUrl('Delete', 'lab-migration/code/delete/' . $solution_data->id),
//               // Construct the URL for a custom path.
//               $link
//             ];
//           }
//           else {
//             $experiment_rows[] = [
//               '',
//               $solution_status,
//               '',
//             ];
//           }
//           /* get solution files */
//           //$solution_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_files} WHERE solution_id = %d ORDER BY id ASC", $solution_data->id);
//           $query = \Drupal::database()->select('lab_migration_solution_files');
//           $query->fields('lab_migration_solution_files');
//           $query->condition('solution_id', $solution_data->id);
//           $query->orderBy('id', 'ASC');
//           $solution_files_q = $query->execute();

//           if ($solution_files_q) {
//             while ($solution_files_data = $solution_files_q->fetchObject()) {
//               $code_file_type = '';
//               switch ($solution_files_data->filetype) {
//                 case 'S':
//                   $code_file_type = 'Source';
//                   break;
//                 case 'R':
//                   $code_file_type = 'Result';
//                   break;
//                 case 'X':
//                   $code_file_type = 'Xcox';
//                   break;
//                 case 'U':
//                   $code_file_type = 'Unknown';
//                   break;
//                 default:
//                   $code_file_type = 'Unknown';
//                   break;
//               }
//               $experiment_rows[] = [
//                 // "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . // Construct the URL for the download route.
//                 // $url = Url::fromRoute('lab_migration.download_file', ['id' => $solution_files_data->id]),
                
//                 // Create the Link object.
//                 // $link = Link::fromTextAndUrl($solution_files_data->filename, $url)->toString(),
//                 $code_file_type,
//                 '',
//                 '',
//               ];
//             }
//           }
          
          
//         }
//       }
//     }

//     $experiment_header = [
//       'No. Title of the Experiment',
//       'Type',
//       'Status',
//       'Actions',
//     ];
//     // $return_html .= theme_table($experiment_header, $experiment_rows);

//     $return_html[] = [
//       '#type' => 'table',
//       '#header' => $experiment_header,
//       '#rows' => $experiment_rows,
//     ];
//     return $return_html;
//   }

public function lab_migration_list_experiments() {
  // Get proposal data.
  $proposal_data = \Drupal::service("lab_migration_global")->lab_migration_get_proposal();
  if (!$proposal_data) {
    return new RedirectResponse(Url::fromRoute('<front>')->toString());
  }

  // Prepare return HTML with lab and proposer information.
  $return_html = [
    '#markup' => '<strong>Title of the Lab:</strong><br />' . $proposal_data->lab_title . '<br /><br />' .
                 '<strong>Proposer Name:</strong><br />' . $proposal_data->name_title . ' ' . $proposal_data->name . '<br /><br />'
  ];

  // Link to 'Upload Solution' page.
  $upload_solution_url = Url::fromRoute('lab_migration.upload_code_form');
  $return_html['#markup'] .= Link::fromTextAndUrl('Upload Solution', $upload_solution_url)->toString() . '<br />';

  // Prepare experiment table header.
  $experiment_header = ['No. Title of the Experiment', 'Type', 'Status', 'Actions'];
  $experiment_rows = [];

  // Get experiment list.
  $query = \Drupal::database()->select('lab_migration_experiment', 'lme');
  $query->fields('lme');
  $query->condition('proposal_id', $proposal_data->id);
  $query->orderBy('number', 'ASC');
  $experiment_q = $query->execute();

  while ($experiment_data = $experiment_q->fetchObject()) {
    $experiment_rows[] = [
      $experiment_data->number . ') ' . $experiment_data->title,
      '', '', ''
    ];
    //var_dump($experiment_data);die;
    // Get solutions related to each experiment.
    $query = \Drupal::database()->select('lab_migration_solution', 'lms');
    $query->fields('lms');
    $query->condition('experiment_id', $experiment_data->id);
    $query->orderBy('id', 'ASC');
    $solution_q = $query->execute();

    if ($solution_q) {
      while ($solution_data = $solution_q->fetchObject()) {
        //var_dump($solution_data);die;
        $solution_status = ($solution_data->approval_status == 0) ? "Pending" : (($solution_data->approval_status == 1) ? "Approved" : "Unknown");

        // Action link for 'Delete' if approval status is pending.
        $action_link = '';
        if ($solution_data->approval_status == 0) {
          $delete_url = Url::fromUri('internal:/lab-migration/code/delete/' . $solution_data->id);
          //Url::fromRoute('lab_migration.upload_code_delete', ['id' => $solution_data->id]);
          $action_link = Link::fromTextAndUrl('Delete', $delete_url)->toString();
        }

        $experiment_rows[] = [
          // "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . 
          $solution_data->code_number . "   " . $solution_data->caption, 
          '', 
          $solution_status, 
          $action_link
        ];

        // Get solution files related to each solution.
        $query = \Drupal::database()->select('lab_migration_solution_files', 'lmsf');
        $query->fields('lmsf');
        $query->condition('solution_id', $solution_data->id);
        $query->orderBy('id', 'ASC');
        $solution_files_q = $query->execute();

        if ($solution_files_q) {
          while ($solution_files_data = $solution_files_q->fetchObject()) {
            //var_dump($solution_files_data);die;
            $filetype_map = ['S' => 'Source', 'R' => 'Result', 'X' => 'Xcox', 'U' => 'Unknown'];
            $code_file_type = $filetype_map[$solution_files_data->filetype] ?? 'Unknown';

            $download_url = Url::fromUri('internal:/lab-migration/download/file/' . $solution_files_data->id);
            $experiment_rows[] = [
             
              Link::fromTextAndUrl($solution_files_data->filename, $download_url)->toString(),
              $code_file_type,
              '',
              ''
            ];
          }
        }
      
        // Get dependency files related to each solution.
        // $query = \Drupal::database()->select('lab_migration_solution_dependency', 'lmsd');
        // $query->fields('lmsd');
        // $query->condition('solution_id', $solution_data->id);
        // $query->orderBy('id', 'ASC');
        // $dependency_q = $query->execute();

        // while ($dependency_data = $dependency_q->fetchObject()) {
        //   $query = \Drupal::database()->select('lab_migration_dependency_files', 'lmf');
        //   $query->fields('lmf');
        //   $query->condition('id', $dependency_data->dependency_id);
        //   $dependency_files_q = $query->execute();

        //   if ($dependency_files_data = $dependency_files_q->fetchObject()) {
        //     $dependency_url = Url::fromRoute('lab_migration.download_dependency', ['id' => $dependency_files_data->id]);
        //     $experiment_rows[] = [
        //   Link::fromTextAndUrl($dependency_files_data->filename, $dependency_url)->toString(),
        //       'Dependency',
        //       '',
        //       ''
        //     ];
        //   }
        // }
      }
    }
  }
//var_dump($experiment_rows);die;
  // Build the table render array.
  $return_html[] = [
    '#theme' => 'table',
    '#header' => $experiment_header,
    '#rows' => $experiment_rows,
  ];

  return $return_html;
}


  public function lab_migration_upload_code_delete() {
    $user = \Drupal::currentUser();

    $root_path = \Drupal::service('lab_migration_global')->lab_migration_path();
    // $solution_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();

    $solution_id = (int) $route_match->getParameter('solution_id');

    /* check solution */
    // $solution_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution} WHERE id = %d LIMIT 1", $solution_id);
    $query = \Drupal::database()->select('lab_migration_solution');
    $query->fields('lab_migration_solution');
    $query->condition('id', $solution_id);
    $query->range(0, 1);
    $solution_q = $query->execute();
    $solution_data = $solution_q->fetchObject();
    if (!$solution_data) {
      \Drupal::messenger()->addmessage('Invalid solution.', 'error');
      // drupal_goto('lab-migration/code');
      return;
    }
    if ($solution_data->approval_status != 0) {
      \Drupal::messenger()->addmessage('You cannnot delete a solution after it has been approved. Please contact site administrator if you want to delete this solution.', 'error');
      // drupal_goto('lab-migration/code');
      return;
    }

    //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE id = %d LIMIT 1", $solution_data->experiment_id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('id', $solution_data->experiment_id);
    $query->range(0, 1);
    $experiment_q = $query->execute();

    $experiment_data = $experiment_q->fetchObject();
    if (!$experiment_data) {
      \Drupal::messenger()->addmessage('You do not have permission to delete this solution.', 'error');
      // drupal_goto('lab-migration/code');
      return;
    }

    //$proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d AND solution_provider_uid = %d LIMIT 1", $experiment_data->proposal_id, $user->uid);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $experiment_data->proposal_id);
    $query->condition('solution_provider_uid', $user->uid);
    $query->range(0, 1);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    // if (!$proposal_data) {
    //   \Drupal::messenger()->addmessage('You do not have permission to delete this solution.', 'error');
    //   drupal_goto('lab-migration/code');
      // return;
    // }

    /* deleting solution files */
    if (\Drupal::service('lab_migration_global')->lab_migration_delete_solution($solution_data->id)) {
      \Drupal::messenger()->addmessage('Solution deleted.', 'status');

      /* sending email */
      // $email_to = $user->mail;

      // $from = $config->get('lab_migration_from_email', '');
      // $bcc = $config->get('lab_migration_emails', '');
      // $cc = $config->get('lab_migration_cc_emails', '');

      // $param['solution_deleted_user']['lab_title'] = $proposal_data->lab_title;
      // $param['solution_deleted_user']['experiment_title'] = $experiment_data->title;
      // $param['solution_deleted_user']['solution_number'] = $solution_data->code_number;
      // $param['solution_deleted_user']['solution_caption'] = $solution_data->caption;
      // $param['solution_deleted_user']['user_id'] = $user->uid;
      // $param['solution_deleted_user']['headers'] = [
      //   'From' => $from,
      //   'MIME-Version' => '1.0',
      //   'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      //   'Content-Transfer-Encoding' => '8Bit',
      //   'X-Mailer' => 'Drupal',
      //   'Cc' => $cc,
      //   'Bcc' => $bcc,
      // ];

      // if (!drupal_mail('lab_migration', 'solution_deleted_user', $email_to, language_default(), $param, $from, TRUE)) {
      //   \Drupal::messenger()->addmessage('Error sending email message.', 'error');
      // }
    }
    else {
      \Drupal::messenger()->addmessage('Error deleting example.', 'status');
    }

    // drupal_goto('lab-migration/code');
    return;
  }

  // public function lab_migration_download_solution_file() {
  //   $solution_file_id = arg(3);
  //   $root_path = lab_migration_path();
  //   // $solution_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_files} WHERE id = %d LIMIT 1", $solution_file_id);
  //   $solution_files_q = \Drupal::database()->query("SELECT lmsf.*, lmp.directory_name FROM lab_migration_solution_files lmsf JOIN lab_migration_solution lms JOIN lab_migration_experiment lme JOIN lab_migration_proposal lmp WHERE lms.id = lmsf.solution_id AND lme.id = lms.experiment_id AND lmp.id = lme.proposal_id AND lmsf.id = :solution_id LIMIT 1", [
  //     ':solution_id' => $solution_file_id
  //     ]);
  //   /*$query = \Drupal::database()->select('lab_migration_solution_files');
  //   $query->fields('lab_migration_solution_files');
  //   $query->condition('id', $solution_file_id);
  //   $query->range(0, 1);
  //   $solution_files_q = $query->execute();*/
  //   $solution_file_data = $solution_files_q->fetchObject();
  //   header('Content-Type: ' . $solution_file_data->filemime);
  //   //header('Content-Type: application/octet-stram');
  //   header('Content-disposition: attachment; filename="' . str_replace(' ', '_', ($solution_file_data->filename)) . '"');
  //   header('Content-Length: ' . filesize($root_path . $solution_file_data->directory_name . '/' . $solution_file_data->filepath));
  //   readfile($root_path . $solution_file_data->directory_name . '/' . $solution_file_data->filepath);
  // }

 

function lab_migration_download_solution_file() {
    // Get the solution file ID from the route or query parameters.

    $route_match = \Drupal::routeMatch();
    $solution_file_id = (int) $route_match->getParameter('solution_file_id');
    
    // Define the root path where files are stored.
    $root_path = \Drupal::service("lab_migration_global")->lab_migration_path();

    // Fetch file details using the Database API.
    $connection = \Drupal::database();
    $query = $connection->select('lab_migration_solution_files', 'lmsf');
    $query->join('lab_migration_solution', 'lms', 'lms.id = lmsf.solution_id');
    $query->join('lab_migration_experiment', 'lme', 'lme.id = lms.experiment_id');
    $query->join('lab_migration_proposal', 'lmp', 'lmp.id = lme.proposal_id');
    $query->fields('lmsf')
          ->fields('lmp', ['directory_name'])
          ->condition('lmsf.id', $solution_file_id)
          ->range(0, 1);
    $solution_file_data = $query->execute()->fetchObject();

    if (!$solution_file_data) {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Solution file not found.');
    }

    // Construct the file path.
    $file_path = $root_path . $solution_file_data->directory_name . '/' . $solution_file_data->filepath;

    // Check if the file exists.
    if (!file_exists($file_path)) {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('File does not exist.');
    }

    // Prepare the response with appropriate headers.
    $response = new Response();
    $response->headers->set('Content-Type', $solution_file_data->filemime);
    $response->headers->set('Content-Disposition', 'attachment; filename="' . str_replace(' ', '_', $solution_file_data->filename) . '"');
    $response->headers->set('Content-Length', filesize($file_path));
    $response->setContent(file_get_contents($file_path));

    return $response;
}

public function lab_migration_download_problem_statement() {
    // Get the proposal ID from the route.
    $route_match = \Drupal::routeMatch();
    $proposal_id = (int) $route_match->getParameter('id');
    // var_dump($proposal_id);die;
    // Get the file path root.
    $root_path = \Drupal::service("lab_migration_global")->lab_migration_path();
    // var_dump($root_path);die;
    // Query the proposal data.
    $query = \Drupal::database()->query("SELECT lmp.* FROM lab_migration_proposal lmp WHERE lmp.id = :proposal_id LIMIT 1", [
      ':proposal_id' => $proposal_id
    ]);
    $proposal_data = $query->fetchObject();
    // var_dump($proposal_data);die;
    // Check if the proposal data exists.
    if (!$proposal_data) {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Proposal not found');
    }
    
    // Construct the file path.
    $file_path = $root_path . $proposal_data->directory_name . '/' . $proposal_data->problem_statement_file;
    // var_dump($file_path);die;
    // Check if the file exists.
    if (!file_exists($file_path)) {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('File not found');
    }
    
    // Prepare the file response.
    $response = new BinaryFileResponse($file_path);
    // $response->setContentDisposition(Response::DISPOSITION_ATTACHMENT, str_replace(' ', '_', $proposal_data->problem_statement_file));
    $response->headers->set('Content-Type', 'application/msword'); // Adjust content type as needed.
    // Return the response.
    return $response;
}


  // public function lab_migration_download_solution() {
  //   // $solution_id = arg(3);
  //   $route_match = \Drupal::routeMatch();

  //   $solution_id = (int) $route_match->getParameter('solution_id');

  //   // $route_match = \Drupal::routeMatch();

  //   // $experiment_id = (int) $route_match->getParameter('experiment_id');
   
  //   // $root_path = \Drupal::service('lab_migration_global')->lab_migration_path();
  //   // var_Dump($experiment_id);die;
  //   /* get solution data */
  //   //$solution_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution} WHERE id = %d", $solution_id);
  //   $query = \Drupal::database()->select('lab_migration_solution');
  //   $query->fields('lab_migration_solution');
  //   $query->condition('id', $solution_id);
  //   $solution_q = $query->execute();
  //   $solution_data = $solution_q->fetchObject();

  //   // var_dump($solution_data);die;
  //   //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE id = %d", $solution_data->experiment_id);
  //   $query = \Drupal::database()->select('lab_migration_experiment');
  //   $query->fields('lab_migration_experiment');
  //   $query->condition('id', $solution_data->experiment_id);
  //   $experiment_q = $query->execute();
  //   $experiment_data = $experiment_q->fetchObject();
  //   // var_dump($experiment_data);die;
  //   //$solution_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_files} WHERE solution_id = %d", $solution_id);
  //   /*$query = \Drupal::database()->select('lab_migration_solution_files');
  //   $query->fields('lab_migration_solution_files');
  //   $query->condition('solution_id', $solution_id);
  //   $solution_files_q = $query->execute();*/
  //   $solution_files_q = \Drupal::database()->query("SELECT lmsf.*, lmp.directory_name FROM lab_migration_solution_files lmsf JOIN lab_migration_solution lms JOIN lab_migration_experiment lme JOIN lab_migration_proposal lmp WHERE lms.id = lmsf.solution_id AND lme.id = lms.experiment_id AND lmp.id = lme.proposal_id AND lmsf.solution_id = :solution_id", [
  //     ':solution_id' => $solution_id
  //     ]);
  //   //$solution_dependency_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_dependency} WHERE solution_id = %d", $solution_id);
  //   $query = \Drupal::database()->select('lab_migration_solution_dependency');
  //   $query->fields('lab_migration_solution_dependency');
  //   $query->condition('solution_id', $solution_id);
  //   $solution_dependency_files_q = $query->execute();
  //   $CODE_PATH = 'CODE' . $solution_data->code_number . '/';
  //   /* zip filename */
  //   $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
  //   /* creating zip archive on the server */
  //   $zip = new \ZipArchive();
  //   $zip->open($zip_filename, \ZipArchive::CREATE);
  //   while ($solution_files_row = $solution_files_q->fetchObject()) {
  //     $zip->addFile($root_path . $solution_files_row->directory_name . '/' . $solution_files_row->filepath, $CODE_PATH . str_replace(' ', '_', ($solution_files_row->filename)));
  //   }
  //   /* dependency files */
  //   while ($solution_dependency_files_row = $solution_dependency_files_q->fetchObject()) {
  //     //$dependency_file_data = (\Drupal::database()->query("SELECT * FROM {lab_migration_dependency_files} WHERE id = %d LIMIT 1", $solution_dependency_files_row->dependency_id))->fetchObject();
  //     $query = \Drupal::database()->select('lab_migration_dependency_files');
  //     $query->fields('lab_migration_dependency_files');
  //     $query->condition('id', $solution_dependency_files_row->dependency_id);
  //     $query->range(0, 1);
  //     $dependency_file_data = $query->execute()->fetchObject();
  //     if ($dependency_file_data) {
  //       $zip->addFile($root_path . $dependency_file_data->filepath, $CODE_PATH . 'DEPENDENCIES/' . str_replace(' ', '_', ($dependency_file_data->filename)));
  //     }
  //   }
  //   $zip_file_count = $zip->numFiles;
  //   $zip->close();
  //   if ($zip_file_count > 0) {
  //     /* download zip file */
  //     header('Content-Type: application/zip');
  //     header('Content-disposition: attachment; filename="CODE' . $solution_data->code_number . '.zip"');
  //     header('Content-Length: ' . filesize($zip_filename));
  //     ob_clean();
  //     //flush();
  //     readfile($zip_filename);
  //     unlink($zip_filename);
  //   }
  //   else {
  //     \Drupal::messenger()->addmessage("There are no files in this solutions to download", 'error');
  //     // drupal_goto('lab-migration/lab-migration-run');
  //     // $url = Url::fromRoute('lab_migration.run_form')->toString();
      
  //     $url = Url::fromRoute('lab_migration.run_form')->toString();
      
  //     return new RedirectResponse($url);
  //         }
  // }
  public function lab_migration_download_solution() {
    $root_path = \Drupal::service("lab_migration_global")->lab_migration_path();
    $route_match = \Drupal::routeMatch();

$solution_id = (int) $route_match->getParameter('solution_id');
    $solution_files_q = \Drupal::database()->query("SELECT lmsf.*, lmp.directory_name, lms.code_number FROM lab_migration_solution_files lmsf 
JOIN lab_migration_solution lms ON lms.id = lmsf.solution_id 
JOIN lab_migration_experiment lme
ON lms.experiment_id = lme.id JOIN lab_migration_proposal lmp ON lmp.id = lme.proposal_id WHERE lmsf.id = :solution_id", [
      ':solution_id' => $solution_id
      ]);

$solution_files_row = $solution_files_q->fetchObject();

    // var_dump($solution_files_row->code_number);die;
     ob_clean();
     header('Content-Type: ' . $solution_files_row->filemime);
     header('Content-disposition: attachment; filename=' . $solution_files_row->filename);
     header('Content-Length: ' . $solution_files_row->filesize);
     //ob_end_clean();
     //flush();
     readfile($root_path . $solution_files_row->directory_name . '/' . $solution_files_row->filepath);
    $CODE_PATH = 'CODE' . $solution_data->code_number . '/';
  }


  public function lab_migration_download_experiment() {
    // $experiment_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();
    $experiment_id = (int) $route_match->getParameter('experiment_id');
    $root_path = \Drupal::service('lab_migration_global')->lab_migration_path();
    /* get solution data */
    //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE id = %d", $experiment_id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('id', $experiment_id);
    $experiment_q = $query->execute();
    $experiment_data = $experiment_q->fetchObject();
    $EXP_PATH = 'EXP' . $experiment_data->number . '/';
    /* zip filename */
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new \ZipArchive();
    $zip->open($zip_filename, \ZipArchive::CREATE);
    //$solution_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution} WHERE experiment_id = %d AND approval_status = 1", $experiment_id);
    $query = \Drupal::database()->select('lab_migration_solution');
    $query->fields('lab_migration_solution');
    $query->condition('experiment_id', $experiment_id);
    $query->condition('approval_status', 1);
    $solution_q = $query->execute();
    while ($solution_row = $solution_q->fetchObject()) {
      $CODE_PATH = 'CODE' . $solution_row->code_number . '/';
      // $solution_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_files} WHERE solution_id = %d", $solution_row->id);
      $solution_files_q = \Drupal::database()->query("SELECT lmsf.*, lmp.directory_name FROM lab_migration_solution_files lmsf JOIN lab_migration_solution lms JOIN lab_migration_experiment lme JOIN lab_migration_proposal lmp WHERE lms.id = lmsf.solution_id AND lme.id = lms.experiment_id AND lmp.id = lme.proposal_id AND lmsf.solution_id = :solution_id", [
        ':solution_id' => $solution_row->id
        ]);
      /* $query = \Drupal::database()->select('lab_migration_solution_files');
        $query->fields('lab_migration_solution_files');
        $query->condition('solution_id', $solution_row->id);
        $solution_files_q = $query->execute();*/
      // $solution_dependency_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_dependency} WHERE solution_id = %d", $solution_row->id);        
      while ($solution_files_row = $solution_files_q->fetchObject()) {
        $zip->addFile($root_path . $solution_files_row->directory_name . '/' . $solution_files_row->filepath, $EXP_PATH . $CODE_PATH . str_replace(' ', '_', ($solution_files_row->filename)));
      }
      /* dependency files */
      $query = \Drupal::database()->select('lab_migration_solution_dependency');
      $query->fields('lab_migration_solution_dependency');
      $query->condition('solution_id', $solution_row->id);
      $solution_dependency_files_q = $query->execute();
      while ($solution_dependency_files_row = $solution_dependency_files_q->fetchObject()) {
        //$dependency_file_data = (\Drupal::database()->query("SELECT * FROM {lab_migration_dependency_files} WHERE id = %d LIMIT 1", $solution_dependency_files_row->dependency_id))->fetchObject();
        $query = \Drupal::database()->select('lab_migration_dependency_files');
        $query->fields('lab_migration_dependency_files');
        $query->condition('id', $solution_dependency_files_row->dependency_id);
        $query->range(0, 1);
        $dependency_file_data = $query->execute()->fetchObject();
        if ($dependency_file_data) {
          $zip->addFile($root_path . $dependency_file_data->filepath, $EXP_PATH . $CODE_PATH . 'DEPENDENCIES/' . str_replace(' ', '_', ($dependency_file_data->filename)));
        }
      }
    }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0) {
      /* download zip file */
      header('Content-Type: application/zip');
      header('Content-disposition: attachment; filename="EXP' . $experiment_data->number . '.zip"');
      header('Content-Length: ' . filesize($zip_filename));
      ob_clean();
      //flush();
      readfile($zip_filename);
      unlink($zip_filename);
    }
    else {
      \Drupal::messenger()->addmessage("There are no solutions in this experiment to download", 'error');
      // drupal_goto('lab-migration/lab-migration-run');
      $response = new RedirectResponse('/lab-migration/lab-migration-run');
$response->send();
    }
  }

  public function lab_migration_download_lab() {
    $user = \Drupal::currentUser();
    // $lab_id = arg(3);
$route_match = \Drupal::routeMatch();
$lab_id = (int) $route_match->getParameter('lab_id');

$root_path = \Drupal::service("lab_migration_global")->lab_migration_path();
    //var_dump($lab_id);die;
   
    /* get solution data */
    //$lab_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $lab_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $lab_id);
    $lab_q = $query->execute();
    $lab_data = $lab_q->fetchObject();
    $LAB_PATH = $lab_data->lab_title . '/';
    /* zip filename */
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new \ZipArchive();
    $zip->open($zip_filename, \ZipArchive::CREATE);
    //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d", $lab_id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('proposal_id', $lab_id);
    $experiment_q = $query->execute();
    while ($experiment_row = $experiment_q->fetchObject()) {
      $EXP_PATH = 'EXP' . $experiment_row->number . '/';
      //$solution_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution} WHERE experiment_id = %d AND approval_status = 1", $experiment_row->id);
      $query = \Drupal::database()->select('lab_migration_solution');
      $query->fields('lab_migration_solution');
      $query->condition('experiment_id', $experiment_row->id);
      $query->condition('approval_status', 1);
      $solution_q = $query->execute();
      while ($solution_row = $solution_q->fetchObject()) {
        $CODE_PATH = 'CODE' . $solution_row->code_number . '/';
        //$solution_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_files} WHERE solution_id = %d", $solution_row->id);

        $solution_files_q = \Drupal::database()->query("SELECT lmsf.*, lmp.directory_name FROM lab_migration_solution_files lmsf JOIN lab_migration_solution lms JOIN lab_migration_experiment lme JOIN lab_migration_proposal lmp WHERE lms.id = lmsf.solution_id AND lme.id = lms.experiment_id AND lmp.id = lme.proposal_id AND lmsf.solution_id = :solution_id", [
          ':solution_id' => $solution_row->id
          ]);
        /*$query = \Drupal::database()->select('lab_migration_solution_files');
            $query->fields('lab_migration_solution_files');
            $query->condition('solution_id', $solution_row->id);
            $solution_files_q = $query->execute();*/
        //$solution_dependency_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_dependency} WHERE solution_id = %d", $solution_row->id);
        $query = \Drupal::database()->select('lab_migration_solution_dependency');
        $query->fields('lab_migration_solution_dependency');
        $query->condition('solution_id', $solution_row->id);
        $solution_dependency_files_q = $query->execute();
        while ($solution_files_row = $solution_files_q->fetchObject()) {
          $zip->addFile($root_path . $solution_files_row->directory_name . '/' . $solution_files_row->filepath, $EXP_PATH . $CODE_PATH . str_replace(' ', '_', ($solution_files_row->filename)));
          //var_dump($zip->numFiles);
        }
        // die;
            /* dependency files */
        while ($solution_dependency_files_row = $solution_dependency_files_q->fetchObject()) {
          //$dependency_file_data = (\Drupal::database()->query("SELECT * FROM {lab_migration_dependency_files} WHERE id = %d LIMIT 1", $solution_dependency_files_row->dependency_id))->fetchObject();
          $query = \Drupal::database()->select('lab_migration_dependency_files');
          $query->fields('lab_migration_dependency_files');
          $query->condition('id', $solution_dependency_files_row->dependency_id);
          $query->range(0, 1);
          $dependency_file_data = $query->execute()->fetchObject();
          if ($dependency_file_data) {
            $zip->addFile($root_path . $dependency_file_data->filepath, $EXP_PATH . $CODE_PATH . 'DEPENDENCIES/' . str_replace(' ', '_', ($dependency_file_data->filename)));
          }
        }
      }
    }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0) {
      if ($user->uid) {
        /* download zip file */
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename="' . str_replace(' ', '_', $lab_data->lab_title) . '.zip"');
        header('Content-Length: ' . filesize($zip_filename));
        ob_clean();
        //flush();
        readfile($zip_filename);
        unlink($zip_filename);
      }
      else {
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename="' . str_replace(' ', '_', $lab_data->lab_title) . '.zip"');
        header('Content-Length: ' . filesize($zip_filename));
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        header('Pragma: no-cache');
        ob_end_flush();
        ob_clean();
        flush();
        readfile($zip_filename);
        unlink($zip_filename);
      }
    }
    else {
      \Drupal::messenger()->addmessage("There are no solutions in this Lab to download", 'error');
      // drupal_goto('lab-migration/lab-migration-run');
      $url = Url::fromUri('internal:/lab-migration/lab-migration-run/')->toString();

// Return the RedirectResponse.
return new RedirectResponse($url);
    }
  }

  public function lab_migration_download_full_experiment() {
    $experiment_id = arg(3);
    $root_path = lab_migration_path();
    $APPROVE_PATH = 'APPROVED/';
    $PENDING_PATH = 'PENDING/';
    /* get solution data */
    //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE id = %d", $experiment_id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('id', $experiment_id);
    $experiment_q = $query->execute();
    $experiment_data = $experiment_q->fetchObject();
    $EXP_PATH = 'EXP' . $experiment_data->number . '/';
    /* zip filename */
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new ZipArchive();
    $zip->open($zip_filename, ZipArchive::CREATE);
    /* approved solutions */
    //$solution_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution} WHERE experiment_id = %d AND approval_status = 1", $experiment_id);
    $query = \Drupal::database()->select('lab_migration_solution');
    $query->fields('lab_migration_solution');
    $query->condition('experiment_id', $experiment_id);
    $query->condition('approval_status', 1);
    $solution_q = $query->execute();
    while ($solution_row = $solution_q->fetchObject()) {
      $CODE_PATH = 'CODE' . $solution_row->code_number . '/';
      //$solution_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_files} WHERE solution_id = %d", $solution_row->id);
        /*$query = \Drupal::database()->select('lab_migration_solution_files');
        $query->fields('lab_migration_solution_files');
        $query->condition('solution_id', $solution_row->id);
        $solution_files_q = $query->execute();*/
      $solution_files_q = \Drupal::database()->query("SELECT lmsf.*, lmp.directory_name FROM lab_migration_solution_files lmsf JOIN lab_migration_solution lms JOIN lab_migration_experiment lme JOIN lab_migration_proposal lmp WHERE lms.id = lmsf.solution_id AND lme.id = lms.experiment_id AND lmp.id = lme.proposal_id AND lmsf.id = :solution_id", [
        ':solution_id' => $solution_row->id
        ]);
      //$solution_dependency_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_dependency} WHERE solution_id = %d", $solution_row->id);
      $query = \Drupal::database()->select('lab_migration_solution_dependency');
      $query->fields('lab_migration_solution_dependency');
      $query->condition('solution_id', $solution_row->id);
      $solution_dependency_files_q = $query->execute();
      while ($solution_files_row = $solution_files_q->fetchObject()) {
        $zip->addFile($root_path . $solution_files_row->directory_name . '/' . $solution_files_row->filepath, $APPROVE_PATH . $EXP_PATH . $CODE_PATH . $solution_files_row->filename);
      }
      /* dependency files */
      while ($solution_dependency_files_row = $solution_dependency_files_q->fetchObject()) {
        // $dependency_file_data = (\Drupal::database()->query("SELECT * FROM {lab_migration_dependency_files} WHERE id = %d LIMIT 1", $solution_dependency_files_row->dependency_id))->fetchObject();
        $query = \Drupal::database()->select('lab_migration_dependency_files');
        $query->fields('lab_migration_dependency_files');
        $query->condition('id', $solution_dependency_files_row->dependency_id);
        $query->range(0, 1);
        $dependency_file_data = $query->execute()->fetchObject();
        if ($dependency_file_data) {
          $zip->addFile($root_path . $dependency_file_data->filepath, $APPROVE_PATH . $EXP_PATH . $CODE_PATH . 'DEPENDENCIES/' . $dependency_file_data->filename);
        }
      }
    }
    /* unapproved solutions */
    // $solution_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution} WHERE experiment_id = %d AND approval_status = 0", $experiment_id);
    $query = \Drupal::database()->select('lab_migration_solution');
    $query->fields('lab_migration_solution');
    $query->condition('experiment_id', $experiment_id);
    $query->condition('approval_status', 0);
    $solution_q = $query->execute();
    while ($solution_row = $solution_q->fetchObject()) {
      $CODE_PATH = 'CODE' . $solution_row->code_number . '/';
      //$solution_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_files} WHERE solution_id = %d", $solution_row->id);
        /*$query = \Drupal::database()->select('lab_migration_solution_files');
        $query->fields('lab_migration_solution_files');
        $query->condition('solution_id', $solution_row->id);
        $solution_files_q = $query->execute();*/
      $solution_files_q = \Drupal::database()->query("SELECT lmsf.*, lmp.directory_name FROM lab_migration_solution_files lmsf JOIN lab_migration_solution lms JOIN lab_migration_experiment lme JOIN lab_migration_proposal lmp WHERE lms.id = lmsf.solution_id AND lme.id = lms.experiment_id AND lmp.id = lme.proposal_id AND lmsf.id = :solution_id", [
        ':solution_id' => $solution_row->id
        ]);

      //$solution_dependency_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_dependency} WHERE solution_id = %d", $solution_row->id);
      $query = \Drupal::database()->select('lab_migration_solution_dependency');
      $query->fields('lab_migration_solution_dependency');
      $query->condition('solution_id', $solution_row->id);
      $solution_dependency_files_q = $query->execute();
      while ($solution_files_row = $solution_files_q->fetchObject()) {
        $zip->addFile($root_path . $solution_files_row->directory_name . '/' . $solution_files_row->filepath, $PENDING_PATH . $EXP_PATH . $CODE_PATH . $solution_files_row->filename);
      }
      /* dependency files */
      while ($solution_dependency_files_row = $solution_dependency_files_q->fetchObject()) {
        // $dependency_file_data = (\Drupal::database()->query("SELECT * FROM {lab_migration_dependency_files} WHERE id = %d LIMIT 1", $solution_dependency_files_row->dependency_id))->fetchObject();
        $query = \Drupal::database()->select('lab_migration_dependency_files');
        $query->fields('lab_migration_dependency_files');
        $query->condition('id', $solution_dependency_files_row->dependency_id);
        $query->range(0, 1);
        $dependency_file_data = $query->execute()->fetchObject();
        if ($dependency_file_data) {
          $zip->addFile($root_path . $dependency_file_data->filepath, $PENDING_PATH . $EXP_PATH . $CODE_PATH . 'DEPENDENCIES/' . $dependency_file_data->filename);
        }
      }
    }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0) {
      /* download zip file */
      header('Content-Type: application/zip');
      header('Content-disposition: attachment; filename="EXP' . $experiment_data->number . '.zip"');
      header('Content-Length: ' . filesize($zip_filename));
      readfile($zip_filename);
      unlink($zip_filename);
    }
    else {
      \Drupal::messenger()->addmessage("There are no solutions in this experiment to download", 'error');
      // drupal_goto('lab-migration/code-approval/bulk');
      $response = new RedirectResponse('/lab-migration/code-approval/bulk');
$response->send();
    }
  }

  public function lab_migration_download_full_lab() {
    
    $route_match = \Drupal::routeMatch();

$lab_id = (int) $route_match->getParameter('lab_id');
    
$root_path = \Drupal::service("lab_migration_global")->lab_migration_path();
    
    
    $APPROVE_PATH = 'APPROVED/';
    $PENDING_PATH = 'PENDING/';
    /* get solution data */
    //$lab_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $lab_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $lab_id);
    $lab_q = $query->execute();
    $lab_data = $lab_q->fetchObject();
    $LAB_PATH = $lab_data->directory_name . '/';
    // var_dump($root_path .$LAB_PATH);die;
    /* zip filename */
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    //var_dump($zip_filename);die;
    /* creating zip archive on the server */
    $zip = new \ZipArchive();
    $zip->open($zip_filename, \ZipArchive::CREATE);
    /* approved solutions */
    //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d", $lab_id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('proposal_id', $lab_id);
    $experiment_q = $query->execute();
    while ($experiment_row = $experiment_q->fetchObject()) {
      $EXP_PATH = 'EXP' . $experiment_row->number . '/';
      //$solution_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution} WHERE experiment_id = %d AND approval_status = 1", $experiment_row->id);
      $query = \Drupal::database()->select('lab_migration_solution');
      $query->fields('lab_migration_solution');
      $query->condition('experiment_id', $experiment_row->id);
      $query->condition('approval_status', 1);
      $solution_q = $query->execute();
      while ($solution_row = $solution_q->fetchObject()) {
        $CODE_PATH = 'CODE' . $solution_row->code_number . '/';
        //$solution_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_files} WHERE solution_id = %d", $solution_row->id);
            $query = \Drupal::database()->select('lab_migration_solution_files');
            $query->fields('lab_migration_solution_files');
            $query->condition('solution_id', $solution_row->id);
            $solution_files_q = $query->execute();
        $solution_files_q = \Drupal::database()->query("SELECT lmsf.*, lmp.directory_name FROM lab_migration_solution_files lmsf JOIN lab_migration_solution lms JOIN lab_migration_experiment lme JOIN lab_migration_proposal lmp WHERE lms.id = lmsf.solution_id AND lme.id = lms.experiment_id AND lmp.id = lme.proposal_id AND lmsf.id = :solution_id", [
          ':solution_id' => $solution_row->id
          ]);
        //$solution_dependency_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_dependency} WHERE solution_id = %d", $solution_row->id);
        $query = \Drupal::database()->select('lab_migration_solution_dependency');
        $query->fields('lab_migration_solution_dependency');
        $query->condition('solution_id', $solution_row->id);
        $solution_dependency_files_q = $query->execute();
        while ($solution_files_row = $solution_files_q->fetchObject()) {
          // var_dump($root_path . $LAB_PATH . $solution_files_row->filepath, $LAB_PATH . $EXP_PATH . $CODE_PATH . str_replace(' ', '_', ($solution_files_row->filename)));die;

          // $zip->addFile($root_path . $solution_files_row->directory_name . '/' . $solution_files_row->filepath, $APPROVE_PATH . $EXP_PATH . $CODE_PATH . $solution_files_row->filename);
          $zip->addFile($root_path . $LAB_PATH . $solution_files_row->filepath, $LAB_PATH . $EXP_PATH . $CODE_PATH . str_replace(' ', '_', ($solution_files_row->filename)));
        }
        
        
      }
      /* unapproved solutions */
      //$solution_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution} WHERE experiment_id = %d AND approval_status = 0", $experiment_row->id);
      $query = \Drupal::database()->select('lab_migration_solution');
      $query->fields('lab_migration_solution');
      $query->condition('experiment_id', $experiment_row->id);
      $query->condition('approval_status', 0);
      $solution_q = $query->execute();
      while ($solution_row = $solution_q->fetchObject()) {
        $CODE_PATH = 'CODE' . $solution_row->code_number . '/';
        // $solution_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_files} WHERE solution_id = %d", $solution_row->id);
            // $query = \Drupal::database()->select('lab_migration_solution_files');
            // $query->fields('lab_migration_solution_files');
            // $query->condition('solution_id', $solution_row->id);
            // $solution_files_q = $query->execute();
            //var_dump($solution_row->id);die;
        $solution_files_q = \Drupal::database()->query("SELECT lmsf.*, lmp.directory_name FROM lab_migration_solution_files lmsf JOIN lab_migration_solution lms JOIN lab_migration_experiment lme JOIN lab_migration_proposal lmp WHERE lms.id = lmsf.solution_id AND lme.id = lms.experiment_id AND lmp.id = lme.proposal_id AND lmsf.solution_id = :solution_id", [
          ':solution_id' => $solution_row->id
          ]);

        // solution_dependency_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_dependency} WHERE solution_id = %d", $solution_row->id);
        $query = \Drupal::database()->select('lab_migration_solution_dependency');
        $query->fields('lab_migration_solution_dependency');
        $query->condition('solution_id', $solution_row->id);
        $solution_dependency_files_q = $query->execute();
        while ($solution_files_row = $solution_files_q->fetchObject()) {
          // var_dump($root_path . $solution_files_row->directory_name . '/' . $solution_files_row->filepath);die;
          $zip->addFile($root_path . $solution_files_row->directory_name . '/' . $solution_files_row->filepath, $LAB_PATH . $PENDING_PATH . $EXP_PATH . $CODE_PATH . $solution_files_row->filename);
        }
       
      }
    }
    $zip_file_count = $zip->numFiles;
    // var_dump($zip_file_count);die;
    $zip->close();
    if ($zip_file_count > 0) {
      /* download zip file */
      ob_clean();
      //flush();
      header('Content-Type: application/zip');
      header('Content-disposition: attachment; filename="' . $lab_data->lab_title . '.zip"');
      header('Content-Length: ' . filesize($zip_filename));
      readfile($zip_filename);
      unlink($zip_filename);
    }
    else {
      \Drupal::messenger()->addMessage("There are no solutions in this lab to download", 'error');
      // return new Response('lab-migration/code-approval/bulk');
      return new RedirectResponse('/lab-migration/code-approval/bulk');
      
    }
  }
               
               
 
  public function lab_migration_completed_labs_all() {
    $output = [];
  
    // Database query to fetch approved lab migration proposals.
    $query = \Drupal::database()->select('lab_migration_proposal', 'lmp');
    $query->fields('lmp');
    $query->condition('approval_status', 3);
    $query->orderBy('approval_date', 'DESC');
    $result = $query->execute();
    $rows = $result->fetchAll();
  
    if (empty($rows)) {
      // Display a message if no data is found.
      $output['content'] = [
        '#markup' => 'We are in the process of updating the lab migration data.',
      ];
    } else {
      $preference_rows = [];
      $i = count($rows);
  
      foreach ($rows as $row) {
        $approval_date = date("Y", $row->approval_date);
  
        // Problem statement link or NA.
        $problem_statement_file = !empty($row->problem_statement_file)
          ? Link::fromTextAndUrl('View', Url::fromUserInput('/lab-migration/download/problem-statement/' . $row->id))->toString()
          : 'NA';
  
        // Lab title link.
        $lab_title_link = Link::fromTextAndUrl($row->lab_title, Url::fromUserInput('/lab-migration/experiments-list/' . $row->id))->toString();
  
        $preference_rows[] = [
          $i,
          $row->university,
          $lab_title_link,
          $problem_statement_file,
          $approval_date,
        ];
        $i--;
      }
  
      $preference_header = [
        'No',
        'Institute',
        'Lab',
        'Problem Statement',
        'Year',
      ];
  
      // Render the table.
      $output['table'] = [
        '#type' => 'table',
        '#header' => $preference_header,
        '#rows' => $preference_rows,
        '#empty' => t('No labs found.'),
      ];
    }
  
    return $output;
  }
  

  public function lab_migration_labs_progress_all() {
    $page_content = [];
    //$query = "SELECT * FROM {lab_migration_proposal} WHERE approval_status = 1 and solution_status = 2";
    $query = \Drupal::database()->select('lab_migration_proposal', 'lmp');
      $query->fields('lmp');  // Fetch all fields (or specify specific fields here)
      $query->condition('approval_status', 1);
      $query->condition('solution_status', 2);
      $result = $query->execute();
      
       // Fetch all rows as an array of objects
      $results = $result->fetchAll();
    
      // Check if there are results
      if (empty($results)) {
        // If no results, return a message
        $page_content['#markup'] = "We are in the process of updating the lab migration data.";
      } else {
        // If there are results, create an ordered list
        $list_items = [];
        foreach ($results as $row) {
          // Create a list item for each row
          $list_items[] = '<li>' . $row->university . ' (' . $row->lab_title . ')</li>';
        }
    
        // Join list items and add the ordered list HTML around them
        $page_content['#markup'] = '<ol >' . implode('', $list_items) . '</ol>';
      }
    
      // Return the render array (Drupal will render it properly)
      return $page_content;
    }
  
  

  public function lab_migration_download_lab_pdf() {
    $lab_id = arg(2);
    _latex_copy_script_file();
    $full_lab = arg(3);
    if ($full_lab == "1") {
      _latex_generate_files($lab_id, TRUE);
    }
    else {
      _latex_generate_files($lab_id, FALSE);
    }
  }

  public function lab_migration_delete_lab_pdf() {
    $lab_id = arg(3);
    lab_migration_del_lab_pdf($lab_id);
    \Drupal::messenger()->addmessage(t('Lab schedule for regeneration.'), 'status');
    // drupal_goto('lab_migration/code_approval/bulk');
    return;
  }

  public function _list_all_lm_certificates() {
    $query = \Drupal::database()->query("SELECT * FROM lab_migration_certificate");
    $search_rows = [];
    $output = '';
    $details_list = $query->fetchAll();
    foreach ($details_list as $details) {
      if ($details->type == "Proposer") {
        $search_rows[] = [
          $details->lab_name,
          $details->institute_name,
          $details->name,
          $details->type,
          Link::fromTextAndUrl('Download Certificate', 'lab-migration/certificate/generate-pdf/' . $details->proposal_id . '/' . $details->id),
          Link::fromTextAndUrl('Edit Certificate', 'lab-migration/certificate/lm-proposer/form/edit/' . $details->proposal_id . '/' . $details->id),
        ];
      } //$details->type == "Proposer"
      else {
        $search_rows[] = [
          $details->lab_name,
          $details->institute_name,
          $details->name,
          $details->type,
        // Link::fromTextAndUrl('Download Certificate', 'lab-migration/certificate/generate-pdf/' . $details->proposal_id . '/' . $details->id)->toString(),
       
      
          Link::fromTextAndUrl('Download Certificate', Url::fromUri('internal:/lab-migration/certificate/generate-pdf/',['id'=>$proposal_id]))->toString(),
        // Link::fromTextAndUrl('Edit Certificate', 'lab-migration/certificate/lm-participation/form/edit/' . $details->proposal_id . '/' . $details->id),
        $edit_url =  Link::fromTextAndUrl('Edit Cerificate', Url::fromUri('internal:/lab-migration/certificate/lm-proposer/form/edit/',['id'=>$proposal_id]))->toString(),
        ];
      }
    } //$details_list as $details
    $search_header = [
      'Lab Name',
      'Institute name',
      'Name',
      'Type',
      'Download Certificates',
      'Edit Certificates',
    ];
    $output = [
      '#type' => 'table',
      '#header' => $search_header,
      '#rows' => $search_rows,
    ];
    return $output;
  }

  public function verify_lab_migration_certificates($qr_code = 0) {
    // $qr_code = arg(3);
    $route_match = \Drupal::routeMatch();
    $qr_code = (int) $route_match->getParameter('qr_code');
    $page_content = "";
    if ($qr_code) {
      $page_content = verify_qrcode_lm_fromdb($qr_code);
    } //$qr_code
    else {
      $verify_certificates_form = \Drupal::formBuilder()->getForm("verify_lab_migration_certificates_form");
      $page_content = \Drupal::service("renderer")->render($verify_certificates_form);
    }
    return $page_content;
  }

}
