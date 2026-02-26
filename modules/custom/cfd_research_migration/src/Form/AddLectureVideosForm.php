<?php

namespace Drupal\cfd_research_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;

class AddLectureVideosForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_lecture_videos_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['video_sno'] = [
      '#type' => 'number',
      '#title' => $this->t('S.No of the video'),
      '#description' => $this->t('Enter s.no starting from 1 to 100'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 100,
    ];

    $form['title_of_video'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Title of the video lecture"),
      '#required' => TRUE,
    ];

    $form['description_of_video'] = [
      '#type' => 'text_format',
      '#format' => 'full_html',
      '#title' => $this->t("Description of the video"),
      '#required' => TRUE,
    ];

    $form['link_to_video'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Paste the URL of the video lecture"),
      '#description' => $this->t('Copy paste the static URL, e.g., https://static.fossee.in/cfd/<path_to_video>'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['link_to_script_file'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Paste the URL of the script file of the video lecture"),
      '#description' => $this->t('Copy paste the static URL, e.g., https://static.fossee.in/cfd/<path_to_script_file>'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['lecture_visibility'] = [
      '#type' => 'select',
      '#title' => $this->t('Do you want to disable this lecture?'),
      '#options' => [
        'Y' => $this->t('Yes'),
        'N' => $this->t('No'),
      ],
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $connection = Database::getConnection();
    $connection->insert('lecture_videos')
      ->fields([
        'video_sno' => $values['video_sno'],
        'video_title' => $values['title_of_video'],
        'video_description_text' => $values['description_of_video']['value'],
        'video_description_text_format' => $values['description_of_video']['format'],
        'script_file_link' => $values['link_to_script_file'],
        'video_link' => $values['link_to_video'],
        'video_visibility' => $values['lecture_visibility'],
        'creation_date' => time(),
      ])
      ->execute();

    $this->messenger()->addStatus($this->t('Video has been added successfully.'));

    // Redirect back to the add form (or another route if you prefer).
    $form_state->setRedirectUrl(Url::fromUserInput('/lecture-videos/add'));
  }

}
