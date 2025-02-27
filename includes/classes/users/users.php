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

class users
{

    static public function output_heading_from_item($item)
    {
        return (CFG_APP_DISPLAY_USER_NAME_ORDER == 'firstname_lastname' ? $item['field_7'] . ' ' . $item['field_8'] : $item['field_8'] . ' ' . $item['field_7']);
    }

    static public function get_cache()
    {
        global $app_choices_cache, $app_users_cache, $app_fields_cache;

        $include_public_profile = false;

        //include public profile for page where it needs only
        if(isset($_GET['module']))
        {
            if(in_array($_GET['module'], array('items/listing', 'items/info', 'items/comments_listing', 'items/comments_listing_mobile')))
            {
                $include_public_profile = true;
            }
        }

        $public_profile_fields = array();

        //get public profile fields
        if(defined('CFG_PUBLIC_USER_PROFILE_FIELDS') and $include_public_profile)
        {
            if(!is_null(CFG_PUBLIC_USER_PROFILE_FIELDS) and strlen(CFG_PUBLIC_USER_PROFILE_FIELDS) > 0)
            {
                $fields_query = db_query("select f.*, t.name as tab_name from app_fields f, app_forms_tabs t where f.type not in (" . fields_types::get_reserverd_types_list() . ") and f.id in (" . CFG_PUBLIC_USER_PROFILE_FIELDS . ") and  f.entities_id='1' and f.forms_tabs_id=t.id order by  field(f.id," . CFG_PUBLIC_USER_PROFILE_FIELDS . ")");
                while($v = db_fetch_array($fields_query))
                {
                    $public_profile_fields[] = $v;
                }
            }
        }

        $cache = array();

        $listing_sql_query_select = '';
        if(count($public_profile_fields))
        {
            $listing_sql_query_select = fieldtype_formula::prepare_query_select(1, '');
        }

        $field_heading_id = fields::get_heading_id(1);

        $users_query = db_query("select e.* " . $listing_sql_query_select . ", a.name as group_name, a.id as group_id from app_entity_1 e left join app_access_groups a on a.id=e.field_6 order by e.field_8, e.field_7");
        while($users = db_fetch_array($users_query))
        {
            $profile_fields = array();

            //generate public profile data
            foreach($public_profile_fields as $field)
            {
                $value = $users['field_' . $field['id']]??'';

                if(strlen($value) > 0)
                {
                    $output_options = array('class' => $field['type'],
                        'value' => $value,
                        'field' => $field,
                        'item' => $users,
                        'is_listing' => true,
                        'is_export' => true,
                        'redirect_to' => '',
                        'reports_id' => 0,
                        'path' => 1);

                    //fix notice in cron
                    if(!defined('TEXT_' . strtoupper($field['type']) . '_TITLE') and defined('IS_CRON'))
                        define('TEXT_' . strtoupper($field['type']) . '_TITLE', '');

                    $profile_fields[] = array('name' => fields_types::get_option($field['type'], 'name', $field['name']), 'value' => fields_types::output($output_options));
                }
            }

            if(isset($users['field_10']) and strlen($users['field_10']) > 0)
            {
                
                $file = attachments::parse_filename($users['field_10']);                
                
                $photo = $file['file_sha1'];
            }
            else
            {
                $photo = '';
            }

            if($field_heading_id and $field_heading_id != 12)
            {
                $name = items::get_heading_field_value($field_heading_id, $users);
            }
            else
            {
                $name = (CFG_APP_DISPLAY_USER_NAME_ORDER == 'firstname_lastname' ? $users['field_7'] . ' ' . $users['field_8'] : $users['field_8'] . ' ' . $users['field_7']);
            }

            $cache[$users['id']] = array(
                'id'=>$users['id'],
                'name' => $name,
                'email' => $users['field_9'],
                'photo' => $photo,
                'group_id' => (int) $users['field_6'],
                'group_array' => explode(',',$users['field_6']),
                'group_name' => ($users['group_id'] > 0 ? $users['group_name'] : (defined('TEXT_ADMINISTRATOR') ? TEXT_ADMINISTRATOR : 'Administrator')),
                'profile' => $profile_fields
            );
        }

        return $cache;
    }

    static public function get_assigned_users_by_item($entity_id, $item_id)
    {

        $users = array();

        $item_info = db_find('app_entity_' . $entity_id, $item_id);

        $fields_query = db_query("select f.* from app_fields f where f.type in ('fieldtype_users','fieldtype_users_ajax','fieldtype_grouped_users') and  f.entities_id='" . db_input($entity_id) . "'");
        while($fields = db_fetch_array($fields_query))
        {
            switch($fields['type'])
            {
                case 'fieldtype_users':
                case 'fieldtype_users_ajax':
                    if(strlen($item_info['field_' . $fields['id']]))
                    {
                        $users = array_merge(explode(',', $item_info['field_' . $fields['id']]), $users);
                    }
                    break;
                case 'fieldtype_grouped_users':
                    if(strlen($choices_id = $item_info['field_' . $fields['id']]))
                    {
                        $choices_query = db_query("select * from app_fields_choices where id='" . $choices_id . "'");
                        if($choices = db_fetch_array($choices_query))
                        {
                            if(strlen($choices['users']))
                            {
                                $users = array_merge(explode(',', $choices['users']), $users);
                            }
                        }
                    }
                    break;
            }
        }

        return $users;
    }

    static public function get_name_by_id($id)
    {
        global $app_users_cache;

        if(isset($app_users_cache[$id]))
        {
            return $app_users_cache[$id]['name'];
        }
        else
        {
            return '';
        }
    }

    static public function render_publi_profile($users_cache, $is_photo_display = false)
    {
        global $app_module_action;

        if(strlen($app_module_action??'') > 0)
        {
            return '';
        }

        if(strlen($users_cache['photo']) and is_file(DIR_WS_USERS . $users_cache['photo']))
        {
            $photo = '<img src=' . url_for_file(DIR_WS_USERS . $users_cache['photo']) . ' width=50>';
        }
        else
        {
            $photo = '<img src=' . url_for_file('images/' . 'no_photo.png') . ' width=50>';
        }

        if(!isset($users_cache['profile']))
            $users_cache['profile'] = array();

        $content = '
      <table align=center>
        <tr>
          <td valign=top width=60>' . $photo . '</td>
          <td valign=top>
            <table class=popover-table-data>';

        foreach($users_cache['profile'] as $field)
        {
            $content .= '
        <tr>
          <td valign=top>' . htmlspecialchars(strip_tags($field['name'])) . ': </td><td valign=top>' . htmlspecialchars(strip_tags($field['value'])) . '</td>
        </tr>';
        }

        $content .= '
            </table>
          </td>
        </tr>
      </table>';

        $display_profile = false;

        if(count($users_cache['profile']) > 0 and $is_photo_display)
        {
            $display_profile = true;
        }
        elseif(!$is_photo_display)
        {
            $display_profile = true;
        }

        if($display_profile)
        {
            return 'data-toggle="popover" title="' . addslashes(htmlspecialchars($users_cache['name'])) . '" data-content="' . addslashes(str_replace(array("\n", "\r", "\n\r"), ' ', $content)) . '"';
        }
        else
        {
            return '';
        }
    }

    static public function get_choices($options = array())
    {
        $choices = array();
        $users_query = db_query("select u.*,a.name as group_name from app_entity_1 u left join app_access_groups a on a.id=u.field_6 where u.field_5=1 order by u.field_8, u.field_7");
        while($users = db_fetch_array($users_query))
        {
            $group_name = ((is_string($users['group_name']) and strlen($users['group_name']) > 0) ? $users['group_name'] : TEXT_ADMINISTRATOR);
            $choices[$group_name][$users['id']] = $users['field_8'] . ' ' . $users['field_7'];
        }

        return $choices;
    }

    static public function get_choices_by_entity($entities_id, $has_access = '')
    {
        global $app_users_cache;

        $access_schema = users::get_entities_access_schema_by_groups($entities_id);

        $choices = array();
        $order_by_sql = (CFG_APP_DISPLAY_USER_NAME_ORDER == 'firstname_lastname' ? 'u.field_7, u.field_8' : 'u.field_8, u.field_7');
        $users_query = db_query("select u.*,a.name as group_name from app_entity_1 u left join app_access_groups a on a.id=u.field_6 where u.field_5=1 order by " . $order_by_sql);
        while($users = db_fetch_array($users_query))
        {
            if(!isset($access_schema[$users['field_6']]))
            {
                $access_schema[$users['field_6']] = array();
            }

            if(strlen($has_access))
            {
                if($users['field_6'] == 0 or in_array($has_access, $access_schema[$users['field_6']]))
                {
                    $choices[$users['id']] = $app_users_cache[$users['id']]['name'];
                }
            }
            elseif($users['field_6'] == 0 or in_array('view', $access_schema[$users['field_6']]) or in_array('view_assigned', $access_schema[$users['field_6']]))
            {
                $choices[$users['id']] = $app_users_cache[$users['id']]['name'];
            }
        }

        return $choices;
    }

    static public function use_email_pattern($pattern, $blocks = array())
    {
        $content = file_get_contents('includes/patterns/email/' . $pattern . '.html');

        foreach($blocks as $k => $v)
        {
            $v = users::use_email_pattern_style($v, $k);
            $content = str_replace('[' . $k . ']', $v, $content);
        }

        return $content;
    }

    static public function use_email_pattern_style($content, $style)
    {
        $content = preg_replace('/data-content="(.*)"/', '', $content);

        require('includes/patterns/email/styles.php');

        foreach($styles[$style] as $tag => $css)
        {
            $content = str_replace(array('<' . $tag . ' ', '<' . $tag . '>'), array('<' . $tag . ' style="' . $css . '" ', '<' . $tag . ' style="' . $css . '">'), $content);
        }

        foreach($css_classes as $class => $styles)
        {
            $content = str_replace('class="' . $class . '"', 'style="' . $styles . '"', $content);
        }

        return $content;
    }

    static public function send_to($send_to, $subject, $body, $attachments = [], $send_from=false)
    {
        global $app_user, $app_users_cache;

        foreach($send_to as $users_id)
        {
            if(strstr($users_id, '@'))
            {
                if(app_validate_email($users_id))
                {
                    $options = array(
                        'to' => $users_id,
                        'to_name' => '',
                        'subject' => $subject,
                        'body' => $body,
                        'from' => $app_user['email'],
                        'from_name' => $app_user['name'],
                        'attachments' => $attachments,
                    );
                    
                    //custom sender stupe
                    if($send_from)
                    {
                        $options['from'] = $send_from['from'];
                        $options['from_name'] = $send_from['from_name'];
                        $options['force_send_from'] = true;
                    }

                    users::send_email($options);                    
                }
            }
            else
            {                
                if(CFG_EMAIL_COPY_SENDER == 0 and $users_id == $app_user['id'] and isset($app_user['email']) and $app_user['email']!=CFG_EMAIL_ADDRESS_FROM)
                    continue;
                                
                if(users_cfg::get_value_by_users_id($users_id, 'disable_notification') == 1)
                    continue;

                $users_info_query = db_query("select * from app_entity_1 where id='" . db_input($users_id) . "' and field_5=1");
                if($users_info = db_fetch_array($users_info_query) and isset($app_user['email']))
                {
                    $options = array(
                        'to' => $users_info['field_9'],
                        'to_name' => $app_users_cache[$users_info['id']]['name'],
                        'subject' => $subject,
                        'body' => $body,
                        'from' => $app_user['email'],
                        'from_name' => $app_user['name'],
                        'attachments' => $attachments,
                    );
                    
                    //custom sender stupe
                    if($send_from)
                    {
                        $options['from'] = $send_from['from'];
                        $options['from_name'] = $send_from['from_name'];
                        $options['force_send_from'] = true;
                    }


                    users::send_email($options);
                }
            }
        }
    }

    static public function send_email($options = array())
    {
        global $alerts;

        //check status
        if(CFG_EMAIL_USE_NOTIFICATION == 0 and!isset($options['send_directly']))
        {
            return false;
        }

        //Sending via cron. Use send_directly to skip corn
        if(CFG_SEND_EMAILS_ON_SCHEDULE == 1 and!isset($options['send_directly']))
        {
            $sql_data = array(
                'date_added' => time(),
                'email_to' => $options['to'],
                'email_to_name' => $options['to_name'],
                'email_subject' => $options['subject'],
                'email_body' => $options['body'],
                'email_from' => $options['from'],
                'email_from_name' => $options['from_name'],
                'email_attachments' => self::emails_on_schedule_prepare_attachments(($options['attachments'] ?? [])),
            );

            db_perform('app_emails_on_schedule', $sql_data);

            return true;
        }

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try
        {
            $mail->CharSet = "UTF-8";
            $mail->setLanguage(APP_LANGUAGE_SHORT_CODE);

            if(CFG_EMAIL_USE_SMTP == 1)
            {
                $mail->isSMTP();                          // Set mailer to use SMTP
                $mail->Host = CFG_EMAIL_SMTP_SERVER;      // Specify main and backup server
                $mail->Port = CFG_EMAIL_SMTP_PORT;

                if(strlen(CFG_EMAIL_SMTP_LOGIN) > 0 or strlen(CFG_EMAIL_SMTP_PASSWORD))
                {
                    $mail->SMTPAuth = true;                               // Enable SMTP authentication
                    $mail->Username = CFG_EMAIL_SMTP_LOGIN;               // SMTP username
                    $mail->Password = CFG_EMAIL_SMTP_PASSWORD;            // SMTP password
                }
                else
                {
                    $mail->SMTPAuth = false;
                }

                //set encryption
                switch(CFG_EMAIL_SMTP_ENCRYPTION)
                {
                    case 'ssl':
                    case 'tls':
                        $mail->SMTPSecure = CFG_EMAIL_SMTP_ENCRYPTION;
                        break;
                    default:
                        $mail->SMTPAutoTLS = false;
                        $mail->SMTPSecure = false;
                        break;
                }
                
                //set debug mode                
                if(CFG_EMAIL_SMTP_DEBUG)
                {
                    $mail->SMTPDebug = 3;
                    $mail->Debugoutput = 'app_smtp_error_log';
                }
            }

            if(isset($options['force_send_from']))
            {                
                $mail->setFrom($options['from'], $options['from_name'],false);
            }
            elseif(CFG_EMAIL_SEND_FROM_SINGLE == 1)
            {                
                $mail->setFrom(CFG_EMAIL_ADDRESS_FROM, CFG_EMAIL_NAME_FROM,false);                
            }
            else
            {
                $mail->setFrom($options['from'], $options['from_name'],false);                
            }

            $mail->addAddress($options['to'], $options['to_name']);  // Add a recipient
            
            if(isset($options['attachments']))
            {
                foreach($options['attachments'] as $filename => $name)
                {                    
                    if(is_file($filename))
                    {
                        if(is_array($name))
                        {
                            $mail->AddEmbeddedImage($filename, current($name));
                        }
                        else
                        {
                            $mail->addAttachment($filename, $name);
                        }
                    }
                }
            }

            $mail->isHTML(true);  // Set email format to HTML
            
            
            //use custom html layout
            $options['html_layout']  = $options['html_layout']??1;
            
            if($options['html_layout'] and CFG_USE_EMAIL_HTML_LAYOUT and strstr(CFG_EMAIL_HTML_LAYOUT,'${body}'))
            {
                $options['body'] = str_replace('${body}',$options['body'],CFG_EMAIL_HTML_LAYOUT);
            }

            $mail->Subject = ((!is_null(CFG_EMAIL_SUBJECT_LABEL) and strlen(CFG_EMAIL_SUBJECT_LABEL) > 0) ? CFG_EMAIL_SUBJECT_LABEL . ' ' : '') . $options['subject'];
            $mail->Body = $options['body'];

            $h2t = new html2text($options['body']);
            $mail->AltBody = $h2t->get_text();

            $mail->send();
            
            app_logs::email_log($options['to'],$options['subject']);
        }
        catch(Exception $e)
        {
            app_logs::email_log($options['to'],$options['subject'], $mail->ErrorInfo);
            
            if(is_object($alerts))
            {
                $alerts->add(sprintf(TEXT_MAILER_ERROR, $options['to']) . ': ' . $mail->ErrorInfo . (CFG_EMAIL_SMTP_DEBUG ? '<br>' . TEXT_MORE_INFO . ': log/smtp_log.txt':''), 'error');
            }
            else
            {
                error_log(date("Y-m-d H:i:s") . ' Error sending message to ' . $options['to'] . ': ' . $mail->ErrorInfo . "\n", 3, "log/Email_Errors_" . date("M_Y") . ".txt");
            }    
            
            if(CFG_EMAIL_SMTP_DEBUG)
            {
                error_log("\n", 3, "log/smtp_log.txt");
            }

            return false;
        }                

        return true;
    }

    static function emails_on_schedule_prepare_attachments($attachments)
    {
        $files_list = [];
        foreach($attachments as $filename => $name)
        {
            if(is_file($filename))
            {
                if(copy($filename, DIR_FS_TMP . sha1($name)))
                {
                    $files_list[] = $name;
                }
            }
        }

        return implode(',', $files_list);
    }

    static public function get_random_password($length = CFG_PASSWORD_MIN_LENGTH, $has_symbols = true)
    {
        $chars = "abcdefghijkmnopqrstuvwxyz0123456789ABCDEFGHIJKMNOPQRSTUVWXYZ" . ($has_symbols ? '~!@#$%^&*()_+' : '');

        $password = '';

        for($i = 0; $i < $length; $i++)
        {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }

        return $password;
    }

    static public function get_fields_access_schema($entities_id, $access_groups_id)
    {
        global $roles_fields_acccess_schema;

        if(isset($roles_fields_acccess_schema))
        {
            if($roles_fields_acccess_schema)
            {
                return $roles_fields_acccess_schema;
            }
        }

        $access_schema = array();
        $acess_info_query = db_query("select * from app_fields_access where entities_id='" . db_input($entities_id) . "' and access_groups_id='" . db_input($access_groups_id) . "'");
        while($acess_info = db_fetch_array($acess_info_query))
        {
            $access_schema[$acess_info['fields_id']] = $acess_info['access_schema'];
        }

        return $access_schema;
    }

    static public function get_entities_access_schema($entities_id, $access_groups_id)
    {
        $access_schema = array();

        $acess_info_query = db_query("select access_schema from app_entities_access where entities_id='" . db_input($entities_id) . "' and access_groups_id='" . db_input($access_groups_id) . "'");
        if($acess_info = db_fetch_array($acess_info_query))
        {
            $access_schema = explode(',', $acess_info['access_schema']);
        }

        return $access_schema;
    }

    static public function get_users_access_schema($access_groups_id)
    {
        $access_schema = array();

        $acess_info_query = db_query("select * from app_entities_access where access_groups_id='" . db_input($access_groups_id) . "'");
        while($acess_info = db_fetch_array($acess_info_query))
        {
            if(isset($acess_info['access_schema']) and strlen($acess_info['access_schema']))
            {
                $access_schema[$acess_info['entities_id']] = explode(',', $acess_info['access_schema']);
            }
        }

        return $access_schema;
    }

    static public function has_users_access_name_to_entity($access, $entities_id)
    {
        global $app_users_access, $app_user;

        //administrator have full access
        if($app_user['group_id'] == 0)
        {
            if($access == 'action_with_assigned')
            {
                return false;
            }
            else
            {
                return true;
            }
        }

        if(isset($app_users_access[$entities_id]))
        {
            return in_array($access, $app_users_access[$entities_id]);
        }
        else
        {
            return false;
        }
    }

    static public function has_users_access_to_entity($entities_id)
    {
        global $app_users_access, $app_user;

        if(isset($app_users_access[$entities_id]) or $app_user['group_id'] == 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    static public function get_entities_access_schema_by_groups($entities_id)
    {
        $access_schema = array();

        $acess_info_query = db_query("select access_schema,access_groups_id  from app_entities_access where entities_id='" . db_input($entities_id) . "'");
        while($acess_info = db_fetch_array($acess_info_query))
        {
            $access_schema[$acess_info['access_groups_id']] = explode(',', $acess_info['access_schema']??'');
        }

        return $access_schema;
    }

    static public function has_access($access, $access_schema = null)
    {
        global $current_access_schema, $app_user;

        //administrator have full access
        if($app_user['group_id'] == 0)
        {
            if(in_array($access, ['action_with_assigned', 'delete_creator','update_creator']))
            {
                return false;
            }
            else
            {
                return true;
            }
        }

        $schema = [];

        if(isset($access_schema))
        {
            $schema = $access_schema;
        }
        elseif(is_array($current_access_schema))
        {
            $schema = $current_access_schema;
        }

        return in_array($access, $schema);
    }

    static public function has_access_to_entity($entities_id, $access, $access_groups_id = null)
    {
        global $app_user;

        $access_schema = array();

        if(!isset($access_groups_id))
        {
            $access_groups_id = $app_user['group_id'];
        }

        if($access_groups_id == 0)
        {
            return true;
        }

        $acess_info_query = db_query("select access_schema from app_entities_access where entities_id='" . db_input($entities_id) . "' and access_groups_id='" . db_input($access_groups_id) . "'");
        if($acess_info = db_fetch_array($acess_info_query))
        {
            $access_schema = explode(',', $acess_info['access_schema']);
        }

        return in_array($access, $access_schema);
    }

    static public function has_access_to_assigned_item($entities_id, $items_id)
    {
        global $app_user;

        //get users entiteis tree
        $users_entities_tree = entities::get_tree(1);

        //get users entities id list
        $users_entities = array();
        foreach($users_entities_tree as $v)
        {
            $users_entities[] = $v['id'];
        }

        //force check users entities tree access
        if(in_array($entities_id, $users_entities) and $app_user['group_id'] > 0)
        {
            $item_query = db_query("select e.id from app_entity_" . $entities_id . " e where e.id='" . db_input($items_id) . "' " . items::add_access_query_for_user_parent_entities($entities_id));
            if($item = db_fetch_array($item_query))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            $users_fields = array();
            $fields_query = db_query("select f.id from app_fields f where f.type in ('fieldtype_users','fieldtype_users_ajax','fieldtype_user_roles','fieldtype_users_approve') and  f.entities_id='" . db_input($entities_id) . "'");
            while($fields = db_fetch_array($fields_query))
            {
                $users_fields[] = $fields['id'];
            }

            $grouped_users_fields = array();
            $grouped_global_users_fields = array();
            $fields_query = db_query("select f.id, f.configuration from app_fields f where f.type in ('fieldtype_grouped_users') and  f.entities_id='" . db_input($entities_id) . "'");
            while($fields = db_fetch_array($fields_query))
            {
                $cfg = new fields_types_cfg($fields['configuration']);

                if($cfg->get('use_global_list') > 0)
                {
                    $grouped_global_users_fields[$cfg->get('use_global_list')] = $fields['id'];
                }
                else
                {
                    $grouped_users_fields[] = $fields['id'];
                }
            }

            $access_group_fields = array();
            $fields_query = db_query("select f.id from app_fields f where f.type in ('fieldtype_access_group') and  f.entities_id='" . db_input($entities_id) . "'");
            while($fields = db_fetch_array($fields_query))
            {
                $access_group_fields[] = $fields['id'];
            }

            $sql_query_array = array();

            //check users fields
            foreach($users_fields as $id)
            {
                $sql_query_array[] = "(select count(*) as total from app_entity_" . $entities_id . "_values cv where cv.items_id='" . db_input($items_id) . "' and cv.fields_id='" . $id . "' and cv.value='" . $app_user['id'] . "')>0";
            }

            //check gouped users
            foreach($grouped_users_fields as $id)
            {
                $sql_query_array[] = "(select count(*) as total from app_entity_" . $entities_id . "_values cv where  cv.items_id='" . db_input($items_id) . "' and cv.fields_id='" . $id . "' and cv.value in (select id from app_fields_choices fc where fc.fields_id='" . $id . "' and find_in_set(" . $app_user['id'] . ",fc.users)))>0";
            }

            //check gouped users with globallist
            foreach($grouped_global_users_fields as $list_id => $id)
            {
                $sql_query_array[] = "(select count(*) as total from app_entity_" . $entities_id . "_values cv where cv.items_id=e.id and cv.fields_id='" . $id . "' and cv.value in (select id from app_global_lists_choices fc where fc.lists_id='" . $list_id . "' and find_in_set(" . $app_user['id'] . ",fc.users)))>0";
            }

            //check access group fields
            foreach($access_group_fields as $id)
            {
                $sql_query_array[] = "(select count(*) as total from app_entity_" . $entities_id . "_values cv where cv.items_id=e.id and cv.fields_id='" . $id . "' and cv.value='" . $app_user['group_id'] . "')>0";
            }

            //check created by
            $sql_query_array[] = "e.created_by='" . $app_user['id'] . "'";
            
            //check user entity
            if($entities_id==1)
            {
                $sql_query_array[] = "e.id='" . $app_user['id'] . "'";
            }

            $item_query = db_query("select e.id from app_entity_" . $entities_id . " e where e.id='" . db_input($items_id) . "' and (" . implode(' or ', $sql_query_array) . ") ",false);
            if($item = db_fetch_array($item_query))
            {
                return true;
            }
            else
            {                
                return false;
            }
        }
    }

    static public function get_comments_access_schema($entities_id, $access_groups_id)
    {
        $access_schema = array();

        $acess_info_query = db_query("select access_schema from app_comments_access where entities_id='" . db_input($entities_id) . "' and access_groups_id='" . db_input($access_groups_id) . "'");
        if($acess_info = db_fetch_array($acess_info_query))
        {
            $access_schema = explode(',', $acess_info['access_schema']);
        }

        return $access_schema;
    }

    static public function has_comments_access($access, $comments_access_schema = null, $check_logged_user = true)
    {
        global $current_comments_access_schema, $app_user;

        //administrator have full access
        if($app_user['group_id'] == 0 and $check_logged_user)
        {
            return true;
        }

        if(isset($comments_access_schema))
        {
            $schema = $comments_access_schema;
        }
        else
        {
            $schema = $current_comments_access_schema;
        }

        return in_array($access, $schema);
    }

    static public function has_reports_access()
    {
        global $app_user;

        //administrator have full access
        if($app_user['group_id'] == 0)
        {
            return true;
        }
        else
        {
            $acccess_query = db_query("select * from app_entities_access where access_groups_id='" . db_input($app_user['group_id']) . "' and find_in_set('reports',access_schema)");
            if($acccess = db_fetch_array($acccess_query))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }

    static function set_clietnt_id()
    {
        global $app_user;

        if(!$app_user['client_id'])
        {
            $client_id = mt_rand(100000, 999999) . $app_user['id'];
            db_query("update app_entity_1 set client_id={$client_id} where id={$app_user['id']}");

            $app_user['client_id'] = $client_id;
        }
    }

    static public function login($username, $password, $remember_me, $password_hashed = null, $redirect_to = null)
    {
        global $alerts, $_GET;

        $user_query = db_query("select * from app_entity_1 where field_12='" . db_input($username) . "' " . (isset($password_hashed) ? " and password='" . db_input($password_hashed) . "'" : ""));
        if($user = db_fetch_array($user_query))
        {
            if($user['field_5'] == 1)
            {
                $hasher = new PasswordHash(11, false);


                if(isset($password_hashed))
                {
                    app_session_register('app_logged_users_id', $user['id']);
                    
                    users_login_log::success($username, $user['id']);

                    if(!isset($_GET['action']))
                    {
                        redirect_to($_GET['module'], get_all_get_url_params());
                    }
                    else
                    {
                        redirect_to('dashboard/');
                    }
                }
                elseif($hasher->CheckPassword($password, $user['password']))
                {

                    app_session_register('app_logged_users_id', $user['id']);

                    //login log
                    if(CFG_2STEP_VERIFICATION_ENABLED != 1)
                    {
                        users_login_log::success($username, $user['id']);
                    }

                    if($remember_me == 1)
                    {
                        setcookie('app_remember_me', 1, time() + 60 * 60 * 24 * 30, '/');
                        setcookie('app_stay_logged', 1, time() + 60 * 60 * 24 * 30, '/');
                        setcookie('app_remember_user', base64_encode($user['field_12']), time() + 60 * 60 * 24 * 30, '/');
                        setcookie('app_remember_pass', base64_encode($user['password']), time() + 60 * 60 * 24 * 30, '/');
                    }
                    else
                    {
                        setcookie('app_remember_me', '', time() - 3600, '/');
                        setcookie('app_stay_logged', '', time() - 3600, '/');
                        setcookie('app_remember_user', '', time() - 3600, '/');
                        setcookie('app_remember_pass', '', time() - 3600, '/');
                    }
                    
                    who_is_online::set($user['id']);

                    if(isset($_COOKIE['app_login_redirect_to']))
                    {
                        //setcookie('app_login_redirect_to','',time() - 3600,'/');
                        redirect_to(str_replace('module=', '', $_COOKIE['app_login_redirect_to']));
                    }
                    else
                    {
                        redirect_to('dashboard/');
                    }
                }
                else
                {
                    //login log
                    users_login_log::fail($username);

                    if(!defined('TEXT_USER_NOT_FOUND'))
                        require('includes/languages/' . CFG_APP_LANGUAGE);

                    $alerts->add(TEXT_USER_NOT_FOUND, 'error');
                    redirect_to('users/login');
                }
            }
            else
            {
                //login log
                users_login_log::fail($username);

                if(!defined('TEXT_USER_NOT_FOUND'))
                    require('includes/languages/' . CFG_APP_LANGUAGE);

                $alerts->add(TEXT_USER_IS_NOT_ACTIVE, 'error');
                redirect_to('users/login');
            }
        }
        else
        {
            //login log
            users_login_log::fail($username);

            if(!defined('TEXT_USER_NOT_FOUND'))
                require('includes/languages/' . CFG_APP_LANGUAGE);

            $alerts->add(TEXT_USER_NOT_FOUND, 'error');
            redirect_to('users/login');
        }
    }

}
