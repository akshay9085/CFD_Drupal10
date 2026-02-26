<?php
// $Id$
function cfd_case_study_abstract()
{
    $user = \Drupal::currentUser();
    $return_html = "";
    $proposal_data = cfd_case_study_get_proposal();
    if (!$proposal_data) {
        drupal_goto('');
        return;
    } //!$proposal_data
    //$return_html .= l('Upload abstract', 'case-study-project/abstract-code/upload') . '<br />';
    /* get experiment list */
    $query = \Drupal::database()->select('case_study_submitted_abstracts');
    $query->fields('case_study_submitted_abstracts');
    $query->condition('proposal_id', $proposal_data->id);
    $abstracts_q = $query->execute()->fetchObject();
    $query_pro = \Drupal::database()->select('case_study_proposal');
    $query_pro->fields('case_study_proposal');
    $query_pro->condition('id', $proposal_data->id);
    $abstracts_pro = $query_pro->execute()->fetchObject();
    $query_pdf = \Drupal::database()->select('case_study_submitted_abstracts_file');
    $query_pdf->fields('case_study_submitted_abstracts_file');
    $query_pdf->condition('proposal_id', $proposal_data->id);
    $query_pdf->condition('filetype', 'A');
    $abstracts_pdf = $query_pdf->execute()->fetchObject();
    if ($abstracts_pdf == true) {
        if ($abstracts_pdf->filename != "NULL" || $abstracts_pdf->filename != "") {
            $abstract_filename = $abstracts_pdf->filename;
            //$abstract_filename = l($abstracts_pdf->filename, 'case-study-project/download/project-file/' . $proposal_data->id);
        } //$abstracts_pdf->filename != "NULL" || $abstracts_pdf->filename != ""
        else {
            $abstract_filename = "File not uploaded";
        }
    } //$abstracts_pdf == TRUE
    else {
        $abstract_filename = "File not uploaded";
    }
    $query_process = \Drupal::database()->select('case_study_submitted_abstracts_file');
    $query_process->fields('case_study_submitted_abstracts_file');
    $query_process->condition('proposal_id', $proposal_data->id);
    $query_process->condition('filetype', 'S');
    $abstracts_query_process = $query_process->execute()->fetchObject();
    if ($abstracts_query_process == true) {
        if ($abstracts_query_process->filename != "NULL" || $abstracts_query_process->filename != "") {
            $abstracts_query_process_filename = $abstracts_query_process->filename;
            //$abstracts_query_process_filename = l($abstracts_query_process->filename, 'case-study-project/download/project-file/' . $proposal_data->id);
        } //$abstracts_query_process->filename != "NULL" || $abstracts_query_process->filename != ""
        else {
            $abstracts_query_process_filename = "File not uploaded";
        }
        if ($abstracts_q->is_submitted == '') {
            // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $url = l('Upload Case Directory', 'case-study-project/abstract-code/upload');

        } //$abstracts_q->is_submitted == ''
        else if ($abstracts_q->is_submitted == 1) {
            $url = "";
        } //$abstracts_q->is_submitted == 1
        else if ($abstracts_q->is_submitted == 0) {
            // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $url = l('Edit', 'case-study-project/abstract-code/upload');

        } //$abstracts_q->is_submitted == 0
    } //$abstracts_query_process == TRUE
    else {
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $url = l('Upload Case Directory', 'case-study-project/abstract-code/upload');

        $abstracts_query_process_filename = "File not uploaded";
    }
    $return_html .= '<strong>Contributor Name:</strong><br />' . $proposal_data->name_title . ' ' . $proposal_data->contributor_name . '<br /><br />';
    $return_html .= '<strong>Title of the Case Study Project:</strong><br />' . $proposal_data->project_title . '<br /><br />';
    $return_html .= '<strong>Uploaded abstract of the project:</strong><br />' . $abstract_filename . '<br /><br />';
    $return_html .= '<strong>Uploaded Case Directory:</strong><br />' . $abstracts_query_process_filename . '<br /><br />';
    $return_html .= $url . '<br />';
    return $return_html;
}
function cfd_case_study_upload_abstract_code_form($form, &$form_state)
{
    $user = \Drupal::currentUser();
    $form['#attributes'] = array(
        'enctype' => "multipart/form-data",
    );
    /* get current proposal */
    //$proposal_id = (int) arg(3);
    $uid = $user->uid;
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('uid', $uid);
    $query->condition('approval_status', '1');
    $proposal_q = $query->execute();
    if ($proposal_q) {
        if ($proposal_data = $proposal_q->fetchObject()) {
            /* everything ok */
        } //$proposal_data = $proposal_q->fetchObject()
        else {
            \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
            drupal_goto('case-study-project/abstract-code');
            return;
        }
    } //$proposal_q
    else {
        \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
        drupal_goto('case-study-project/abstract-code');
        return;
    }
    $query = \Drupal::database()->select('case_study_submitted_abstracts');
    $query->fields('case_study_submitted_abstracts');
    $query->condition('proposal_id', $proposal_data->id);
    $abstracts_q = $query->execute()->fetchObject();
    if ($abstracts_q) {
        if ($abstracts_q->is_submitted == 1) {
            \Drupal::messenger()->addError(t('You have already submited your Case Directory, hence you can not upload any more, for any query please write to us.'));
            drupal_goto('case-study-project/abstract-code');
            //return;
        } //$abstracts_q->is_submitted == 1
    } //$abstracts_q->is_submitted == 1
    $form['project_title'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->project_title,
        '#title' => t('Title of the Case Study Project'),
    );
    $form['contributor_name'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->contributor_name,
        '#title' => t('Contributor Name'),
    );
    $existing_uploaded_S_file = default_value_for_uploaded_files("S", $proposal_data->id);
    if (!$existing_uploaded_S_file) {
        $existing_uploaded_S_file = new stdClass();
        $existing_uploaded_S_file->filename = "No file uploaded";
    } //!$existing_uploaded_S_file
    // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $form['upload_case_study_developed_process'] = array(
//         '#type' => 'file',
//         '#title' => t('Upload the Case Directory'),
//         //'#required' => TRUE,
//         '#description' => t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_S_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . variable_get('case_study_project_files_extensions', '') . '</span>',
//     );

    $form['prop_id'] = array(
        '#type' => 'hidden',
        '#value' => $proposal_data->id,
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
        '#submit' => array(
            'cfd_case_study_upload_abstract_code_form_submit',
        ),
    );
    // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $form['cancel'] = array(
//         '#type' => 'item',
//         '#markup' => l(t('Cancel'), 'case-study-project/abstract-code'),
//     );

    return $form;
}

///////////////////////////////////////////////////////////////
function cfd_case_study_upload_abstract_code_form_validate($form, &$form_state)
{
    //var_dump($form);die;
    if (isset($_FILES['files'])) {
        /* check if atleast one source or result file is uploaded */
        if (!($_FILES['files']['name']['upload_case_study_developed_process'])) {
            form_set_error('upload_case_study_developed_process', t('Please upload the abstract file'));
        }

        /* check for valid filename extensions */
        foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
            if ($file_name) {
                /* checking file type */
                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $allowed_extensions_str = variable_get('case_study_project_files_extensions', '');

                $allowed_extensions = explode(',', $allowed_extensions_str);
                $fnames = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
                $temp_extension = end($fnames);
                if (!in_array($temp_extension, $allowed_extensions)) {
                    form_set_error($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
                }

                if ($_FILES['files']['size'][$file_form_name] <= 0) {
                    form_set_error($file_form_name, t('File size cannot be zero.'));
                }

                /* check if valid file name */
                if (!cfd_case_study_check_valid_filename($_FILES['files']['name'][$file_form_name])) {
                    form_set_error($file_form_name, t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
                }

            } //$file_name
        } //$_FILES['files']['name'] as $file_form_name => $file_name
    }
    return $form_state;
}
function cfd_case_study_upload_abstract_code_form_submit($form, &$form_state)
{
    $user = \Drupal::currentUser();
    $v = $form_state['values'];
    $root_path = cfd_case_study_path();
    $proposal_data = cfd_case_study_get_proposal();
    $proposal_id = $proposal_data->id;
    if (!$proposal_data) {
        drupal_goto('');
        return;
    } //!$proposal_data
    $proposal_id = $proposal_data->id;
    $proposal_directory = $proposal_data->directory_name;
    /* create proposal folder if not present */
    //$dest_path = $proposal_directory . '/';
    $dest_path_project_files = $proposal_directory . '/';
    $proposal_id = $proposal_data->id;
    $query_s = "SELECT * FROM {case_study_submitted_abstracts} WHERE proposal_id = :proposal_id";
    $args_s = array(
        ":proposal_id" => $proposal_id,
    );
    $query_s_result = \Drupal::database()->query($query_s, $args_s)->fetchObject();
    if (!$query_s_result) {
        /* creating solution database entry */
        $query = "INSERT INTO {case_study_submitted_abstracts} (
	proposal_id,
	approver_uid,
	abstract_approval_status,
	abstract_upload_date,
	abstract_approval_date,
	is_submitted) VALUES (:proposal_id, :approver_uid, :abstract_approval_status,:abstract_upload_date, :abstract_approval_date, :is_submitted)";
        $args = array(
            ":proposal_id" => $proposal_id,
            ":approver_uid" => 0,
            ":abstract_approval_status" => 0,
            ":abstract_upload_date" => time(),
            ":abstract_approval_date" => 0,
            ":is_submitted" => 1,
        );
        $submitted_abstract_id = \Drupal::database()->query($query, $args, $query);
        $query1 = "UPDATE {case_study_proposal} SET is_submitted = :is_submitted WHERE id = :id";
        $args1 = array(
            ":is_submitted" => 1,
            ":id" => $proposal_id,
        );
        \Drupal::database()->query($query1, $args1);
        \Drupal::messenger()->addStatus('Abstract uploaded successfully.');
    } //!$query_s_result
    else {
        $query = "UPDATE {case_study_submitted_abstracts} SET


	abstract_upload_date =:abstract_upload_date,
	is_submitted= :is_submitted
	WHERE proposal_id = :proposal_id
	";
        $args = array(

            ":abstract_upload_date" => time(),
            ":is_submitted" => 1,
            ":proposal_id" => $proposal_id,
        );
        $submitted_abstract_id = \Drupal::database()->query($query, $args, $query);
        $query1 = "UPDATE {case_study_proposal} SET is_submitted = :is_submitted WHERE id = :id";
        $args1 = array(
            ":is_submitted" => 1,
            ":id" => $proposal_id,
        );
        \Drupal::database()->query($query1, $args1);
        \Drupal::messenger()->addStatus('Abstract updated successfully.');
    }
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
        if ($file_name) {
            /* checking file type */
            if (strstr($file_form_name, 'upload_case_study_developed_process')) {
                $file_type = 'S';
            } //strstr($file_form_name, 'upload_case_study_developed_process')
            switch ($file_type) {
                case 'S':

                    if (file_exists($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name])) {
                        //unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
                        \Drupal::messenger()->addError(t("File !filename already exists hence overwirtten the exisitng file ", array(
                            '!filename' => $_FILES['files']['name'][$file_form_name],
                        )));
                    } //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
                    /* uploading file */
                    else if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name])) {
                        /* for uploaded files making an entry in the database */
                        $query_abstracts = "SELECT * FROM case_study_submitted_abstracts WHERE proposal_id = :proposal_id";
                        $query_abstracts_args = array(
                            ":proposal_id" => $proposal_id,
                        );
                        $query_abstracts_result = \Drupal::database()->query($query_abstracts, $query_abstracts_args)->fetchObject();
                        $submitted_abstract_id = $query_abstracts_result->id;
                        $query_ab_f = "SELECT * FROM case_study_submitted_abstracts_file WHERE proposal_id = :proposal_id AND filetype =
				:filetype";
                        $args_ab_f = array(
                            ":proposal_id" => $proposal_id,
                            ":filetype" => $file_type,
                        );
                        $query_ab_f_result = \Drupal::database()->query($query_ab_f, $args_ab_f)->fetchObject();
                        if (!$query_ab_f_result) {
                            $query = "INSERT INTO {case_study_submitted_abstracts_file} (submitted_abstract_id, proposal_id, uid, approvar_uid, filename, filepath, filemime, filesize, filetype, timestamp)
          VALUES (:submitted_abstract_id, :proposal_id, :uid, :approvar_uid, :filename, :filepath, :filemime, :filesize, :filetype, :timestamp)";
                            $args = array(
                                ":submitted_abstract_id" => $submitted_abstract_id,
                                ":proposal_id" => $proposal_id,
                                ":uid" => $user->uid,
                                ":approvar_uid" => 0,
                                ":filename" => $_FILES['files']['name'][$file_form_name],
                                ":filepath" => $_FILES['files']['name'][$file_form_name],
                                ":filemime" => mime_content_type($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]),
                                ":filesize" => $_FILES['files']['size'][$file_form_name],
                                ":filetype" => $file_type,
                                ":timestamp" => time(),
                            );
                            \Drupal::database()->query($query, $args, $query);
                            \Drupal::messenger()->addStatus($file_name . ' uploaded successfully.');
                        } //!$query_ab_f_result
                        else {
                            unlink($root_path . $dest_path_project_files . $query_ab_f_result->filename);
                            $query = "UPDATE {case_study_submitted_abstracts_file} SET filename = :filename, filepath=:filepath, filemime=:filemime, filesize=:filesize, timestamp=:timestamp WHERE proposal_id = :proposal_id AND filetype = :filetype";
                            $args = array(
                                ":filename" => $_FILES['files']['name'][$file_form_name],
                                ":filepath" => $file_path . $_FILES['files']['name'][$file_form_name],
                                ":filemime" => mime_content_type($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]),
                                ":filesize" => $_FILES['files']['size'][$file_form_name],
                                ":timestamp" => time(),
                                ":proposal_id" => $proposal_id,
                                ":filetype" => $file_type,
                            );
                            \Drupal::database()->query($query, $args, $query);

                            \Drupal::messenger()->addStatus($file_name . ' file updated successfully.');
                        }
                    } //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
                    else {
                        \Drupal::messenger()->addError('Error uploading file : ' . $dest_path_project_files . $file_name);
                    }
                    break;
            } //$file_type
        } //$file_name
    } //$_FILES['files']['name'] as $file_form_name => $file_name
    /* sending email */
    $email_to = $user->mail;
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

    $params['abstract_uploaded']['proposal_id'] = $proposal_id;
    $params['abstract_uploaded']['submitted_abstract_id'] = $submitted_abstract_id;
    $params['abstract_uploaded']['user_id'] = $user->uid;
    $params['abstract_uploaded']['headers'] = array(
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
    );
    if (!drupal_mail('cfd_case_study', 'abstract_uploaded', $email_to, language_default(), $params, $from, true)) {
        \Drupal::messenger()->addError('Error sending email message.');
    }

    drupal_goto('case-study-project/abstract-code');
}
function default_value_for_uploaded_files($filetype, $proposal_id)
{
    $query = \Drupal::database()->select('case_study_submitted_abstracts_file');
    $query->fields('case_study_submitted_abstracts_file');
    $query->condition('proposal_id', $proposal_id);
    $selected_files_array = "";
    if ($filetype == "S") {
        $query->condition('filetype', $filetype);
        $filetype_q = $query->execute()->fetchObject();
        return $filetype_q;
    } elseif ($filetype == "A") {
        $query->condition('filetype', $filetype);
        $filetype_q = $query->execute()->fetchObject();
        return $filetype_q;
    }
    return;
}
function cfd_case_study_edit_upload_abstract_code_form($form, &$form_state)
{
    $user = \Drupal::currentUser();
    $form['#attributes'] = array(
        'enctype' => "multipart/form-data",
    );
    /* get current proposal */
    $proposal_id = (int) arg(3);
    $uid = $user->uid;
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
        if ($proposal_data = $proposal_q->fetchObject()) {
            /* everything ok */
        } //$proposal_data = $proposal_q->fetchObject()
        else {
            \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
            drupal_goto('case-study-project/manage-proposal/edit-upload-file');
            return;
        }
    } //$proposal_q
    else {
        \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
        drupal_goto('case-study-project/manage-proposal/edit-upload-file');
        return;
    }
    $query = \Drupal::database()->select('case_study_submitted_abstracts');
    $query->fields('case_study_submitted_abstracts');
    $query->condition('proposal_id', $proposal_data->id);
    $abstracts_q = $query->execute()->fetchObject();
    $form['project_title'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->project_title,
        '#title' => t('Title of the Case Study Project'),
    );
    $form['contributor_name'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->contributor_name,
        '#title' => t('Contributor Name'),
    );
    $existing_uploaded_A_file = default_value_for_uploaded_files("A", $proposal_data->id);
    if (!$existing_uploaded_A_file) {
        $existing_uploaded_A_file = new stdClass();
        $existing_uploaded_A_file->filename = "No file uploaded";
    } //!$existing_uploaded_A_file
    // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $form['upload_case_study_abstract'] = array(
//         '#type' => 'file',
//         '#title' => t('Upload the Case study abstract'),
//         //'#required' => TRUE,
//         '#description' => t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_A_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . variable_get('resource_upload_extensions', '') . '</span>',
//     );

    $existing_uploaded_S_file = default_value_for_uploaded_files("S", $proposal_data->id);
    if (!$existing_uploaded_S_file) {
        $existing_uploaded_S_file = new stdClass();
        $existing_uploaded_S_file->filename = "No file uploaded";
    } //!$existing_uploaded_S_file
    // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $form['upload_case_study_developed_process'] = array(
//         '#type' => 'file',
//         '#title' => t('Upload the Case Directory'),
//         //'#required' => TRUE,
//         '#description' => t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_S_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . variable_get('case_study_project_files_extensions', '') . '</span>',
//     );

    $form['prop_id'] = array(
        '#type' => 'hidden',
        '#value' => $proposal_data->id,
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
        '#submit' => array(
            'cfd_case_study_edit_upload_abstract_code_form_submit',
        ),
    );
    // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $form['cancel'] = array(
//         '#type' => 'item',
//         '#markup' => l(t('Cancel'), 'case-study-project/manage-proposal/edit-upload-file'),
//     );

    return $form;
}
///////////////////////////////////////////////////////////////
function cfd_case_study_edit_upload_abstract_code_form_validate($form, &$form_state)
{

    if (!($_FILES['files']['name']['upload_case_study_abstract'] || $_FILES['files']['name']['upload_case_study_developed_process'])) {
        \Drupal::messenger()->addError('No files uploaded');
        return;
    }
    if (isset($_FILES['files'])) {
        /* check for valid filename extensions */
        foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
            if ($file_name) {
                /* checking file type */
                if (strstr($file_form_name, 'upload_case_study_abstract')) {
                    $file_type = 'A';
                } else if (strstr($file_form_name, 'upload_case_study_developed_process')) {
                    $file_type = 'S';
                } else {
                    $file_type = 'U';
                }

                $allowed_extensions_str = '';
                switch ($file_type) {
                    case 'A':
                        // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $allowed_extensions_str = variable_get('resource_upload_extensions', '');

                        break;
                    case 'S':
                        // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $allowed_extensions_str = variable_get('case_study_project_files_extensions', '');

                        break;
                } //$file_type
                /* checking file type */
                $allowed_extensions = explode(',', $allowed_extensions_str);
                $fnames = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
                $temp_extension = end($fnames);
                if (!in_array($temp_extension, $allowed_extensions)) {
                    form_set_error($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
                }

                if ($_FILES['files']['size'][$file_form_name] <= 0) {
                    form_set_error($file_form_name, t('File size cannot be zero.'));
                }

                /* check if valid file name */
                if (!cfd_case_study_check_valid_filename($_FILES['files']['name'][$file_form_name])) {
                    form_set_error($file_form_name, t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
                }

            } //$file_name
        } //$_FILES['files']['name'] as $file_form_name => $file_name
    } else {
        \Drupal::messenger()->addError('No files uploaded');
        return $form_state;
    }

}
function cfd_case_study_edit_upload_abstract_code_form_submit($form, &$form_state)
{
    $user = \Drupal::currentUser();
    $v = $form_state['values'];
    $root_path = cfd_case_study_path();
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('id', $v['prop_id']);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    $proposal_id = $proposal_data->id;
    if (!$proposal_data) {
        drupal_goto('');
        return;
    } //!$proposal_data
    $proposal_id = $proposal_data->id;
    $proposal_directory = $proposal_data->directory_name;
    /* create proposal folder if not present */
    //$dest_path = $proposal_directory . '/';
    $dest_path_project_files = $proposal_directory . '/';
    $proposal_id = $proposal_data->id;
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {

        if ($file_name) {
            /* uploading file */
            /* checking file type */
            if (strstr($file_form_name, 'upload_case_study_abstract')) {
                $file_type = 'A';
                $abs_file_name = $_FILES['files']['name'][$file_form_name];
            } else {
                $abs_file_name = "Not updated";
            }
            if (strstr($file_form_name, 'upload_case_study_developed_process')) {
                $file_type = 'S';
                $proj_file_name = $_FILES['files']['name'][$file_form_name];
            } else {
                $proj_file_name = "Not updated";
            }
            if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name])) {
                $query_ab_f = "SELECT * FROM case_study_submitted_abstracts_file WHERE proposal_id = :proposal_id AND filetype =
				:filetype";
                $args_ab_f = array(
                    ":proposal_id" => $proposal_id,
                    ":filetype" => $file_type,
                );
                $query_ab_f_result = \Drupal::database()->query($query_ab_f, $args_ab_f)->fetchObject();
                unlink($root_path . $dest_path_project_files . $query_ab_f_result->filename);
                $query = "UPDATE {case_study_submitted_abstracts_file} SET filename = :filename, filepath=:filepath, filemime=:filemime, filesize=:filesize, timestamp=:timestamp WHERE proposal_id = :proposal_id AND filetype = :filetype";
                $args = array(
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $file_path . $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => $_FILES['files']['size'][$file_form_name],
                    ":timestamp" => time(),
                    ":proposal_id" => $proposal_id,
                    ":filetype" => $file_type,
                );
                \Drupal::database()->query($query, $args, $query);

                \Drupal::messenger()->addStatus($file_name . ' file updated successfully.');

            } else {
                \Drupal::messenger()->addError($file_name . ' file not updated successfully.');
            }
        }
    } //$_FILES['files']['name'] as $file_form_name => $file_name
    /* sending email */
    $email_to = $user->mail;
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

    $params['abstract_edit_file_uploaded']['proposal_id'] = $proposal_id;
    $params['abstract_edit_file_uploaded']['user_id'] = $user->uid;
    $params['abstract_edit_file_uploaded']['abs_file'] = $abs_file_name;
    $params['abstract_edit_file_uploaded']['proj_file'] = $proj_file_name;
    $params['abstract_edit_file_uploaded']['headers'] = array(
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
    );
    if (!drupal_mail('cfd_case_study', 'abstract_edit_file_uploaded', $email_to, language_default(), $params, $from, true)) {
        \Drupal::messenger()->addError('Error sending email message.');
    }
    drupal_goto('case-study-project/abstract-code/edit-upload-files');
}
