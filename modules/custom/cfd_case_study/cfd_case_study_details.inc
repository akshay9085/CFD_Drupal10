<?php
// case study display completed proposals
function cfd_case_study_completed_proposals_all()
{
	$output = "";
	$query = \Drupal::database()->select('case_study_proposal');
	$query->fields('case_study_proposal');
	$query->condition('approval_status', 3);
	$query->orderBy('actual_completion_date', 'DESC');
	//$query->condition('is_completed', 1);
	$result = $query->execute();
	
	//var_dump($case_study_abstract);die;
	if ($result->rowCount() == 0)
	{
		$output .= "Work has been completed for the following case studies. We welcome your contributions." ."<hr>";

	} //$result->rowCount() == 0
	else
	{
		$output .= "Work has been completed for the following case studies. We welcome your contributions." . "<hr>";
		$preference_rows = array();
		$i = $result->rowCount();
		//var_dump($i);die;
		while ($row = $result->fetchObject())
		{
			$proposal_id = $row->id;
			$query1 = \Drupal::database()->select('case_study_submitted_abstracts_file');
			$query1->fields('case_study_submitted_abstracts_file');
			$query1->condition('file_approval_status', 1);
			$query1->condition('proposal_id', $proposal_id);
			$case_study_files = $query1->execute();
			$case_study_abstract = $case_study_files->fetchObject();
			$solver_used = $row->solver_used;
			// @FIXME
// l() expects a Url object, created from a route name or external URI.
// $project_title = l($row->project_title, "case-study-project/case-study-run/" . $row->id) . t("<br><strong>(Solver used: ") . $solver_used . t(")</strong>") ;

			$year = date("Y", $row->actual_completion_date);
			$preference_rows[] = array(
				$i,
				$project_title,
				//$solver_used,
				$row->contributor_name,
				$row->university,
				$year
			);
			$i--;
		} //$row = $result->fetchObject()
		$preference_header = array(
			'No',
			'Case Study Project',
			//'Solver used',
			'Contributor Name',
			'University/ Institute',
			'Year of Completion'
		);
		// @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
// 
// 
// @see https://www.drupal.org/node/2195739
// $output .= theme('table', array(
// 			'header' => $preference_header,
// 			'rows' => $preference_rows
// 		));

	}
	return $output;
}
// case study display in progress proposals
function cfd_case_study_progress_all()
{
	$page_content = "";
	$query = \Drupal::database()->select('case_study_proposal');
	$query->fields('case_study_proposal');
	$query->condition('approval_status', 1);
	$query->condition('is_completed', 0);
	$query->orderBy('approval_date',DESC);
	$result = $query->execute();
	if ($result->rowCount() == 0)
	{
		$page_content .= "Work is in progress for the following case studies under case study Project<hr>";
	} //$result->rowCount() == 0
	else
	{
		$page_content .= "Work is in progress for the following case studies under case study Project<hr>";
		$preference_rows = array();
		$i = $result->rowCount();
		while ($row = $result->fetchObject())
		{
			$approval_date = date("Y", $row->approval_date);
			$preference_rows[] = array(
				$i,
				$row->project_title,
				$row->contributor_name,
				$row->university,
				$approval_date
			);
			$i--;
		} //$row = $result->fetchObject()
		$preference_header = array(
			'No',
			'Case Study Project',
			'Contributor Name',
			'Institute/ University',
			'Year'
		);
		// @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
// 
// 
// @see https://www.drupal.org/node/2195739
// $page_content .= theme('table', array(
// 			'header' => $preference_header,
// 			'rows' => $preference_rows
// 		));

	}
	return $page_content;
}
