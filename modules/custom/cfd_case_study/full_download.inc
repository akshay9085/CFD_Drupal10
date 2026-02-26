<?php
// $Id$
function cfd_case_study_download_full_project()
{
	$user = \Drupal::currentUser();
	$id = arg(3);
	$root_path = cfd_case_study_path();
	//var_dump($root_path);die;
	$query = \Drupal::database()->select('case_study_proposal');
	$query->fields('case_study_proposal');
	$query->condition('id', $id);
	$case_study_q = $query->execute();
	$case_study_data = $case_study_q->fetchObject();
	$CASE_STUDY_PATH = $case_study_data->directory_name . '/';
	/* zip filename */
	$zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
	/* creating zip archive on the server */
	$zip = new ZipArchive();
	$zip->open($zip_filename, ZipArchive::CREATE);
	$query = \Drupal::database()->select('case_study_proposal');
	$query->fields('case_study_proposal');
	$query->condition('id', $id);
	$circuit_simulation_udc_q = $query->execute();
	$query = \Drupal::database()->select('case_study_proposal');
	$query->fields('case_study_proposal');
	$query->condition('id', $id);
	$query = \Drupal::database()->select('case_study_submitted_abstracts_file');
	$query->fields('case_study_submitted_abstracts_file');
	$query->condition('proposal_id', $id);
	$project_files = $query->execute();
	while ($cfd_project_files = $project_files->fetchObject()) {
		$zip->addFile($root_path . $CASE_STUDY_PATH . $cfd_project_files->filepath, $CASE_STUDY_PATH . str_replace(' ', '_', basename($cfd_project_files->filename)));
	}
	$zip_file_count = $zip->numFiles;
	$zip->close();
	if ($zip_file_count > 0) {
		if ($user->uid) {
			/* download zip file */
			header('Content-Type: application/zip');
			header('Content-disposition: attachment; filename="' . str_replace(' ', '_', $case_study_data->project_title) . '.zip"');
			header('Content-Length: ' . filesize($zip_filename));
			ob_end_flush();
			ob_clean();
			flush();
			readfile($zip_filename);
			unlink($zip_filename);
		} //$user->uid
		else {
			header('Content-Type: application/zip');
			header('Content-disposition: attachment; filename="' . str_replace(' ', '_', $case_study_data->project_title) . '.zip"');
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
	} //$zip_file_count > 0
	else {
		\Drupal::messenger()->addError("There are no case study project in this proposal to download");
		drupal_goto('circuit-simulation-project/full-download/project');
	}
}
/*
function circuit_simulation_download_completed_project()
{
		$user = \Drupal::currentUser();
	$id = arg(3);
	$root_path = cfd_case_study_path();
	$query = db_select('case_study_proposal');
	$query->fields('case_study_proposal');
	$query->condition('id', $id);
	$case_study_q = $query->execute();
	$case_study_data = $case_study_q->fetchObject();
	$CASE_STUDY_PATH = $case_study_data->directory_name . '/';
	/* zip filename 
	$zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
	/* creating zip archive on the server 
	$zip = new ZipArchive();
	$zip->open($zip_filename, ZipArchive::CREATE);
	$query = db_select('case_study_proposal');
	$query->fields('case_study_proposal');
	$query->condition('id', $id);
	$circuit_simulation_udc_q = $query->execute();
	$query = db_select('case_study_proposal');
	$query->fields('case_study_proposal');
	$query->condition('id', $id);
	$query = db_select('case_study_submitted_abstracts_file');
	$query->fields('case_study_submitted_abstracts_file');
	$query->condition('proposal_id', $id);
	$project_files = $query->execute();
	//var_dump($root_path . $CASE_STUDY_PATH . 'project_files/');die;
	while ($cfd_project_files = $project_files->fetchObject()) {
		$zip->addFile($root_path . $CASE_STUDY_PATH . 'project_files/' . $cfd_project_files->filepath, $CASE_STUDY_PATH . str_replace(' ', '_', basename($cfd_project_files->filename)));
	}
	$zip_file_count = $zip->numFiles;
	$zip->close();
	if ($zip_file_count > 0) {
		if ($user->uid) {
			/* download zip file 
			header('Content-Type: application/zip');
			header('Content-disposition: attachment; filename="' . str_replace(' ', '_', $case_study_data->project_title) . '.zip"');
			header('Content-Length: ' . filesize($zip_filename));
			ob_end_flush();
			ob_clean();
			flush();
			readfile($zip_filename);
			unlink($zip_filename);
		} //$user->uid
		else {
			header('Content-Type: application/zip');
			header('Content-disposition: attachment; filename="' . str_replace(' ', '_', $case_study_data->project_title) . '.zip"');
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
	} //$zip_file_count > 0
	else {
		drupal_set_message("There are circuit simulation project in this proposal to download", 'error');
		drupal_goto('circuit-simulation-project/full-download/project');
	}
}*/
