<?php

/**
 * @file
 * Contains \Drupal\cfd_research_migration\Form\CfdResearchMigrationRunForm.
 */

namespace Drupal\cfd_research_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Database\Database;


class CfdResearchMigrationRunForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cfd_research_migration_run_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $options_first = $this->_list_of_research_migration();
    // $url_research_migration_id = (int) arg(2);
    $route_match = \Drupal::routeMatch();

    $url_research_migration_id = (int) $route_match->getParameter('url_research_migration_id');
    $research_migration_data = $this->_research_migration_information($url_research_migration_id);
    if ($research_migration_data == 'Not found') {
      $url_research_migration_id = '';
    } //$research_migration_data == 'Not found'
    if (!$url_research_migration_id) {
      $selected = !$form_state->getValue(['research_migration']) ? $form_state->getValue(['research_migration']) : key($options_first);
    } //!$url_research_migration_id
    elseif ($url_research_migration_id == '') {
      $selected = 0;
    } //$url_research_migration_id == ''
    else {
      $selected = $url_research_migration_id;
    }
    $form = [];
    $form['research_migration'] = [
      '#type' => 'select',
      '#title' => t('Title of the research migration'),
      '#options' => $this->_list_of_research_migration(),
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::research_migration_project_details_callback'
        ],
    ];
    if (!$url_research_migration_id) {
      $form['research_migration_details'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax_research_migration_details"></div>',
      ];
      $form['selected_research_migration'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax_selected_research_migration"></div>',
      ];
    } //!$url_research_migration_id
    else {
      $research_migration_default_value = $url_research_migration_id;
      $form['research_migration_details'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax_research_migration_details">' . $this->_research_migration_details($research_migration_default_value) . '</div>',
      ];
      $form['selected_research_migration'] = [
        '#type' => 'item',
        // '#markup' => '<div id="ajax_selected_research_migration">' . l('Download Synopsis', "research-migration-project/download/project-file/" . $research_migration_default_value) . '<br>' . l('Download research migration', 'research-migration-project/full-download/project/' . $research_migration_default_value) . '</div>',

'#markup' => '<div id="ajax_selected_research_migration">' . 
    Link::fromTextAndUrl(t('Download Synopsis'), Url::fromUri('internal:/research-migration-project/download/project-file/' . $research_migration_default_value))->toString() . '<br>' .
    Link::fromTextAndUrl(t('Download research migration'), Url::fromUri('internal:/research-migration-project/full-download/project/' . $research_migration_default_value))->toString() . 
'</div>',

      ];
    }
    return $form;
  }

 

/**
 * AJAX callback for research migration project details.
 */
function research_migration_project_details_callback(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $research_migration_default_value = $form_state->getValue('research_migration');

    if ($research_migration_default_value != 0) {
        // Load research migration details
        $research_migration_details = $this->_research_migration_information($research_migration_default_value);

        // Load user entity
        $provider = \Drupal::entityTypeManager()->getStorage('user')->load($research_migration_details->uid);

        // Generate download links
        $download_synopsis = Link::fromTextAndUrl(
            t('Download Synopsis'),
            Url::fromUri('internal:/research-migration-project/download/project-file/' . $research_migration_default_value)
        )->toString();

        $download_project = Link::fromTextAndUrl(
            t('Download Research Migration'),
            Url::fromUri('internal:/research-migration-project/full-download/project/' . $research_migration_default_value)
        )->toString();

        if ($research_migration_details->uid > 0) {
            $response->addCommand(new HtmlCommand('#ajax_selected_research_migration', $download_synopsis . '<br>' . $download_project));
        } else {
            $response->addCommand(new HtmlCommand('#ajax_selected_research_migration', ''));
        }

        // Update research migration details
        $response->addCommand(new HtmlCommand('#ajax_research_migration_details', $this->_research_migration_details($research_migration_default_value)));
    } else {
        // Clear details when no selection is made
        $response->addCommand(new HtmlCommand('#ajax_research_migration_details', ''));
        $response->addCommand(new HtmlCommand('#ajax_selected_research_migration', ''));
    }

    return $response;
}


/**
 * Fetches a list of approved research migration projects.
 *
 * @return array
 *   An associative array of project titles with IDs.
 */
function _list_of_research_migration() {
    $research_migration_titles = [
        '0' => t('Please select...')
    ];

    // Query the database to get approved research migration projects
    $query = \Drupal::database()->select('research_migration_proposal', 'rmp')
        ->fields('rmp', ['id', 'project_title', 'name_title', 'contributor_name'])
        ->condition('approval_status', 3)
        ->orderBy('project_title', 'ASC')
        ->execute()
        ->fetchAll();

    // Loop through results and format the project titles
    foreach ($query as $row) {
        $research_migration_titles[$row->id] = $row->project_title . ' (Proposed by ' . $row->name_title . ' ' . $row->contributor_name . ')';
    }

    return $research_migration_titles;
}

/**
 * Retrieves and formats research migration details.
 *
 * @param int $research_migration_default_value
 *   The ID of the research migration proposal.
 *
 * @return string
 *   The formatted HTML markup with details.
 */
function _research_migration_details($research_migration_default_value) {
  if ($research_migration_default_value == 0) {
      return '';
  }

  // Fetch research migration details
  $research_migration_details = $this->_research_migration_information($research_migration_default_value);

  // Construct the HTML markup
  $details = '<span style="color: rgb(128, 0, 0);"><strong>' . t('About the research migration') . '</strong></span><br />';
  $details .= '<ul>';
  $details .= '<li><strong>' . t('Proposer Name:') . '</strong> ' . $research_migration_details->name_title . ' ' . $research_migration_details->contributor_name . '</li>';
  $details .= '<li><strong>' . t('Title of the research migration:') . '</strong> ' . $research_migration_details->project_title . '</li>';
  $details .= '<li><strong>' . t('University:') . '</strong> ' . $research_migration_details->university . '</li>';
  $details .= '</ul>';

  return $details;
}


/**
 * Retrieves research migration proposal details.
 *
 * @param int $proposal_id
 *   The ID of the research migration proposal.
 *
 * @return object|string
 *   The proposal details as an object, or 'Not found' if no data exists.
 */
function _research_migration_information($proposal_id) {
    $query = Database::getConnection()->select('research_migration_proposal', 'rmp')
        ->fields('rmp')
        ->condition('id', $proposal_id)
        ->condition('approval_status', 3);

    $research_migration_data = $query->execute()->fetchObject();

    return $research_migration_data ?: 'Not found';
}

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {


}
}
?>
