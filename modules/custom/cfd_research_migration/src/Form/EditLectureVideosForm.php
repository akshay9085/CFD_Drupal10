<?php

namespace Drupal\cfd_research_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

/**
 * Form for editing lecture videos.
 */
class EditLectureVideosForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_lecture_videos_form';
  }

  /**
   * Fetch lecture video data from the database.
   */
  protected function getLectureVideoData($video_id) {
    $connection = Database::getConnection();
    return $connection->select('lecture_videos', 'lv')
      ->fields('lv')
      ->condition('id', $video_id)
      ->execute()
      ->fetchObject();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $route_match = \Drupal::routeMatch();
    $video_id = (int) $route_match->getParameter('video_id');

    // Fetch video data.
    $lecture_video_data = $this->getLectureVideoData($video_id);

    if (!$lecture_video_data) {
      $this->messenger()->addError($this->t('Lecture video not found.'));
      return $form;
    }

    // Store video_id in hidden field so we don’t lose it after submit.
    $form['video_id'] = [
      '#type' => 'hidden',
      '#value' => $video_id,
    ];

    $form['video_sno'] = [
      '#type' => 'textfield',
      '#title' => $this->t('S.No of the video'),
      '#required' => TRUE,
      '#disabled' => TRUE,
      '#default_value' => $lecture_video_data->video_sno,
    ];

    $form['title_of_video'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title of the video lecture'),
      '#required' => TRUE,
      '#default_value' => $lecture_video_data->video_title,
    ];

    $form['description_of_video'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description of the video'),
      '#default_value' => $lecture_video_data->video_description_text,
      '#format' => $lecture_video_data->video_description_text_format,
      '#required' => TRUE,
    ];

    $form['link_to_video'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Paste the URL of the video lecture'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $lecture_video_data->video_link,
    ];

    $form['link_to_script_file'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Paste the URL of the script file of the video lecture'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $lecture_video_data->script_file_link,
    ];

    $form['lecture_visibility'] = [
      '#type' => 'select',
      '#title' => $this->t('Do you want to disable this lecture?'),
      '#default_value' => $lecture_video_data->video_visibility ?? 'N',
      '#options' => [
        'Y' => $this->t('Yes'),
        'N' => $this->t('No'),
      ],
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $video_id = (int) $values['video_id'];

    $connection = Database::getConnection();
    $connection->update('lecture_videos')
      ->fields([
        'video_title' => $values['title_of_video'],
        'video_description_text' => $values['description_of_video']['value'],
        'video_description_text_format' => $values['description_of_video']['format'],
        'script_file_link' => $values['link_to_script_file'],
        'video_link' => $values['link_to_video'],
        'video_visibility' => $values['lecture_visibility'],
      ])
      ->condition('id', $video_id)
      ->execute();

    $this->messenger()->addStatus($this->t('Video details updated successfully.'));

    // Redirect back to the same edit page.
    $form_state->setRedirectUrl(Url::fromRoute('cfd_research_migration.edit_lecture_videos_form', [
      'video_id' => $video_id,
    ]));
  }

}
