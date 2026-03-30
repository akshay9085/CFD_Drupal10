<?php
function verify_lab_migration_certificates($qr_code = 0)
	{
		$qr_code = arg(3);
		$page_content = "";
		if ($qr_code)
			{
				$page_content = verify_qrcode_lm_fromdb($qr_code);
			} //$qr_code
		else
			{
				$verify_certificates_form = drupal_get_form("verify_lab_migration_certificates_form");
				$page_content = drupal_render($verify_certificates_form);
			}
		return $page_content;
	}
function verify_lab_migration_certificates_form($form, &$form_state)
	{
		$form = array();
		$form['Title'] = array(
				'#type' => 'markup',
				'#markup' => ''
		);
		$form["QR_code"] = array(
				"#type" => "textfield",
				"#title" => "Enter QR Code",
				"#default_value" => '',
				"#required" => TRUE
		);
		$form["submit"] = array(
				"#type" => "submit",
				"#value" => "Verify",
				'#ajax' => array(
						'callback' => 'verify_lab_migration_certificates_form_submit',
						'progress' => array(
								'message' => ''
						)
				)
		);
		$form['displaytable'] = array(
				'#type' => 'markup',
				'#prefix' => '<div><div id="displaytable" style="font-weight:bold;padding-top:10px">',
				'#suffix' => '</div></div>',
				'#markup' => ''
		);
		return $form;
	}
function verify_lab_migration_certificates_form_submit($form, &$form_state)
	{
		$page_content = "";
		$v = $form_state["values"];
		$qr_code = $v["QR_code"];
		$page_content = verify_qrcode_lm_fromdb($qr_code);
		$form['displaytable']['#markup'] = $page_content;
		$commands[] = ajax_command_html("#displaytable", drupal_render($form['displaytable']));
		return array(
				'#type' => 'ajax',
				'#commands' => $commands
		);
	}
function verify_qrcode_lm_fromdb($qr_code)
	{
		$query = db_select('lab_migration_certificate_qr_code');
		$query->fields('lab_migration_certificate_qr_code');
		$query->condition('qr_code', $qr_code);
		$result = $query->execute()->fetchObject();
		$proposal_id = $result->proposal_id;
		$certificate_id = $result->certificate_id;
		if ($proposal_id)
			{
				$query2 = db_query("SELECT * FROM {lab_migration_certificate} WHERE proposal_id= :prop_id AND id=:certificate_id", array(
						':prop_id' => $proposal_id,
						':certificate_id' => $certificate_id
				));
				$data2 = $query2->fetchObject();
				if ($data2->type == "Proposer")
					{
						$page_content = "";
						$page_content .= "<h4>Participation Details</h4><table>";
						//$page_content.="<tr><td>Name</td>";
						//$page_content.="<td>".$data2->name_title. ' '.$data2->name."</td></tr>";
						$page_content .= "<tr><td>Project</td>";
						$page_content .= "<td>CFD Lab Migration Project</td></tr>";
						$page_content .= "<tr><td>Lab completed</td>";
						$page_content .= "<td>" . $data2->lab_name . "</td></tr>";
						$page_content .= "<tr><td>Department</td>";
						$page_content .= "<td>" . $data2->department . "</td></tr>";
						$page_content .= "<tr><td>Semester Details</td>";
						$page_content .= "<td>" . $data2->semester_details . "</td></tr>";
						$page_content .= "<tr><td>Institute Name</td>";
						$page_content .= "<td>" . $data2->institute_name . "</td></tr>";
						$page_content .= "<tr><td>Institute Address</td>";
						$page_content .= "<td>" . $data2->institute_address . "</td></tr>";
						$page_content .= "</table>";
					} //$data2->type == "Proposer"
				elseif ($data2->type == "Participant")
					{
						$page_content = "";
						$page_content .= "<h4>Participation Details</h4><table>";
						//$page_content.="<tr><td>Name</td>";
						//$page_content.="<td>".$data2->name_title. ' '.$data2->name."</td></tr>";
						$page_content .= "<tr><td>Project</td>";
						$page_content .= "<td>CFD Lab Migration Project</td></tr>";
						$page_content .= "<tr><td>Contributed to complete the lab</td>";
						$page_content .= "<td>" . $data2->lab_name . "</td></tr>";
						$page_content .= "<tr><td>Participant</td>";
						$page_content .= "<td>" . $data2->name_title . " " . $data2->name . "</td></tr>";
						$page_content .= "<tr><td>Department</td>";
						$page_content .= "<td>" . $data2->department . "</td></tr>";
						$page_content .= "<tr><td>Semester Details</td>";
						$page_content .= "<td>" . $data2->semester_details . "</td></tr>";
						$page_content .= "<tr><td>Institute Name</td>";
						$page_content .= "<td>" . $data2->institute_name . "</td></tr>";
						$page_content .= "<tr><td>Institute Address</td>";
						$page_content .= "<td>" . $data2->institute_address . "</td></tr>";
						$page_content .= "</table>";
					} //$data2->type == "Participant"
				else
					{
						$page_content = "<b>Sorry ! The serial number you entered seems to be invalid. Please try again ! <b>";
					}
				return $page_content;
			} //$proposal_id
	}
