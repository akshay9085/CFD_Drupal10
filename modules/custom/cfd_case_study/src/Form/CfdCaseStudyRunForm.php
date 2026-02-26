<?php

/**
 * @file
 * Contains \Drupal\cfd_case_study\Form\CfdCaseStudyRunForm.
 */

namespace Drupal\cfd_case_study\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class CfdCaseStudyRunForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cfd_case_study_run_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    \Drupal::moduleHandler()->loadInclude('cfd_case_study', 'inc', 'run');
    $options_first = _list_of_case_study();
    $route_match = \Drupal::routeMatch();
    $url_case_study_id = (int) ($route_match->getParameter('id') ?? \Drupal::request()->query->get('id'));
    $case_study_data = _case_study_information($url_case_study_id);
    if ($case_study_data == 'Not found') {
      $url_case_study_id = '';
    } //$case_study_data == 'Not found'
    if (!$url_case_study_id) {
      $selected = !$form_state->getValue(['case_study']) ? $form_state->getValue(['case_study']) : key($options_first);
    } //!$url_case_study_id
    elseif ($url_case_study_id == '') {
      $selected = 0;
    } //$url_case_study_id == ''
    else {
      $selected = $url_case_study_id;
    }
    $form = [];
    $form['case_study'] = [
      '#type' => 'select',
      '#title' => t('Title of the case study'),
      '#options' => _list_of_case_study(),
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::caseStudyProjectDetailsCallback',
        'event' => 'change',
        'limit_validation_errors' => [['case_study']],
        'wrapper' => 'ajax_case_study_wrapper',
        ],
    ];
    if (!$url_case_study_id) {
      $form['case_study_wrapper'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'ajax_case_study_wrapper'],
      ];
      $form['case_study_wrapper']['case_study_details'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'ajax_case_study_details'],
      ];
      $form['case_study_wrapper']['selected_case_study'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'ajax_selected_case_study'],
      ];
    } //!$url_case_study_id
    else {
      $case_study_default_value = $url_case_study_id;
      $form['case_study_wrapper'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'ajax_case_study_wrapper'],
      ];
      $form['case_study_wrapper']['case_study_details'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax_case_study_details">' . _case_study_details($case_study_default_value) . '</div>',
      ];
      $form['case_study_wrapper']['selected_case_study'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'ajax_selected_case_study'],
      ];
      // @FIXME
      // l() expects a Url object, created from a route name or external URI.
      // $form['selected_case_study'] = array(
      // 			'#type' => 'item',
      // 			'#markup' => '<div id="ajax_selected_case_study">' . l('Download Abstract', "case-study-project/download/project-file/" . $case_study_default_value) . '<br>' . l('Download Case Study', 'case-study-project/full-download/project/' . $case_study_default_value) . '</div>'
      // 		);

    }
    return $form;
  }

  public function caseStudyProjectDetailsCallback(array &$form, FormStateInterface $form_state) {
    \Drupal::moduleHandler()->loadInclude('cfd_case_study', 'inc', 'run');
    $form_state->setRebuild(TRUE);
    return $form['case_study_wrapper'];
  }
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state){
  }
}
?>
