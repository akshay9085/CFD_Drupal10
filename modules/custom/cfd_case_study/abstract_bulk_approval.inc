<?php

function cfd_case_study_abstract_bulk_approval_form($form, &$form_state)
{
	$options_first = _bulk_list_of_case_study_project();
	$selected = isset($form_state['values']['case_study_project']) ? $form_state['values']['case_study_project'] : key($options_first);
	$form = array();
	$form['case_study_project'] = array(
		'#type' => 'select',
		'#title' => t('Title of the case study project'),
		'#options' => _bulk_list_of_case_study_project(),
		'#default_value' => $selected,
		'#ajax' => array(
			
			'callback' => 'ajax_bulk_case_study_abstract_details_callback'
		),
		'#suffix' => '<div id="ajax_selected_case_study"></div><div id="ajax_selected_case_study_pdf"></div>'
	);
	$form['case_study_actions'] = array(
		'#type' => 'select',
		'#title' => t('Please select action for case study project'),
		'#options' => _bulk_list_case_study_actions(),
		'#default_value' => 0,
		'#prefix' => '<div id="ajax_selected_case_study_action" style="color:red;">',
		'#suffix' => '</div>',
		'#states' => array(
			'invisible' => array(
				':input[name="case_study_project"]' => array(
					'value' => 0
				)
			)
		)
	);
	$form['message'] = array(
		'#type' => 'textarea',
		'#title' => t('If Dis-Approved please specify reason for Dis-Approval'),
		'#prefix' => '<div id= "message_submit">',
		'#states' => array(
			'visible' => array(
				array(
					':input[name="case_study_actions"]' => array(
						'value' => 3
					)
				),
				'or',
				array(
					':input[name="case_study_actions"]' => array(
						'value' => 4
					)
				)
			)
		)
	);
	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Submit'),
		'#states' => array(
			'invisible' => array(
				':input[name="lab"]' => array(
					'value' => 0
				)
			)
		)
	);
	return $form;
}
function ajax_bulk_case_study_abstract_details_callback($form, $form_state)
{
	$commands = array();
	$case_study_project_default_value = $form_state['values']['case_study_project'];
	if ($case_study_project_default_value != 0)
	{
		$commands[] = ajax_command_html('#ajax_selected_case_study', _case_study_details($case_study_project_default_value));
		$form['case_study_actions']['#options'] = _bulk_list_case_study_actions();
		$commands[] = ajax_command_replace('#ajax_selected_case_study_action', \Drupal::service("renderer")->render($form['case_study_actions']));
	} //$case_study_project_default_value != 0
	else
	{
		$commands[] = ajax_command_html('#ajax_selected_case_study', '');
		$commands[] = ajax_command_data('#ajax_selected_case_study', 'form_state_value_select', $form_state['values']['case_study_project']);
	}
	return array(
		'#type' => 'ajax',
		'#commands' => $commands
	);
}
/************************************************************/
function cfd_case_study_abstract_bulk_approval_form_submit($form, &$form_state)
{
	$user = \Drupal::currentUser();
	$msg = '';
	$root_path = cfd_case_study_path();
	//var_dump($root_path);die;
	if ($form_state['clicked_button']['#value'] == 'Submit')
	{
		if ($form_state['values']['case_study_project'])
			//var_dump($form_state['values']['case_study_actions']);die;
		// case_study_abstract_del_lab_pdf($form_state['values']['case_study_project']);
			if (\Drupal::currentUser()->hasPermission('Case Study bulk manage abstract'))
			{
				$query = \Drupal::database()->select('case_study_proposal');
				$query->fields('case_study_proposal');
				$query->condition('id', $form_state['values']['case_study_project']);
				$user_query = $query->execute();
				$user_info = $user_query->fetchObject();
				//var_dump($user_info);die;
				$user_data = \Drupal::entityTypeManager()->getStorage('user')->load($user_info->uid);
				if ($form_state['values']['case_study_actions'] == 1)
				{
					// approving entire project //
					$query = \Drupal::database()->select('case_study_submitted_abstracts');
					$query->fields('case_study_submitted_abstracts');
					$query->condition('proposal_id', $form_state['values']['case_study_project']);
					$abstracts_q = $query->execute();
					//var_dump($abstracts_q);die;
					$experiment_list = '';
					while ($abstract_data = $abstracts_q->fetchObject())
					{
						\Drupal::database()->query("UPDATE {case_study_submitted_abstracts} SET abstract_approval_status = 1, is_submitted = 1, approver_uid = :approver_uid WHERE id = :id", array(
							':approver_uid' => $user->uid,
							':id' => $abstract_data->id
						));
						\Drupal::database()->query("UPDATE {case_study_submitted_abstracts_file} SET file_approval_status = 1, approvar_uid = :approver_uid WHERE submitted_abstract_id = :submitted_abstract_id", array(
							':approver_uid' => $user->uid,
							':submitted_abstract_id' => $abstract_data->id
						));
					} //$abstract_data = $abstracts_q->fetchObject()
					\Drupal::messenger()->addStatus(t('Approved case study project.'));
					// email 
					// @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_subject = t('[!site_name][case study Project] Your uploaded case study project have been approved', array(
// 						'!site_name' => variable_get('site_name', '')
// 					));

					// @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_body = array(
// 						0 => t('
// 
// Dear !user_name,
// 
// Your uploaded abstract for the case study project has been approved:
// 
// Title of case study project  : ' . $user_info->project_title . '
// 
// Best Wishes,
// 
// !site_name Team,
// FOSSEE,IIT Bombay', array(
// 							'!site_name' => variable_get('site_name', ''),
// 							'!user_name' => $user_data->name
// 						))
// 					);

					/** sending email when everything done **/
					$email_to = $user_data->mail;
					// @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $from = variable_get('case_study_from_email', '');

					// @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $bcc = variable_get('case_study_emails', '');

					// @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $cc = variable_get('case_study_cc_emails', '');

					$params['standard']['subject'] = $email_subject;
					$params['standard']['body'] = $email_body;
					$params['standard']['headers'] = array(
						'From' => $from,
						'MIME-Version' => '1.0',
						'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
						'Content-Transfer-Encoding' => '8Bit',
						'X-Mailer' => 'Drupal',
						'Cc' => $cc,
						'Bcc' => $bcc
					);
					if (!drupal_mail('cfd_case_study', 'standard', $email_to, language_default(), $params, $from, TRUE))
					{
						$msg = \Drupal::messenger()->addError('Error sending email message.');
					} //!drupal_mail('cfd_case_study', 'standard', $email_to, language_default(), $params, $from, TRUE)
				} //$form_state['values']['case_study_actions'] == 1
				elseif ($form_state['values']['case_study_actions'] == 2)
				{
					//pending review entire project 
					$query = \Drupal::database()->select('case_study_submitted_abstracts');
					$query->fields('case_study_submitted_abstracts');
					$query->condition('proposal_id', $form_state['values']['case_study_project']);
					$abstracts_q = $query->execute();
					$experiment_list = '';
					while ($abstract_data = $abstracts_q->fetchObject())
					{
						\Drupal::database()->query("UPDATE {case_study_submitted_abstracts} SET abstract_approval_status = 0, is_submitted = 0, approver_uid = :approver_uid WHERE id = :id", array(
							':approver_uid' => $user->uid,
							':id' => $abstract_data->id
						));
						\Drupal::database()->query("UPDATE {case_study_proposal} SET is_submitted = 0, approver_uid = :approver_uid WHERE id = :id", array(
							':approver_uid' => $user->uid,
							':id' => $abstract_data->proposal_id
						));
						\Drupal::database()->query("UPDATE {case_study_submitted_abstracts_file} SET file_approval_status = 0, approvar_uid = :approver_uid WHERE submitted_abstract_id = :submitted_abstract_id", array(
							':approver_uid' => $user->uid,
							':submitted_abstract_id' => $abstract_data->id
						));
					} //$abstract_data = $abstracts_q->fetchObject()
					\Drupal::messenger()->addStatus(t('Resubmit the project files'));
					// email 
					// @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_subject = t('[!site_name][case study Project] Your uploaded case study project have been marked as pending', array(
// 						'!site_name' => variable_get('site_name', '')
// 					));

					// @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_body = array(
// 						0 => t('
// 
// Dear !user_name,
// 
// Kindly resubmit the project files for the project : ' . $user_info->project_title . '.
// 
// 
// Best Wishes,
// 
// !site_name Team,
// FOSSEE,IIT Bombay', array(
// 							'!site_name' => variable_get('site_name', ''),
// 							'!user_name' => $user_data->name
// 						))
// 					);

					/** sending email when everything done **/
					$email_to = $user_data->mail;
					// @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $from = variable_get('case_study_from_email', '');

					// @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $bcc = variable_get('case_study_emails', '');

					// @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $cc = variable_get('case_study_cc_emails', '');

					$params['standard']['subject'] = $email_subject;
					$params['standard']['body'] = $email_body;
					$params['standard']['headers'] = array(
						'From' => $from,
						'MIME-Version' => '1.0',
						'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
						'Content-Transfer-Encoding' => '8Bit',
						'X-Mailer' => 'Drupal',
						'Cc' => $cc,
						'Bcc' => $bcc
					);
					if (!drupal_mail('cfd_case_study', 'standard', $email_to, language_default(), $params, $from, TRUE))
					{
						\Drupal::messenger()->addError('Error sending email message.');
					} //!drupal_mail('cfd_case_study', 'standard', $email_to, language_default(), $params, $from, TRUE)
				} //$form_state['values']['case_study_actions'] == 2
				elseif ($form_state['values']['case_study_actions'] == 3) //disapprove and delete entire case study project
				{
					if (strlen(trim($form_state['values']['message'])) <= 30)
					{
						form_set_error('message', t(''));
						$msg = \Drupal::messenger()->addError("Please mention the reason for disapproval. Minimum 30 character required");
						return $msg;
					} //strlen(trim($form_state['values']['message'])) <= 30
					if (!\Drupal::currentUser()->hasPermission('Case Study bulk delete abstract'))
					{
						$msg = \Drupal::messenger()->addError(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Lab.'));
						return $msg;
					} //!user_access('case_study bulk delete code')
					if (case_study_abstract_delete_project($form_state['values']['case_study_project'])) //////
					{
						\Drupal::messenger()->addStatus(t('Dis-Approved and Deleted Entire case study project.'));
						// @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_subject = t('[!site_name][case study Project] Your uploaded case study project have been marked as dis-approved', array(
// 						'!site_name' => variable_get('site_name', '')
// 					));

					// @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_body = array(
// 						0 => t('
// Dear !user_name,
// 
// Your uploaded case study project files for the case study project Title : ' . $user_info->project_title . ' have been marked as dis-approved.
// 
// Reason for dis-approval: ' . $form_state['values']['message'] . '
// 
// Best Wishes,
// 
// !site_name Team,
// FOSSEE,IIT Bombay', array(
// 						'!site_name' => variable_get('site_name', ''),
// 						'!user_name' => $user_data->name
// 											))
// 					);

					$email_to = $user_data->mail;
					// @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $from = variable_get('case_study_from_email', '');

					// @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $bcc = variable_get('case_study_emails', '');

					// @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $cc = variable_get('case_study_cc_emails', '');

					$params['standard']['subject'] = $email_subject;
					$params['standard']['body'] = $email_body;
					$params['standard']['headers'] = array(
						'From' => $from,
						'MIME-Version' => '1.0',
						'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
						'Content-Transfer-Encoding' => '8Bit',
						'X-Mailer' => 'Drupal',
						'Cc' => $cc,
						'Bcc' => $bcc
					);
					if (!drupal_mail('cfd_case_study', 'standard', $email_to, language_default(), $params, $from, TRUE))
					{
						\Drupal::messenger()->addError('Error sending email message.');
					}
					} //case_study_abstract_delete_project($form_state['values']['case_study_project'])
					else
					{
						\Drupal::messenger()->addError(t('Error Dis-Approving and Deleting Entire case study project.'));
					}
					// email 
					
				} //$form_state['values']['case_study_actions'] == 3
				
			} //user_access('case_study project bulk manage code')
		return $msg;
	} //$form_state['clicked_button']['#value'] == 'Submit'
}
/**********************************************************/
function _bulk_list_of_case_study_project()
{
	$project_titles = array(
		'0' => 'Please select...'
	);
	$query = \Drupal::database()->select('case_study_proposal');
	$query->fields('case_study_proposal');
	$query->condition('is_submitted', 1);
	$query->condition('approval_status', 1);
	$query->orderBy('project_title', 'ASC');
	$project_titles_q = $query->execute();
	while ($project_titles_data = $project_titles_q->fetchObject())
	{
		$project_titles[$project_titles_data->id] = $project_titles_data->project_title . ' (Proposed by ' . $project_titles_data->contributor_name . ')';
	} //$project_titles_data = $project_titles_q->fetchObject()
	return $project_titles;
}
function _bulk_list_case_study_actions()
{
	$case_study_actions = array(
		0 => 'Please select...'
	);
	$case_study_actions[1] = 'Approve Entire case study Project';
	$case_study_actions[2] = 'Resubmit Project files';
	$case_study_actions[3] = 'Dis-Approve Entire case study Project (This will delete case study Project)';
	//$case_study_actions[4] = 'Delete Entire case study Project Including Proposal';
	return $case_study_actions;
}
function _case_study_details($case_study_proposal_id)
{
	$return_html = "";
	$download_case_study = '';
	$query_pro = \Drupal::database()->select('case_study_proposal');
	$query_pro->fields('case_study_proposal');
	$query_pro->condition('id', $case_study_proposal_id);
	$abstracts_pro = $query_pro->execute()->fetchObject();
	//var_dump($abstracts_pro);die;
	$query_pdf = \Drupal::database()->select('case_study_submitted_abstracts_file');
	$query_pdf->fields('case_study_submitted_abstracts_file');
	$query_pdf->condition('proposal_id', $case_study_proposal_id);
	$query_pdf->condition('filetype', 'A');
	$abstracts_pdf = $query_pdf->execute()->fetchObject();
	if ($abstracts_pdf == TRUE)
	{
		if ($abstracts_pdf->filename != "NULL" || $abstracts_pdf->filename != "")
		{
			$abstract_filename = $abstracts_pdf->filename;
		} //$abstracts_pdf->filename != "NULL" || $abstracts_pdf->filename != ""
		else
		{
			$abstract_filename = "File not uploaded";
		}
	} //$abstracts_pdf == TRUE
	else
	{
		$abstract_filename = "File not uploaded";
	}
	$query_process = \Drupal::database()->select('case_study_submitted_abstracts_file');
	$query_process->fields('case_study_submitted_abstracts_file');
	$query_process->condition('proposal_id', $case_study_proposal_id);
	$query_process->condition('filetype', 'S');
	$abstracts_query_process = $query_process->execute()->fetchObject();
	$query = \Drupal::database()->select('case_study_submitted_abstracts');
	$query->fields('case_study_submitted_abstracts');
	$query->condition('proposal_id', $case_study_proposal_id);
	$abstracts_q = $query->execute()->fetchObject();
	if ($abstracts_q)
	{
		if ($abstracts_q->is_submitted == 0)
		{
			//drupal_set_message(t('Abstract is not submmited yet.'), 'error', $repeat = FALSE);
			//return;
		} //$abstracts_q->is_submitted == 0
	} //$abstracts_q
	//var_dump($abstracts_query_process);die;
	if ($abstracts_query_process == TRUE)
	{
		if ($abstracts_query_process->filename != "NULL" || $abstracts_query_process->filename != "")
		{
			$abstracts_query_process_filename = $abstracts_query_process->filename;
		} //$abstracts_query_process->filename != "NULL" || $abstracts_query_process->filename != ""
		else
		{
			$abstracts_query_process_filename = "File not uploaded";
		}
	} //$abstracts_query_process == TRUE
	else
	{
		// @FIXME
// l() expects a Url object, created from a route name or external URI.
// $url = l('Upload abstract', 'case-study-project/abstract-code/upload');

		$abstracts_query_process_filename = "File not uploaded";
	}
	// @FIXME
// l() expects a Url object, created from a route name or external URI.
// $download_case_study = l('Download case study project','case-study-project/full-download/project/'.$case_study_proposal_id);
	$download_case_study = \Drupal\Core\Link::fromTextAndUrl(
		t('Download case study project'),
		\Drupal\Core\Url::fromRoute('cfd_case_study.download_full_project', [], [
			'query' => ['id' => $case_study_proposal_id],
		])
	)->toString();

	$return_html .= '<strong>Proposer Name:</strong><br />' . $abstracts_pro->name_title . ' ' . $abstracts_pro->contributor_name . '<br /><br />';
	$return_html .= '<strong>Title of the case study Project:</strong><br />' . $abstracts_pro->project_title . '<br /><br />';
	$return_html .= '<strong>Uploaded an abstract (brief outline) of the project:</strong><br />' . $abstract_filename . '<br /><br />';
	$return_html .= '<strong>Uploaded Case Directory Folder:</strong><br />' . $abstracts_query_process_filename . '<br /><br />';
	$return_html .= '<strong>Download Case Study Project:</strong><br />' . $download_case_study;
	return $return_html;
}
