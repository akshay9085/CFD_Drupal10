<?php
function cfd_case_study_project_files() {
	$proposal_id = arg(3);
	$root_path = cfd_case_study_path();
	$query = \Drupal::database()->select('case_study_submitted_abstracts_file');
	$query->fields('case_study_submitted_abstracts_file');
	$query->condition('proposal_id', $proposal_id);
	$query->condition('filetype', 'A');
	$result = $query->execute();
	$cfd_case_study_project_files = $result->fetchObject();
	$query1 = \Drupal::database()->select('case_study_proposal');
	$query1->fields('case_study_proposal');
	$query1->condition('id', $proposal_id);
	$result1 = $query1->execute();
	$case_study = $result1->fetchObject();
	$directory_name = $case_study->directory_name . '/';
	$abstract_file = $cfd_case_study_project_files->filename;
	ob_clean();
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: public");
	header("Content-Description: File Transfer");
	header("Content-Type: application/pdf");
	header('Content-disposition: attachment; filename="' . $abstract_file . '"');
	header("Content-Length: " . filesize($root_path . $directory_name . $abstract_file));
	header("Content-Transfer-Encoding: binary");
	header("Expires: 0");
	header("Pragma: no-cache");
	readfile($root_path . $directory_name . $abstract_file);
	ob_end_flush();
	ob_clean();
}
