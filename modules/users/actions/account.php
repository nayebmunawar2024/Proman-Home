<?php
/**
 * Этот файл является частью программы "CRM Руководитель" - конструктор CRM систем для бизнеса
 * https://www.rukovoditel.net.ru/
 * 
 * CRM Руководитель - это свободное программное обеспечение, 
 * распространяемое на условиях GNU GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * 
 * Автор и правообладатель программы: Харчишина Ольга Александровна (RU), Харчишин Сергей Васильевич (RU).
 * Государственная регистрация программы для ЭВМ: 2023664624
 * https://fips.ru/EGD/3b18c104-1db7-4f2d-83fb-2d38e1474ca3
 */

//check if logged user is guest
if(guest_login::is_guest())
{
    redirect_to('dashboard/');
}

$current_entity_id = 1;

$entity_cfg = new entities_cfg($current_entity_id);

$user_query = db_query("select e.* " . fieldtype_formula::prepare_query_select(1, '') . " from app_entity_1 e where e.id='" . db_input($app_logged_users_id) . "' and e.field_5=1");
$obj = db_fetch_array($user_query);

switch($app_module_action)
{
    case 'set_cfg':
        if(isset($_POST['key']) and isset($_POST['value']))
        {
            $app_users_cfg->set($_POST['key'], $_POST['value']);
        }
        exit();
        break;
    case 'update':

        //chck form token
        app_check_form_token();

        $msg = array();

        //check POST data for user form
        if(isset($_POST['fields'][9]))
            if(strlen($_POST['fields'][9]) == 0)
            {
                $msg[] = TEXT_ERROR_USEREMAL_EMPTY;
            }

        if(isset($_POST['fields'][12]))
            if(CFG_ALLOW_CHANGE_USERNAME == 1)
            {
                if(strlen($_POST['fields'][12]) == 0)
                {
                    $msg[] = TEXT_ERROR_USERNAME_EMPTY;
                }
            }

        if(isset($_POST['fields'][9]))
            if(strlen($_POST['fields'][9]) > 0 and CFG_ALLOW_REGISTRATION_WITH_THE_SAME_EMAIL == 0)
            {
                $check_query = db_query("select count(*) as total from app_entity_1 where field_9='" . db_input($_POST['fields'][9]) . "'  and id!='" . db_input($app_logged_users_id) . "'");
                $check = db_fetch_array($check_query);
                if($check['total'] > 0)
                {
                    $msg[] = TEXT_ERROR_USEREMAL_EXIST;
                }
            }

        if(CFG_ALLOW_CHANGE_USERNAME == 1 and isset($_POST['fields'][12]))
        {
            if(strlen($_POST['fields'][12]) > 0)
            {
                $check_query = db_query("select count(*) as total from app_entity_1 where field_12='" . db_input($_POST['fields'][12]) . "'  and id!='" . db_input($app_logged_users_id) . "'");
                $check = db_fetch_array($check_query);
                if($check['total'] > 0)
                {
                    $msg[] = TEXT_ERROR_USERNAME_EXIST;
                }
            }
        }

        if(count($msg) > 0)
        {
            foreach($msg as $v)
            {
                $alerts->add($v, 'error');
            }

            redirect_to('users/account');
        }

        if(isset($_POST['fields']))
            $fields_values_cache = items::get_fields_values_cache($_POST['fields'], array($current_entity_id), $current_entity_id);

        $fields_access_schema = users::get_fields_access_schema($current_entity_id, $app_user['group_id']);

        $item_info_query = db_query("select * from app_entity_" . $current_entity_id . " where id='" . db_input($app_user['id']) . "'");
        $item_info = db_fetch_array($item_info_query);

        $sql_data = array();


        $excluded_fileds_types = "'fieldtype_user_accessgroups','fieldtype_user_status','fieldtype_user_skin','fieldtype_user_last_login_date'";

        if(CFG_ALLOW_CHANGE_USERNAME == 0)
        {
            $excluded_fileds_types .= ",'fieldtype_user_username'";
        }

        $choices_values = new choices_values($current_entity_id);

        $fields_query = db_query("select f.* from app_fields f where f.type not in (" . fields_types::get_reserverd_types_list() . "," . $excluded_fileds_types . ") and  f.entities_id='" . db_input($current_entity_id) . "' order by f.sort_order, f.name");
        while($field = db_fetch_array($fields_query))
        {
            //check field access and skip fields without access
            if(isset($fields_access_schema[$field['id']]))
            {
                continue;
            }

            $value = (isset($_POST['fields'][$field['id']]) ? $_POST['fields'][$field['id']] : '');

            //current field value
            $current_field_value = (isset($item_info['field_' . $field['id']]) ? $item_info['field_' . $field['id']] : '');

            $process_options = array(
                'class' => $field['type'],
                'value' => $value,
                'fields_cache' => $fields_values_cache,
                'field' => $field,
                'is_new_item' => false,
                'current_field_value' => $current_field_value,
            );

            $sql_data['field_' . $field['id']] = fields_types::process($process_options);

            //prepare choices values for fields with multiple values
            $choices_values->prepare($process_options);
        }

        if(count($sql_data))
        {
            db_perform('app_entity_' . $current_entity_id, $sql_data, 'update', "id='" . db_input($app_logged_users_id) . "'");
        }

        //insert choices values for fields with multiple values
        $choices_values->process($app_logged_users_id);

        //autoupdate all field types
        fields_types::update_items_fields($current_entity_id, $app_logged_users_id);

        //set user configuration options
        $cfg = array('disable_notification', 'disable_internal_notification', 'disable_highlight_unread');
        foreach($cfg as $key)
        {
            $app_users_cfg->set($key, (isset($_POST['cfg'][$key]) ? $_POST['cfg'][$key] : ''));
        }

        if(is_ext_installed())
        {
            //sending sms
            $modules = new modules('sms');
            $sms = new sms($current_entity_id, $app_logged_users_id);            
            $sms->send_edit_msg($item_info);
                
            //subscribe
            $modules = new modules('mailing');
            $mailing = new mailing($current_entity_id, $app_logged_users_id);
            $mailing->update($item_info);
            
            //email rules
            $email_rules = new email_rules($current_entity_id, $app_logged_users_id);
            $email_rules->send_edit_msg($item_info);

            //run actions after item update
            $processes = new processes($current_entity_id);
            $processes->run_after_update($app_logged_users_id);
        }

        email_verification::check_if_user_email_is_updated();

        $alerts->add(TEXT_ACCOUNT_UPDATED, 'success');

        redirect_to('users/account');

        break;

    case 'attachments_upload':
        $verifyToken = md5($app_user['id'] . $_POST['timestamp']);

        if(strlen($_FILES['Filedata']['tmp_name']) and $_POST['token'] == $verifyToken)
        {
            $name = $_FILES['Filedata']['name'];

            if(isset($_POST['filename_template']) and strlen($_POST['filename_template']) and isset($_POST['form_data']))
            {
                $name = attachments::get_filename_by_template($_POST['filename_template'], $_POST['form_data'], $name);
            }

            $file = attachments::prepare_filename($name);

            if(move_uploaded_file($_FILES['Filedata']['tmp_name'], DIR_WS_ATTACHMENTS . $file['folder'] . '/' . $file['file']))
            {
                //autoresize images if enabled
                attachments::resize(DIR_WS_ATTACHMENTS . $file['folder'] . '/' . $file['file']);

                //add attachments to tmp table
                $sql_data = array('form_token' => $verifyToken, 'filename' => $file['name'], 'date_added' => date('Y-m-d'), 'container' => $_GET['field_id']);
                db_perform('app_attachments', $sql_data);

                //add file to queue
                if(class_exists('file_storage'))
                {
                    $file_storage = new file_storage();
                    $file_storage->add_to_queue($_GET['field_id'], $file['name']);
                }
            }
        }
        exit();
        break;

    case 'attachments_preview':
        $field_id = $_GET['field_id'];

        $attachments_list = $uploadify_attachments[$field_id];

        //get new attachments
        $attachments_query = db_query("select filename from app_attachments where form_token='" . db_input($_GET['token']) . "' and container='" . db_input($_GET['field_id']) . "'");
        while($attachments = db_fetch_array($attachments_query))
        {
            $attachments_list[] = $attachments['filename'];

            if(!in_array($attachments['filename'], $uploadify_attachments_queue[$field_id]))
                $uploadify_attachments_queue[$field_id][] = $attachments['filename'];
        }

        $delete_file_url = url_for('users/account', 'action=attachments_delete_in_queue');

        echo attachments::render_preview($field_id, $attachments_list, $delete_file_url);

        exit();
        break;
    case 'attachments_delete_in_queue':
        //chck form token
        app_check_form_token();

        attachments::delete_in_queue($_POST['field_id'], $_POST['filename']);

        exit();
        break;
    case 'check_unique':

        //chck form token
        app_check_form_token();

        echo items::check_unique(_get::int('entities_id'), _post::int('fields_id'), $_POST['fields_value'], $app_user['id']);

        exit();
        break;
    
    case 'audiorecording_start_recording':
        include component_path('items/audiorecording_form');
        exit();
        break;
    case 'audiorecording_upload':        
        $field_id = _GET('field_id');

        if(!isset_field($current_entity_id, $field_id))
        {
            exit();
        }                
        
        $verifyToken = md5($app_user['id'] . _GET('timestamp'));        
        audiorecorder::upload($field_id, $verifyToken);        
        exit();
        break;
    case 'onlyoffice_upload':
        (new onlyoffice($current_entity_id))->upload();
        exit();
        break;
    case 'onlyoffice_preview':
        echo (new onlyoffice($current_entity_id))->preview(_GET('field_id'),$_GET['form_token']??'',$app_user['id']);
        exit();
        break;
    case 'onlyoffice_delete':
        onlyoffice::delete(_POST('file'));
        exit();
        break; 
    
    case 'fs_upload':
        $filetype = _GETS('ft');                
        if(class_exists($filetype)) (new $filetype())->upload($current_entity_id, _GET('field_id')); 
        exit();
        break;
    case 'fs_preview':
        $filetype = _GETS('ft');
        if(class_exists($filetype)) echo file_storage_field::preview($current_entity_id,_GET('field_id'),$_GET['form_token']??'',$app_user['id']);            
        exit();
        break;
    case 'fs_delete':
        $filetype = _GETS('ft');
        if(class_exists($filetype)) (new $filetype())->delete($current_entity_id,_GET('field_id'),_POST('file'));            
        exit();
        break;
}
