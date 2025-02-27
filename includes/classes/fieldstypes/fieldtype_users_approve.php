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

class fieldtype_users_approve
{

    public $options;

    function __construct()
    {
        $this->options = array('title' => TEXT_FIELDTYPE_USERS_APPROVE_TITLE);
    }

    function get_configuration($params = array())
    {
        $entity_info = db_find('app_entities', $params['entities_id']);

        $cfg = array();
        $cfg[TEXT_SETTINGS][] = array('title' => TEXT_DISPLAY_USERS_AS,
            'name' => 'display_as',
            'type' => 'dropdown',
            'params' => array('class' => 'form-control input-xlarge'),
            'choices' => array('dropdown' => TEXT_DISPLAY_USERS_AS_DROPDOWN, 'checkboxes' => TEXT_DISPLAY_USERS_AS_CHECKBOXES, 'dropdown_muliple' => TEXT_DISPLAY_USERS_AS_DROPDOWN_MULTIPLE));

        

        $cfg[TEXT_SETTINGS][] = array('title' => TEXT_DISABLE_NOTIFICATIONS, 'name' => 'disable_notification', 'type' => 'checkbox', 'tooltip_icon' => TEXT_DISABLE_NOTIFICATIONS_FIELDS_INFO);

        if($entity_info['parent_id'] > 0)
        {
            $cfg[TEXT_SETTINGS][] = array('title' => TEXT_DISABLE_USERS_DEPENDENCY, 'name' => 'disable_dependency', 'type' => 'checkbox', 'tooltip_icon' => TEXT_DISABLE_USERS_DEPENDENCY_INFO);
        }
        
        $cfg[TEXT_LIST][] = array('title' => TEXT_DEFAULT, 'name' => 'users_by_default', 'type' => 'dropdown', 'choices' => users::get_choices(), 'params' => array('class' => 'form-control input-xlarge chosen-select', 'multiple' => 'multiple'));
        
        
        $cfg[TEXT_LIST][] = array('title' => TEXT_LIMIT_LIST,  'type' => 'section', 'html'=>'<p class="form-section-description">' . TEXT_LIMIT_LIST_INFO . '</p>');
        $cfg[TEXT_LIST][] = array('title' => TEXT_USERS_GROUPS, 'name' => 'user_groups_in_list', 'type' => 'dropdown', 'choices' => access_groups::get_choices_by_entity($params['entities_id']), 'params' => array('class' => 'form-control input-xlarge chosen-select', 'multiple' => 'multiple'));        
        $cfg[TEXT_LIST][] = array('title' => TEXT_USERS, 'name' => 'users_in_list', 'type' => 'dropdown', 'choices' => users::get_choices(), 'params' => array('class' => 'form-control input-xlarge chosen-select', 'multiple' => 'multiple'));
        $cfg[TEXT_LIST][] = array('title' => TEXT_MYSQL_QUERY, 'name'=>'mysql_query_where', 'type'=>'textarea', 
            'tooltip'=>TEXT_AVAILABLE_VALUES . ': <code>[current_user_id]</code>, <code>[current_user_group_id]</code><br>' . TEXT_EXAMPLE . ': <code>e.parent_item_id = (select parent_item_id from app_entity_1 where id=[current_user_id])</code>', 
            'params'=>array('class'=>'form-control code'));
        
        

        $cfg[TEXT_SETTINGS][] = array('title' => TEXT_HIDE_ADMIN, 'name' => 'hide_admin', 'type' => 'checkbox');
        $cfg[TEXT_SETTINGS][] = array('title' => TEXT_HIDE_FIELD_NAME, 'name' => 'hide_field_name', 'type' => 'checkbox', 'tooltip_icon' => TEXT_HIDE_FIELD_NAME_TIP);
        $cfg[TEXT_SETTINGS][] = array('title' => TEXT_ALLOW_SEARCH, 'name' => 'allow_search', 'type' => 'checkbox', 'tooltip_icon' => TEXT_ALLOW_SEARCH_TIP);
        $cfg[TEXT_SETTINGS][] = array('title' => TEXT_HIDE_FIELD_IF_EMPTY, 'name' => 'hide_field_if_empty', 'type' => 'checkbox', 'tooltip_icon' => TEXT_HIDE_FIELD_IF_EMPTY_TIP);

        
        $cfg[TEXT_BUTTON][] = array('title' => TEXT_APPROVE,  'type' => 'section');
        $cfg[TEXT_BUTTON][] = array('title' => TEXT_BUTTON_TITLE, 'name' => 'button_title', 'type' => 'input', 'params' => array('class' => 'form-control input-medium'), 'tooltip_icon' => TEXT_DEFAULT . ': ' . TEXT_APPROVE);
        $cfg[TEXT_BUTTON][] = array('title' => TEXT_ICON, 'name' => 'button_icon', 'type' => 'input_icon', 'params' => array('class' => 'form-control input-medium'));
        $cfg[TEXT_BUTTON][] = array('title' => TEXT_COLOR, 'name' => 'button_color', 'type' => 'colorpicker');
        
        $cfg[TEXT_BUTTON][] = array('title' => TEXT_CANCEL,  'type' => 'section');
        $cfg[TEXT_BUTTON][] = array('title' => TEXT_USE_CANCEL_BUTTON, 'name' => 'use_cancel_button', 'tooltip_icon'=> TEXT_FIELDTYPE_USERS_APPROVE_CANCEL_BTN_TIP, 'type' => 'dropdown', 'choices' => ['0' => TEXT_NO, '1' => TEXT_YES], 'params' => array('class' => 'form-control input-small'));
        
        $choices = ['' => ''];

        $fields_query = db_query("select * from app_fields where type in ('fieldtype_stages','fieldtype_dropdown','fieldtype_radioboxes','fieldtype_dropdown_multiple','fieldtype_tags','fieldtype_checkboxes','fieldtype_autostatus') and entities_id='" . db_input($_POST['entities_id']) . "'");
        while($fields = db_fetch_array($fields_query))
        {
            $choices[$fields['id']] = $fields['name'];
        }


        $cfg[TEXT_BUTTON][] = array('title' => TEXT_HIDE_BUTTON,
            'name' => 'disable_cancel_btn_by_field',
            'type' => 'dropdown',
            'choices' => $choices,
            'tooltip_icon' => TEXT_FIELDTYPE_USERS_APPROVE_HIDE_CANCEL_BTN_TIP,
            'params' => array('class' => 'form-control input-large', 'onChange' => 'fields_types_ajax_configuration(\'disable_cancel_btn_by_field_values\',this.value)'),
        );

        $cfg[TEXT_BUTTON][] = array('name' => 'disable_cancel_btn_by_field_values', 'type' => 'ajax', 'html' => '<script>fields_types_ajax_configuration(\'disable_cancel_btn_by_field_values\',$("#fields_configuration_disable_cancel_btn_by_field").val())</script>');


        $cfg[TEXT_ACTION][] = array('title' => TEXT_CONFIRMATION_WINDOW, 'name' => 'confirmation_window', 'type' => 'dropdown', 'choices' => ['0' => TEXT_NO, '1' => TEXT_YES], 'params' => array('class' => 'form-control input-small'));
        $cfg[TEXT_ACTION][] = array('title' => TEXT_CONFIRMATION_TEXT, 'name' => 'confirmation_text', 'type' => 'textarea', 'params' => array('class' => 'form-control textarea-small'), 'tooltip_icon' => TEXT_DEFAULT . ': ' . TEXT_ARE_YOU_SURE);

        $cfg[TEXT_ACTION][] = array('title' => TEXT_ADD_COMMENT, 'name' => 'add_comment', 'type' => 'dropdown', 'choices' => ['0' => TEXT_NO, '1' => TEXT_YES], 'params' => array('class' => 'form-control input-small'));
        $cfg[TEXT_ACTION][] = array('title' => TEXT_COMMENT_TEXT, 'name' => 'comment_text', 'type' => 'textarea', 'params' => array('class' => 'form-control textarea-small'), 'tooltip_icon' => TEXT_DEFAULT . ': ' . TEXT_APPROVED);

        $cfg[TEXT_SIGNATURE][] = array('title' => TEXT_SIGNATURE, 'name' => 'use_signature', 'type' => 'dropdown', 'choices' => ['0' => TEXT_NO, '1' => TEXT_YES], 'params' => array('class' => 'form-control input-small'));
        $cfg[TEXT_SIGNATURE][] = array('title' => TEXT_DESCRIPTION, 'name' => 'signature_description', 'type' => 'textarea', 'params' => array('class' => 'form-control textarea-small'));
        $cfg[TEXT_SIGNATURE][] = array('title' => TEXT_WIDTH_IN_ITEM_PAGE, 'name' => 'signature_width_item_page', 'type' => 'input', 'params' => array('class' => 'form-control input-medium'), 'tooltip_icon' => TEXT_WIDTH_IN_ITEM_PAGE_INFO);
        $cfg[TEXT_SIGNATURE][] = array('title' => TEXT_WIDTH_IN_PRINT_PAGE, 'name' => 'signature_width_print_page', 'type' => 'input', 'params' => array('class' => 'form-control input-medium'), 'tooltip_icon' => TEXT_WIDTH_IN_PRINT_PAGE_INFO);

        $choices = [];
        $choices[0] = '';

        if(is_ext_installed())
        {
            $processes_query = db_query("select id, name from app_ext_processes where entities_id='" . $params['entities_id'] . "' order by sort_order, name");
            while($processes = db_fetch_array($processes_query))
            {
                $choices[$processes['id']] = $processes['name'];
            }
        }

        $cfg[TEXT_ACTION][] = array('title' => TEXT_ALL_USERS_APPROVED, 'name' => 'run_process', 'type' => 'dropdown', 'choices' => $choices, 'params' => array('class' => 'form-control input-large'), 'tooltip' => TEXT_ALL_USERS_APPROVED_INFO);

        return $cfg;
    }
    
    function get_ajax_configuration($name, $value)
    {
        $cfg = array();

        switch($name)
        {
            case 'disable_cancel_btn_by_field_values':
                if(strlen($value))
                {
                    $field_query = db_query("select id, name, configuration from app_fields where id='" . $value . "'");
                    if($field = db_fetch_array($field_query))
                    {
                        $field_cfg = new fields_types_cfg($field['configuration']);

                        if($field_cfg->get('use_global_list') > 0)
                        {
                            $choices = global_lists::get_choices($field_cfg->get('use_global_list'), false);
                        }
                        else
                        {
                            $choices = fields_choices::get_choices($field['id'], false);
                        }

                        $cfg[] = array(
                            'title' => $field['name'],
                            'name' => 'disable_cancel_btn_by_field_choices',
                            'type' => 'dropdown',
                            'choices' => $choices,
                            'params' => array('class' => 'form-control input-large chosen-select', 'multiple' => 'multiple'),
                        );
                    }
                }
                break;
        }

        return $cfg;
    }

    static function get_choices($field, $params, $value = '')
    {
        global $app_users_cache, $app_user;

        $cfg = new settings($field['configuration']);

        $entities_id = $field['entities_id'];

        //get access schema
        $access_schema = users::get_entities_access_schema_by_groups($entities_id);

        //check if parent item has users fields and if users are assigned
        $has_parent_users = false;
        $parent_users_list = array();

        if(isset($params['parent_entity_item_id']) and $params['parent_entity_item_id'] > 0 and $cfg->get('disable_dependency') != 1)
        {
            if($parent_users_list = items::get_paretn_users_list($entities_id, $params['parent_entity_item_id']))
            {
                $has_parent_users = true;
            }
        }

        //get users choices
        //select all active users or already assigned users
        $where_sql =  "e.field_5=1";

        //hide administrators
        if($cfg->get('hide_admin') == 1)
        {
            $where_sql .= " and e.field_6>0 ";
        }
        
        $user_groups_in_list = is_array($cfg->get('user_groups_in_list')) ? $cfg->get('user_groups_in_list') : [];
        $users_in_list = is_array($cfg->get('users_in_list')) ? $cfg->get('users_in_list') : [];
        //list limit
        if(count($user_groups_in_list) or count($users_in_list))
        {
            $where_or_sql = [];
            
            if(count($user_groups_in_list))
            {
                $where_or_sql[] ="e.field_6 in (" . db_input_in($user_groups_in_list) . ")";
            }
            
            if(count($users_in_list))
            {
                $where_or_sql[] ="e.id in (" . db_input_in($users_in_list) . ")";
            }
            
            if(count($where_or_sql))
            {
                $where_sql .= " and (" . implode(" or ", $where_or_sql) . ")";
            }
            
        }
        
        if(strlen($cfg->get('mysql_query_where')))
        {
            $mysql_query_where = str_replace(['[current_user_id]','[current_user_group_id]'],[$app_user['id'],$app_user['group_id']],$cfg->get('mysql_query_where'));
            $where_sql .= " and (" . $mysql_query_where . ")";
        }
        
        if(strlen($value))
        {
            $where_sql = "({$where_sql}) or  e.id in (" . db_input_in($value) . ")";
        }

        $choices = array();
        $order_by_sql = (CFG_APP_DISPLAY_USER_NAME_ORDER == 'firstname_lastname' ? 'e.field_7, e.field_8' : 'e.field_8, e.field_7');
        $users_query = db_query("select e.*,a.name as group_name from app_entity_1 e left join app_access_groups a on a.id=e.field_6 where {$where_sql} order by group_name, " . $order_by_sql, false);
        while($users = db_fetch_array($users_query))
        {
            if(!isset($access_schema[$users['field_6']]))
            {
                $access_schema[$users['field_6']] = array();
            }

            if($users['field_6'] == 0 or in_array('view', $access_schema[$users['field_6']]) or in_array('view_assigned', $access_schema[$users['field_6']]))
            {
                //check parent users and check already assigned
                if($has_parent_users and!in_array($users['id'], $parent_users_list) and!in_array($users['id'], explode(',', $value)))
                    continue;

                $group_name = ((isset($users['group_name']) and strlen($users['group_name'])) > 0 ? $users['group_name'] : TEXT_ADMINISTRATOR);
                $choices[$group_name][$users['id']] = $app_users_cache[$users['id']]['name'];
            }
        }

        return $choices;
    }

    function render($field, $obj, $params = array())
    {
        global $app_users_cache, $app_user;

        $cfg = new fields_types_cfg($field['configuration']);

        $entities_id = $field['entities_id'];

        if($params['is_new_item'] == 1)
        {
            $value = (is_array($cfg->get('users_by_default')) ? implode(',', $cfg->get('users_by_default')) : '');
        }
        else
        {
            $value = (strlen($obj['field_' . $field['id']]) ? $obj['field_' . $field['id']] : '');
        }

        $choices = self::get_choices($field, $params, $value);

        if($cfg->get('display_as') == 'dropdown')
        {
            //add empty value for comment form
            $choices = ($params['form'] == 'comment' ? array('' => '') + $choices : $choices);

            $attributes = array('class' => 'form-control chosen-select input-large field_' . $field['id'] . ($field['is_required'] == 1 ? ' required' : ''));

            return select_tag('fields[' . $field['id'] . ']', array('' => TEXT_NONE) + $choices, $value, $attributes) . fields_types::custom_error_handler($field['id']);
        }
        elseif($cfg->get('display_as') == 'checkboxes')
        {
            $attributes = array('class' => 'field_' . $field['id'] . ($field['is_required'] == 1 ? ' required' : ''));

            return '<div class="checkboxes_list ' . ($field['is_required'] == 1 ? ' required' : '') . '">' . select_checkboxes_tag('fields[' . $field['id'] . ']', $choices, $value, $attributes) . '</div>';
        }
        elseif($cfg->get('display_as') == 'dropdown_muliple')
        {
            $attributes = array('class' => 'form-control input-xlarge chosen-select field_' . $field['id'] . ($field['is_required'] == 1 ? ' required' : ''),
                'multiple' => 'multiple',
                'data-placeholder' => TEXT_SELECT_SOME_VALUES);
            return select_tag('fields[' . $field['id'] . '][]', $choices, explode(',', $value), $attributes) . fields_types::custom_error_handler($field['id']);
        }
    }

    function process($options)
    {
        global $app_send_to, $app_send_to_new_assigned;

        $cfg = new fields_types_cfg($options['field']['configuration']);

        if($cfg->get('disable_notification') != 1)
        {
            if(is_array($options['value']))
            {
                $app_send_to = array_merge($options['value'], $app_send_to);
            }
            else
            {
                $app_send_to[] = $options['value'];
            }
        }

        $value = (is_array($options['value']) ? implode(',', $options['value']) : $options['value']);

        //check if value changed
        if($cfg->get('disable_notification') != 1)
        {
            if(!$options['is_new_item'])
            {
                if($value != $options['current_field_value'])
                {
                    foreach(array_diff(explode(',', $value), explode(',', $options['current_field_value'])) as $v)
                    {
                        $app_send_to_new_assigned[] = $v;
                    }
                }
            }
        }

        //reset approved user in no users assigned
        if(!$options['is_new_item'])
        {
            if(!strlen($value))
            {
                db_query("delete from app_approved_items where entities_id='" . $options['field']['entities_id'] . "' and items_id='" . $options['item']['id'] . "' and fields_id='" . $options['field']['id'] . "'");
            }
            else
            {
                db_query("delete from app_approved_items where entities_id='" . $options['field']['entities_id'] . "' and items_id='" . $options['item']['id'] . "' and fields_id='" . $options['field']['id'] . "' and users_id not in (" . $value . ")");
            }
        }

        return $value;
    }

    function output($options)
    {
        global $app_users_cache, $app_user, $app_path, $app_module_path;

        if(!strlen($options['value']))
            return '';

        $cfg = new fields_types_cfg($options['field']['configuration']);

        //print_rr($options);

        if(isset($options['is_print']) and $cfg->get('use_signature') == 1)
        {
            $html = '';

            $approved_users = approved_items::get_approved_users_by_field($options['field']['entities_id'], $options['item']['id'], $options['field']['id']);

            $current_user_approved = false;

            $users_list = array();
            foreach(explode(',', $options['value']) as $id)
            {
                if(isset($app_users_cache[$id]))
                {
                    $signagure_html = '';

                    if(isset($approved_users[$id]))
                    {
                        if(strlen($approved_users[$id]['signature']))
                        {
                            $signagure_html = '<img src="' . $approved_users[$id]['signature'] . '" width="' . (strlen($cfg->get('signature_width_print_page')) ? (int) $cfg->get('signature_width_print_page') : 150) . '">';
                        }
                    }

                    $html .= '
        			<tr>
        				
        				<td>' . $app_users_cache[$id]['name'] . '</td>
        				<td style="padding-left: 5px;">' . $signagure_html . '</td>
        			</tr>';
                }
            }

            if(strlen($html))
            {
                $html = '
      			<table>
      				' . $html . '
      			</table>
      			';
            }

            return $html;
        }
        elseif(isset($options['is_export']) or isset($options['is_email']) or isset($options['is_comments_listing']))
        {
            $users_list = array();
            foreach(explode(',', $options['value']) as $id)
            {
                if(isset($app_users_cache[$id]))
                {
                    $users_list[] = $app_users_cache[$id]['name'];
                }
            }

            if(isset($options['is_email']) or isset($options['is_comments_listing']))
            {
                return implode('<br>', $users_list);
            }
            else
            {
                return implode(', ', $users_list);
            }
        }
        else
        {
            $html = '';

            $approved_users = approved_items::get_approved_users_by_field($options['field']['entities_id'], $options['item']['id'], $options['field']['id']);

            $current_user_approved = false;

            $users_list = array();
            foreach(explode(',', $options['value']) as $id)
            {
                if(isset($app_users_cache[$id]))
                {

                    $icon = '<i class="fa fa-minus"></i>';

                    $signagure_html = '';

                    if(isset($approved_users[$id]))
                    {
                        $icon = '<i class="fa fa-check fa-success"></i>';

                        if($id == $app_user['id'])
                            $current_user_approved = true;

                        if(strlen($approved_users[$id]['signature']))
                        {
                            $signagure_html = '<img src="' . $approved_users[$id]['signature'] . '" width="' . (strlen($cfg->get('signature_width_item_page')) ? (int) $cfg->get('signature_width_item_page') : 150) . '">';
                        }
                    }


                    $html .= '
        			<tr>
        				<td style="padding-right: 5px;">' . $icon . '</td>
        				<td><span class="user-name" ' . users::render_publi_profile($app_users_cache[$id], true) . '>' . $app_users_cache[$id]['name'] . '</span></td>
        				<td style="padding-left: 5px;">' . $signagure_html . '</td>		
        			</tr>';
                }
            }

            if(strlen($html))
            {
                $html = '
      			<table>
      				' . $html . '
      			</table>
      			';

                if(in_array($app_user['id'], explode(',', $options['value'])) and!$current_user_approved and $this->check_button_filter($options))
                {
                    $button_title = (strlen($cfg->get('button_icon')) ? app_render_icon($cfg->get('button_icon')) . ' ' : '') . (strlen($cfg->get('button_title')) ? $cfg->get('button_title') : TEXT_APPROVE);

                    $btn_css = 'btn-color-' . $options['field']['id'];

                    $path_info = items::get_path_info($options['field']['entities_id'], $options['item']['id'], $options['item']);


                    $redirect_to = '&redirect_to=items';
                    
                    if(strlen($options['redirect_to']??'') > 0)
                    {
                        $redirect_to = '&redirect_to=' . $options['redirect_to'];
                    }                    
                    elseif($app_module_path == 'items/info')
                    {
                        $redirect_to = '&redirect_to=items_info';
                    }

                    //print_rr($options);      		

                    $redirect_to .= (isset($_POST['page']) ? '&gotopage[' . $options['reports_id'] . ']=' . $_POST['page'] : '');

                    if($cfg->get('confirmation_window') == 1 or $cfg->get('use_signature') == 1)
                    {
                        $button_html = button_tag($button_title, url_for('items/approve', 'fields_id=' . $options['field']['id'] . '&path=' . $path_info['full_path'] . $redirect_to), true, ['class' => 'btn btn-primary btn-sm ' . $btn_css]);
                    }
                    else
                    {
                        $button_html = button_tag($button_title, url_for('items/approve', 'action=approve&fields_id=' . $options['field']['id'] . '&path=' . $path_info['full_path'] . $redirect_to), false, ['class' => 'btn btn-primary btn-sm prevent-double-click ' . $btn_css]);
                    }

                    $html .= '<div style="padding-top: 5px;">' . $button_html . app_button_color_css($cfg->get('button_color'), $btn_css) . '</div>';
                }
                //displya cancel button
                elseif($cfg->get('use_cancel_button')==1 and in_array($app_user['id'], explode(',', $options['value'])) and $current_user_approved and $this->check_button_filter($options))
                {
                    if(strlen($cfg->get('disable_cancel_btn_by_field')))
                    {
                        if(isset($options['item']['field_' . $cfg->get('disable_cancel_btn_by_field')]))
                        {
                            if(is_array($cfg->get('disable_cancel_btn_by_field_choices')))
                                foreach($cfg->get('disable_cancel_btn_by_field_choices') as $choices_id)
                                {
                                    if(in_array($choices_id, explode(',', $options['item']['field_' . $cfg->get('disable_cancel_btn_by_field')])))
                                    {
                                        return $html;
                                    }
                                }
                        }
                    }
                    
                    $path_info = items::get_path_info($options['field']['entities_id'], $options['item']['id'], $options['item']);
                    
                    $redirect_to = '&redirect_to=items';
                    
                    if(strlen($options['redirect_to']??'') > 0)
                    {
                        $redirect_to = '&redirect_to=' . $options['redirect_to'];
                    }                    
                    elseif($app_module_path == 'items/info')
                    {
                        $redirect_to = '&redirect_to=items_info';
                    }
                    
                    $html .= '<a href="' . url_for('items/approve', 'action=cancel_approve&fields_id=' . $options['field']['id'] . '&path=' . $path_info['full_path'] . $redirect_to) . '" class="btn btn-default btn-sm" onclick="return confirm(\'' . TEXT_ARE_YOU_SURE . '\')">' . TEXT_CANCEL . '</a>';
                }
            }

            return $html;
        }
    }

    function check_button_filter($options)
    {
        global $sql_query_having;

        $field_id = $options['field']['id'];
        $entities_id = $options['field']['entities_id'];

        $reports_info_query = db_query("select * from app_reports where entities_id='" . db_input($entities_id) . "' and reports_type='fieldfilter" . $field_id . "'");
        if($reports_info = db_fetch_array($reports_info_query))
        {
            $reports_fileds = [];
            $filtes_query = db_query("select fields_id from app_reports_filters where reports_id='" . $reports_info['id'] . "'");
            while($filtes = db_fetch_array($filtes_query))
            {
                $reports_fileds[] = $filtes['fields_id'];
            }

            $listing_sql_query = "e.id='" . $options['item']['id'] . "'";
            $listing_sql_query_having = '';

            $listing_sql_select = fieldtype_formula::prepare_query_select($reports_info['entities_id'], '', false, ['fields_in_query' => implode(',', $reports_fileds)]);

            $listing_sql_query = reports::add_filters_query($reports_info['id'], $listing_sql_query);

            //prepare having query for formula fields
            if(isset($sql_query_having[$reports_info['entities_id']]))
            {
                $listing_sql_query_having = reports::prepare_filters_having_query($sql_query_having[$reports_info['entities_id']]);
            }

            $listing_sql = "select  e.* " . $listing_sql_select . " from app_entity_" . $reports_info['entities_id'] . " e where " . $listing_sql_query . $listing_sql_query_having;
            $items_query = db_query($listing_sql, false);
            if($item = db_fetch_array($items_query))
            {
                return true;
            }
            else
            {
                return false;
            }
        }

        return true;
    }

    function reports_query($options)
    {
        global $app_user;

        $filters = $options['filters'];
        $sql_query = $options['sql_query'];

        if(strlen($filters['filters_values']) > 0)
        {
            $filters['filters_values'] = str_replace('current_user_id', $app_user['id'], $filters['filters_values']);

            if($filters['filters_condition'] == 'include_signature')
            {
                $sql_query[] = "(select count(*) from app_entity_" . $options['entities_id'] . "_values as cv where cv.items_id=e.id and cv.fields_id='" . db_input($options['filters']['fields_id']) . "' and cv.value in (" . $filters['filters_values'] . ") 
                                    and (select count(*) from app_approved_items ai where ai.entities_id={$options['entities_id']} and ai.items_id=e.id and ai.fields_id={$options['filters']['fields_id']} and ai.users_id=cv.value)=0 
                                )>0";                                                                                        
            }
            else
            {
                $sql_query[] = "(select count(*) from app_entity_" . $options['entities_id'] . "_values as cv where cv.items_id=e.id and cv.fields_id='" . db_input($options['filters']['fields_id']) . "' and cv.value in (" . $filters['filters_values'] . ")) " . ($filters['filters_condition'] == 'include' ? '>0' : '=0');
            }
        }

        return $sql_query;
    }

}
