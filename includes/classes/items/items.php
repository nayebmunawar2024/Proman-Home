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

class items
{

    public static function get_info($entities_id, $items_id)
    {
        $item_query = db_query("select e.* " . fieldtype_formula::prepare_query_select($entities_id, '') . " from app_entity_" . $entities_id . " e where id='" . $items_id . "'");
        $item = db_fetch_array($item_query);

        return $item;
    }

    public static function get_items_to_delete($entities_id, $itesm_list)
    {
        $entities_query = db_query("select id from app_entities where parent_id = '" . $entities_id . "'");
        while ($entities = db_fetch_array($entities_query))
        {
            $items_query = db_query("select id from app_entity_" . $entities['id'] . " where parent_item_id in (" . implode(',', $itesm_list[$entities_id]) . ") ");
            while ($items = db_fetch_array($items_query))
            {
                $itesm_list[$entities['id']][] = $items['id'];
            }

            if (isset($itesm_list[$entities['id']]))
            {
                $itesm_list = self::get_items_to_delete($entities['id'], $itesm_list);
            }
        }

        return $itesm_list;
    }

    public static function delete($entities_id, $items_id)
    {
        global $app_user;

        //stop delete yourself 
        if ($entities_id == 1 and $items_id == $app_user['id'])
            return false;

        //run process before delete
        if(is_ext_installed())
        {
            //run actions before delete records
            $processes = new processes($entities_id);
            $processes->run_before_delete($items_id);
        }
        
        $item_info = db_find("app_entity_" . $entities_id, $items_id);
        
        $parent_item_id = ($item_info['parent_id']>0 ? tree_table::get_top_parent_item_id($entities_id,$item_info['parent_id']) : 0); 
                
        //reset parent items
        db_query("update app_entity_" . $entities_id . "  set parent_id='" . $item_info['parent_id'] . "' where parent_id='" . $items_id . "'");

        attachments::delete_attachments($entities_id, $items_id);
        onlyoffice::delete_attachments($entities_id, $items_id);
        file_storage_field::delete_by_item_id($entities_id, $items_id);

        //handle actions before delete
        if (is_ext_installed())
        {
            //subscribe
            $modules = new modules('mailing');
            $mailing = new mailing($entities_id, $items_id);
            $mailing->delete();

            //track changes
            $log = new track_changes($entities_id, $items_id);
            $log->log_delete();
        }

        db_delete_row('app_entity_' . $entities_id, $items_id);

        comments::delete_item_comments($entities_id, $items_id);

        reports::delete_reports_by_item_id($entities_id, $items_id);

        choices_values::delete_by_item_id($entities_id, $items_id);

        related_records::delete_related_by_item_id($entities_id, $items_id);

        mind_map::delete($entities_id, $items_id);   
                
        //delete notifications
        db_query("delete from app_users_notifications where entities_id='" . $entities_id . "' and items_id='" . $items_id . "'");

        //delete roles
        db_query("delete from app_user_roles_to_items where entities_id='" . $entities_id . "' and items_id='" . $items_id . "'");

        //delete approved records
        db_query("delete from app_approved_items where entities_id='" . $entities_id . "' and items_id='" . $items_id . "'");

        image_map::delete_markers($entities_id, $items_id);
        image_map_nested::delete_markers($entities_id, $items_id);
        
        favorites::delete_by_item_id($entities_id, $items_id);
        
        if($parent_item_id)
        {
            //tree table recalculated count/sum
            fieldtype_nested_calculations::update_items_fields($entities_id,$parent_item_id, 0);
        }

        if (is_ext_installed())
        {
            //delete timers
            db_query("delete from app_ext_timer where entities_id='" . $entities_id . "' and items_id='" . $items_id . "'");

            //delete gantt
            db_query("delete from app_ext_ganttchart_depends where entities_id='" . $entities_id . "' and (item_id='" . db_input($items_id) . "' or depends_id='" . db_input($items_id) . "')");

            //delete log changes
            track_changes::delete_log($entities_id, $items_id);

            //delete recurring tasks
            recurring_tasks::delete($entities_id, $items_id);

            //delete mind map items
            mind_map_reports::delete($entities_id, $items_id);

            //delete items to mail
            db_query("delete from app_ext_mail_to_items where entities_id='" . $entities_id . "' and items_id='" . $items_id . "'");

            //hande users delete
            if ($entities_id == 1)
            {
                //delete cryptopro cert
                db_query("delete from app_ext_cryptopro_certificates where users_id='" . $items_id . "'");
            }                        
        }

        //hande users delete
        if ($entities_id == 1)
        {
            users_login_log::delete_by_user_id($items_id);
            portlets::delete_by_user_id($items_id);
            who_is_online::remove($items_id);
        }
    }

    public static function get_choices($entity_id, $add_empty = false, $empty_text = '',$reports_id=false)
    {
        $listing_sql_query = '';
        $listing_sql_query_join = '';

        //check view assigned only access
        $listing_sql_query = items::add_access_query($entity_id, $listing_sql_query);
        
        if($reports_id)
        {
            $listing_sql_query = reports::add_filters_query($reports_id, $listing_sql_query);
        }

        //include access to parent records
        $listing_sql_query .= items::add_access_query_for_parent_entities($entity_id);

        $listing_sql_query .= items::add_listing_order_query_by_entity_id($entity_id);

        //build query
        $listing_sql = "select e.* from app_entity_" . $entity_id . " e " . $listing_sql_query_join . "where e.id>0 " . $listing_sql_query;
        $items_query = db_query($listing_sql);

        $choices = array();

        if ($add_empty)
        {
            $choices[''] = $empty_text;
        }

        while ($item = db_fetch_array($items_query))
        {
            $path_info = items::get_path_info($entity_id, $item['id']);

            //print_r($path_info);

            $parent_name = '';
            if (strlen($path_info['parent_name']) > 0)
            {
                $parent_name = str_replace('<br>', ' / ', $path_info['parent_name']) . ' / ';
            }

            $choices[$item['id']] = $parent_name . self::get_heading_field($entity_id, $item['id']);
        }

        return $choices;
    }

    public static function get_choices_by_entity($entity_id, $parent_entity_id, $add_empty = false)
    {
        $listing_sql_query = '';
        $listing_sql_query_join = '';

        //add filters from defualt report
        $default_reports_query = db_query("select * from app_reports where entities_id='" . db_input($parent_entity_id) . "' and reports_type='default'");
        if ($default_reports = db_fetch_array($default_reports_query))
        {
            $listing_sql_query = reports::add_filters_query($default_reports['id'], $listing_sql_query);
        }

        //check view assigned only access
        $listing_sql_query = items::add_access_query($parent_entity_id, $listing_sql_query);

        //include access to parent records
        $listing_sql_query .= items::add_access_query_for_parent_entities($parent_entity_id);

        $listing_sql_query .= items::add_listing_order_query_by_entity_id($parent_entity_id);

        //build query
        $listing_sql = "select e.* from app_entity_" . $parent_entity_id . " e " . $listing_sql_query_join . "where e.id>0 " . $listing_sql_query;
        $items_query = db_query($listing_sql);

        $choices = array();

        if ($add_empty)
        {
            $choices[''] = '';
        }

        while ($item = db_fetch_array($items_query))
        {
            $path_info = items::get_path_info($parent_entity_id, $item['id']);

            //print_r($path_info);

            $parent_name = '';
            if (strlen($path_info['parent_name']) > 0)
            {
                $parent_name = str_replace('<br>', ' / ', $path_info['parent_name']) . ' / ';
            }

            $choices[$path_info['full_path'] . '/' . $entity_id] = $parent_name . self::get_heading_field($parent_entity_id, $item['id']);
        }

        return $choices;
    }

    public static function get_heading_field($entity_id, $item_id, $item_info = null)
    {
        global $app_users_cache;

        if ($entity_id == 1)
        {
            return (isset($app_users_cache[$item_id]) ? $app_users_cache[$item_id]['name'] : '');
        }

        $heading_field_id = fields::get_heading_id($entity_id);

        if ($heading_field_id and!$item_info)
        {
            $item_info = db_find('app_entity_' . $entity_id, $item_id);
        }
        
        return ($heading_field_id > 0 ? self::get_heading_field_value($heading_field_id, $item_info) : $item_id);
    }

    public static function get_heading_field_value($heading_field_id, $item_info)
    {
        global $app_choices_cache, $app_users_cache, $app_heading_fields_cache, $app_entities_cache;

        $heading_field_value = '';

        if (isset($item_info['field_' . $heading_field_id]))
        {
            $heading_field_value = $item_info['field_' . $heading_field_id];
        }

        if (isset($app_heading_fields_cache[$heading_field_id]))
        {
            $field_info = $app_heading_fields_cache[$heading_field_id];

            if (strlen($heading_field_value) == 0 and!in_array($field_info['type'], array('fieldtype_parent_value','fieldtype_id', 'fieldtype_created_by', 'fieldtype_date_added', 'fieldtype_text_pattern')))
            {
                return '';
            }

            switch ($field_info['type'])
            {
                case 'fieldtype_parent_value':                    
                    return self::get_heading_field($app_entities_cache[$field_info['entities_id']]['parent_id'],$item_info['parent_item_id']);
                    break;
                case 'fieldtype_auto_increment':
                case 'fieldtype_input_ip':
                    $output_options = array('class' => $field_info['type'],
                        'value' => $heading_field_value,
                        'field' => $field_info,
                        'item' => $item_info,
                        'is_export' => true,
                        'path' => $field_info['entities_id']);

                    return fields_types::output($output_options);
                    break;
                case 'fieldtype_text_pattern':
                    $output_options = array('class' => $field_info['type'],
                        'value' => '',
                        'field' => $field_info,
                        'item' => $item_info,
                        'is_export' => true,
                        'path' => $field_info['entities_id']);

                    return fields_types::output($output_options);
                    break;
                case 'fieldtype_id':
                    return (strlen($field_info['name']) ? $field_info['name'] . ': ' . $item_info['id'] : $item_info['id']);
                    break;
                case 'fieldtype_created_by':
                    if (isset($app_users_cache[$item_info['created_by']]))
                    {
                        return $app_users_cache[$item_info['created_by']]['name'];
                    } else
                    {
                        return '';
                    }
                    break;
                case 'fieldtype_date_added':
                    return format_date_time($item_info['date_added']);
                    break;
                case 'fieldtype_input_date':
                    return format_date($heading_field_value);
                    break;
                case 'fieldtype_input_date_extra':
                    $cfg = new fields_types_cfg($field_info['configuration']);
                    return format_date($heading_field_value,fieldtype_input_date_extra::get_date_format($cfg));
                    break;
                case 'fieldtype_input_datetime':
                    return format_date_time($heading_field_value);
                    break;
                case 'fieldtype_checkboxes':
                case 'fieldtype_radioboxes':
                case 'fieldtype_dropdown':
                case 'fieldtype_dropdown_multiple':
                case 'fieldtype_dropdown_multilevel':
                case 'fieldtype_grouped_users':
                case 'fieldtype_stages':
                case 'fieldtype_tags':
                case 'fieldtype_autostatus':

                    $cfg = new fields_types_cfg($field_info['configuration']);

                    if ($cfg->get('use_global_list') > 0)
                    {
                        return global_lists::render_value($heading_field_value, true);
                    } else
                    {
                        return fields_choices::render_value($heading_field_value, true);
                    }
                    break;
                case 'fieldtype_entity_multilevel':
                case 'fieldtype_entity_ajax':
                case 'fieldtype_entity':
                    $cfg = fields_types::parse_configuration($field_info['configuration']);

                    $entity_heading_field_id = false;
                    $fields_query = db_query("select f.* from app_fields f where f.is_heading=1 and  f.entities_id='" . db_input($cfg['entity_id']) . "'");
                    if ($fields = db_fetch_array($fields_query))
                    {
                        $entity_heading_field_id = $fields['id'];
                    }

                    $output = array();
                    foreach (explode(',', $heading_field_value) as $item_id)
                    {
                        $items_info_sql = "select e.* from app_entity_" . $cfg['entity_id'] . " e where e.id='" . db_input($item_id) . "'";
                        $items_query = db_query($items_info_sql);
                        if ($item = db_fetch_array($items_query))
                        {
                            if ($cfg['entity_id'] == 1)
                            {
                                $output[] = $app_users_cache[$item['id']]['name'];
                            } else
                            {

                                if ($entity_heading_field_id)
                                {
                                    $output[] = self::get_heading_field_value($entity_heading_field_id, $item);
                                } else
                                {
                                    $output[] = $item['id'];
                                }
                            }
                        }
                    }

                    return implode(', ', $output);

                    break;
                case 'fieldtype_users':
                case 'fieldtype_users_ajax':
                    $users_list = array();
                    foreach (explode(',', $heading_field_value) as $id)
                    {
                        if (isset($app_users_cache[$id]))
                        {
                            $users_list[] = $app_users_cache[$id]['name'];
                        }
                    }

                    return implode(', ', $users_list);
                    break;
                default:
                    return $heading_field_value;
                    break;
            }
        }
    }

    public static function get_breadcrumb_by_item_id($entity_id, $item_id)
    {
        $breadcrumb = array();
        $breadcrumb_html = array();

        $path_array = self::get_path_array($entity_id, $item_id);

        foreach ($path_array as $v)
        {
            $breadcrumb[] = $v['name'];
            $breadcrumb_html[] = '<a href="' . url_for('items/info', 'path=' . $v['path'], true) . '">' . $v['name'] . '</a>';
        }

        return array('text' => implode(' - ', $breadcrumb), 'html' => implode(' - ', $breadcrumb_html),'array'=>$breadcrumb);
    }

    public static function get_breadcrumb($path_array)
    {
        $breadcrumb = array();
        $path = '';

        foreach ($path_array as $v)
        {
            $vv = explode('-', $v);
            $entity_id = $vv[0];
            $item_id = (isset($vv[1]) ? $vv[1] : 0);

            $entity_info = db_find('app_entities', $entity_id);
            $entity_cfg = entities::get_cfg($entity_id);
            $heading_field_id = fields::get_heading_id($entity_id);

            $entitiy_name = (strlen($entity_cfg['listing_heading']) > 0 ? $entity_cfg['listing_heading'] : $entity_info['name']);

            //check if user have access to entity
            if (users::has_users_access_to_entity($entity_id))
            {
                $breadcrumb[] = array('url' => url_for('items/items', 'path=' . $path . $entity_id), 'title' => $entitiy_name);
            } else
            {
                $breadcrumb[] = array('title' => $entitiy_name);
            }

            if ($item_id > 0)
            {
                $item_name = '';

                $item_info_query = db_query("select e.* " . fieldtype_formula::prepare_query_select($entity_id, '') . " from app_entity_" . $entity_id . " e where e.id='" . $item_id . "'", false);
                if ($item_info = db_fetch_array($item_info_query))
                {
                    $item_name = ($heading_field_id > 0 ? self::get_heading_field_value($heading_field_id, $item_info) : $item_info['id']);
                }
                
                //parent items breadcrumb
                if(($item_info['parent_id']??0)>0)
                {
                    $parents = array_reverse(tree_table::get_parents($entity_id,$item_info['parent_id']));
                    
                    foreach($parents as $parent_item_id)
                    {
                        $item_info_query = db_query("select e.* " . fieldtype_formula::prepare_query_select($entity_id, '') . " from app_entity_" . $entity_id . " e where e.id='" . $parent_item_id . "'", false);
                        $item_info = db_fetch_array($item_info_query);
                        
                        $breadcrumb[] = array('url' => url_for('items/info', 'path=' . $path . $entity_id . '-' . $parent_item_id), 'title' => self::get_heading_field($entity_id, $parent_item_id, $item_info));
                    }
                }

                //check if user have access to entity
                if (users::has_users_access_to_entity($entity_id))
                {
                    $breadcrumb[] = array('url' => url_for('items/info', 'path=' . $path . $entity_id . '-' . $item_id), 'title' => $item_name);
                } else
                {
                    $breadcrumb[] = array('title' => $item_name);
                }
            }

            $path .= $entity_id . ($item_id > 0 ? '-' . $item_id . '/' : '');
        }

        return $breadcrumb;
    }

    public static function render_breadcrumb($breadcrumb)
    {
        $count = count($breadcrumb);
        $html = '';
        foreach ($breadcrumb as $k=>$v)
        {
            $html .= '
                <li>
                  ' . (isset($v['url']) ? '<a href="' . $v['url'] . '">' . $v['title'] . '</a>' : $v['title']) . '
                  ' . ($count-1!=$k ? '<i class="fa fa-angle-right"></i>':'') . '
                </li>
              ';
        }

        return $html;
    }

    public static function build_menu()
    {
        global $current_path, $current_path_array, $app_user, $app_users_access;

        $entity_id = 0;
        $path_to_item = array();
        foreach ($current_path_array as $v)
        {
            $vv = explode('-', $v);

            //$count = db_count('app_entities', $vv[0], 'parent_id');

            if (isset($vv[1]))
            {
                $entity_id = $vv[0];
                $item_id = $vv[1];

                $path_to_item[] = $v;
            }
        }

        $menu = array();


        //print_r($app_users_access);
        //exit();


        if ($entity_id > 0)
        {
            $parent_entity_cfg = new entities_cfg($entity_id);

            if (users::has_users_access_to_entity($entity_id))
            {
                $menu[] = array('title' => (strlen($parent_entity_cfg->get('window_heading')) > 0 ? $parent_entity_cfg->get('window_heading') : TEXT_INFO), 'url' => url_for('items/info', 'path=' . implode('/', $path_to_item)), 'selected_id' => $entity_id,'menu_css'=>'navbar-nav-entity-' . $entity_id);
            } else
            {
                $menu[] = array('title' => (strlen($parent_entity_cfg->get('window_heading')) > 0 ? $parent_entity_cfg->get('window_heading') : TEXT_INFO),'menu_css'=>'navbar-nav-entity-' . $entity_id);
            }

            $entities_query = db_query("select e.*, (select count(*) from app_nested_entities_menu m where find_in_set(e.id,m.entities) and is_active=1) is_used from app_entities e where parent_id='" . db_input($entity_id) . "' having is_used=0 order by e.sort_order, e.name");

            while ($entities = db_fetch_array($entities_query))
            {
                if (!isset($app_users_access[$entities['id']]) and $app_user['group_id'] > 0)
                    continue;

                //check if subentity hidden by filter that set on item page configuration
                if (entities::is_hidden_by_condition($entities['id'], $item_id))
                    continue;

                $entity_cfg = new entities_cfg($entities['id']);

                //skip hidden in menu
                if ($parent_entity_cfg->get('hide_subentity' . $entities['id'] . '_in_top_menu') == 1)
                    continue;

                $path = implode('/', $path_to_item) . '/' . $entities['id'];

                $menu[] = array('title' => (strlen($entity_cfg->get('menu_title')) > 0 ? $entity_cfg->get('menu_title') : $entities['name']), 'url' => url_for('items/items', 'path=' . $path), 'selected_id' => $entities['id'],'menu_css'=>'navbar-nav-entity-' . $entities['id']);
            }
            
            $nested_entities_menu = new nested_entities_menu($entity_id,$item_id,$path_to_item);
            $menu = $nested_entities_menu->render($menu);

            $s = array();

            if (count($plugin_menu = plugins::include_menu('items_menu_reports')) > 0)
            {
                $s = array_merge($s, $plugin_menu);
            }

            if (count($s) > 0)
            {
                $menu[] = array('title' => TEXT_REPORTS, 'submenu' => $s,'menu_css'=>'navbar-nav-entity-reports');
            }
        }

        return $menu;
    }

    public static function prepare_field_value_by_type($field, $item)
    {
        switch ($field['type'])
        {
            case 'fieldtype_created_by':
                $value = $item['created_by'];
                break;
            case 'fieldtype_date_added':
                $value = $item['date_added'];
                break;
            case 'fieldtype_date_updated':
                $value = $item['date_updated'];
                break;
            case 'fieldtype_action':
            case 'fieldtype_id':
                $value = $item['id'];
                break;
            case 'fieldtype_parent_item_id':
                $value = $item['parent_item_id'];
                break;
            default:
                $value = $item['field_' . $field['id']]??'';
                break;
        }

        return $value;
    }

    public static function render_info_box($entity_id, $item_id, $users_id = false, $exclude_fields_types = true)
    {
        global $current_path, $app_user, $app_users_cache, $current_item_info;

        $entity_cfg = new entities_cfg($entity_id);

        if ($users_id > 0)
        {
            $is_email = true;
            $user_info = db_find('app_entity_1', $users_id);
            $fields_access_schema = users::get_fields_access_schema($entity_id, $user_info['field_6']);
        } else
        {
            $is_email = false;
            $fields_access_schema = users::get_fields_access_schema($entity_id, $app_user['group_id']);
        }

        $fields_display_rules = array();

        $listing_sql_query_select = '';

        //prepare forumulas query
        $listing_sql_query_select = fieldtype_formula::prepare_query_select($entity_id, $listing_sql_query_select);

        $item_query = db_query("select e.* " . $listing_sql_query_select . " from app_entity_" . $entity_id . " e where id='" . $item_id . "'", false);
        $current_item_info = $item = db_fetch_array($item_query);

        $html = '';


        /**
         * display entity fields
         */
        if ($exclude_fields_types == true)
        {
            $exclude_fields_types = ",'fieldtype_image_map_nested','fieldtype_textarea_encrypted','fieldtype_video','fieldtype_iframe','fieldtype_google_map_directions','fieldtype_google_map','fieldtype_yandex_map','fieldtype_yandex_map_nested','fieldtype_yandex_map_directions','fieldtype_mind_map','fieldtype_image_map','fieldtype_todo_list','fieldtype_textarea','fieldtype_textarea_wysiwyg','fieldtype_attachments','fieldtype_onlyoffice','fieldtype_image','fieldtype_image_ajax','fieldtype_3dviewer','fieldtype_related_records','fieldtype_parent_item_id','fieldtype_mapbbcode','fieldtype_dropbox','fieldtype_yandex_disk', 'fieldtype_google_drive'";
        } else
        {
            $exclude_fields_types = ",'fieldtype_related_records','fieldtype_parent_item_id'";
        }


        $count = 0;
                
        $tabs_tree = forms_tabs::get_tree($entity_id);    
        foreach($tabs_tree as $tabs)
        {
            if($tabs['is_folder']) continue;
            
            $html_fields = '';

            $fields_query = db_query("select f.*, fr.sort_order as form_rows_sort_order,right(f.forms_rows_position,1) as forms_rows_pos, t.name as tab_name, if(f.type in ('fieldtype_id','fieldtype_date_added','fieldtype_date_updated','fieldtype_created_by'),-1,t.sort_order) as tab_sort_order from app_fields f left join app_forms_rows fr on fr.id=LEFT(f.forms_rows_position,length(f.forms_rows_position)-2), app_forms_tabs t where f.type not in ('fieldtype_action', 'fieldtype_related_mail','fieldtype_subentity_form' {$exclude_fields_types} )  and f.entities_id='" . db_input($entity_id) . "' and f.forms_tabs_id=t.id and f.forms_tabs_id='" . db_input($tabs['id']) . "' order by tab_sort_order, t.name, form_rows_sort_order, forms_rows_pos, f.sort_order, f.name", false);
            while ($field = db_fetch_array($fields_query))
            {
                //print_rr($field);
                
                //exclude fields in email
                if ($is_email and in_array($field['type'], fields_types::get_types_excluded_in_email()))
                    continue;

                //check field access
                if (isset($fields_access_schema[$field['id']]))
                {
                    if ($fields_access_schema[$field['id']] == 'hide')
                        continue;
                }

                //prepare field value
                $value = self::prepare_field_value_by_type($field, $item);

                $output_options = array(
                    'class' => $field['type'],
                    'value' => $value,
                    'field' => $field,
                    'item' => $item,
                    'display_user_photo' => true,
                    'path' => $current_path,
                    'is_item_page' => true,
                );

                if ($is_email)
                {
                    $output_options['is_email'] = true;
                }

                $cfg = new fields_types_cfg($field['configuration']);

                //hide if empty
                if ($cfg->get('hide_field_if_empty') == 1 and fields_types::is_empty_value($value, $field['type']))
                {
                    continue;
                }

                //hide if date updated empty
                if ($field['type'] == 'fieldtype_date_updated' and $value == 0)
                    continue;

                //check fields display rules
                $check_query = db_query("select * from app_forms_fields_rules where fields_id='" . $field['id'] . "'");
                if ($check = db_fetch_array($check_query))
                {
                    $is_multiple = false;

                    if (in_array($field['type'], ['fieldtype_dropdown_multiple', 'fieldtype_checkboxes']))
                    {
                        $is_multiple = true;
                    }

                    if ($field['type'] == 'fieldtype_grouped_users' and in_array($cfg->get('display_as'), ['checkboxes', 'dropdown_muliple']))
                    {
                        $is_multiple = true;
                    }

                    if (in_array($field['type'], ['fieldtype_boolean_checkbox', 'fieldtype_boolean']))
                    {
                        $value = ($value == 'true' ? 1 : 0);
                    }

                    if (in_array($field['type'], ['fieldtype_dropdown', 'fieldtype_radioboxes', 'fieldtype_stages', 'fieldtype_autostatus']) and $value == 0)
                    {
                        $value = '';
                    }

                    $fields_display_rules[] = 'app_handle_forms_fields_display_rules(\'\',' . $field['id'] . ',"","' . (strlen($value) ? $value : '') . '",' . (int) $is_multiple . '); ';
                }

                //skip heading or hidden fields from list but inlucde fields display rules before
                if ($field['is_heading'] == 1)
                    continue;

                if (strlen($entity_cfg->get('item_page_hidden_fields', '')))
                {
                    if (in_array($field['id'], explode(',', $entity_cfg->get('item_page_hidden_fields', ''))))
                        continue;
                }

                $field_name = fields_types::get_option($field['type'], 'name', $field['name']);

                $field_name .= fields::get_item_info_tooltip($field);

                if ($field['type'] == 'fieldtype_section')
                {
                    $html_fields .= '
                            <tr class="form-group form-group-' . $field['id'] . '">
                              <th colspan="2" class="section-heading">' . $field_name . '</th>
                            </tr>
                          ';
                } 
                elseif ($field['type'] == 'fieldtype_dropdown_multilevel')
                {
                    $html_fields .= fieldtype_dropdown_multilevel::output_info_box($output_options);
                }
                //hide field name to save space to display value
                elseif ($cfg->get('hide_field_name') == 1 or in_array($field['id'], explode(',',$entity_cfg->get('item_page_hidden_field_names'))))
                {
                    $field_value = fields_types::output($output_options);
                    $clipboard_html = '';
                    if(in_array($field['id'], explode(',',$entity_cfg->get('item_page_copy_to_clipboard_fields'))))
                    {
                        $clipboard_html = app_clipboardjs_icon(strip_tags($field_value));
                    }
                    
                    $html_fields .= '
                                    <tr class="form-group form-group-' . $field['id'] . '">                          
                                      <td colspan="2">' . $field_value  . $clipboard_html . '</td>
                                    </tr>
                                  ';
                } 
                elseif ($field['type'] == 'fieldtype_users')
                {
                    $html_fields .= '
                        <tr class="form-group form-group-' . $field['id'] . '">
                          <th colspan="2" ' . (strlen($field_name) > 25 ? 'class="white-space-normal"' : '') . '>' . $field_name . '</th>
                              </tr>
                              <tr class="form-group-' . $field['id'] . '">            		
                          <td colspan="2">' . fields_types::output($output_options) . '</td>
                        </tr>
                      ';
                } elseif ($field['type'] == 'fieldtype_mapbbcode')
                {
                    $html_fields .= '
                        <tr class="form-group form-group-' . $field['id'] . '">
                            <th ' . (strlen($field_name) > 25 ? 'class="white-space-normal"' : '') . '>' . $field_name . '</th>
                          <td style="width: 100%">' . fields_types::output($output_options) . '</td>
                        </tr>
                      ';
                } else
                {

                    $field_name_html = '';

                    //add dwonload All Attachments link if more then 1 files
                    if ($field['type'] == 'fieldtype_attachments' and count(explode(',', $value)) > 1)
                    {
                        $field_name_html = '<br><span class="download-all-attachments"><a style="margin-left: 0; font-weight: normal" href="' . url_for('items/info', 'action=download_all_attachments&id=' . $field['id'] . '&path=' . $current_path) . '"><i class="fa fa-download"></i> ' . TEXT_DOWNLOAD_ALL_ATTACHMENTS . '</a></span>';
                        
                        if($cfg->get('allow_sort_order')=='manual_sorting' and users::has_access('update'))
                        {
                            $field_name_html .= '<span class="download-all-attachments">' . link_to_modalbox('<i class="fa fa-sort-amount-asc"></i> ' . TEXT_SORT, url_for('items/attachments_sort','field_id=' . $field['id'] . '&path=' . $current_path),['style'=>'font-weight: normal']). '</span>';
                        }
                    }
                    
                    //download all onlyoffice files
                    if ($field['type'] == 'fieldtype_onlyoffice' and count(explode(',', $value)) > 1 and !$is_email)
                    {
                        $field_name_html .= '<span class="download-all-attachments"><a href="' . url_for('items/onlyoffice', 'action=download_all&field_id=' . $field['id'] . '&path=' . $current_path) . '"><i class="fa fa-download"></i> ' . TEXT_DOWNLOAD_ALL_ATTACHMENTS . '</a></span>';

                        if($cfg->get('allow_sort_order')=='manual_sorting' and users::has_access('update'))
                        {
                            $field_name_html .= '<span class="download-all-attachments">' . link_to_modalbox('<i class="fa fa-sort-amount-asc"></i> ' . TEXT_SORT, url_for('items/onlyoffice_sort','field_id=' . $field['id'] . '&path=' . $current_path)). '</span>';
                        }
                    }

                    //file storage sorting
                    if (in_array($field['type'],file_storage_field::get_types()) and count(explode(',', $value)) > 1 and !$is_email)
                    {                                       
                        if($cfg->get('allow_sort_order')=='manual_sorting' and users::has_access('update'))
                        {
                            $field_name_html .= '<span class="download-all-attachments">' . link_to_modalbox('<i class="fa fa-sort-amount-asc"></i> ' . TEXT_SORT, url_for('items/file_storage_sort','field_id=' . $field['id'] . '&path=' . $current_path)). '</span>';
                        }
                    }
                    
                    $field_value = fields_types::output($output_options);
                    $clipboard_html = '';
                    if(in_array($field['id'], explode(',',$entity_cfg->get('item_page_copy_to_clipboard_fields'))))
                    {
                        $clipboard_html = app_clipboardjs_icon(strip_tags($field_value));
                    }

                    $html_fields .= '
                        <tr class="form-group form-group-' . $field['id'] . '">            
                          <th ' . (strlen($field_name) > 25 ? 'class="white-space-normal"' : '') . '>' .
                                        $field_name . $field_name_html .
                                        '</th>
                          <td>' . $field_value .  $clipboard_html . '</td>
                        </tr>
                      ';
                }
            }

            //include TAB if there are fields in list
            if (strlen($html_fields))
            {
                $html .= '
                <div class="check-form-tabs" cfg_tab_id="info_box_tab_' . $tabs['id'] . '">    
                    <div class="heading"><h4 class="media-heading ">' . $tabs['name'] . '</h4></div>
                    <div class="table-scrollable info_box_tab_' . $tabs['id'] . '" id="info_box_tab_' . $tabs['id'] . '">
                        <table class="table table-bordered table-hover table-item-details table-item-details-' . $entity_id . '">
                            ' . $html_fields . '
                        </table>
                    </div>
                </div>';
            }

            $count++;
        }

        if (count($fields_display_rules))
        {
            $html .= '<script>' . implode("\n", $fields_display_rules) . '</script>';
        }

        return $html;
    }

    public static function render_content_box($entity_id, $item_id, $users_id = false)
    {
        global $current_path, $app_user;

        if ($users_id > 0)
        {
            $is_email = true;
            $user_info = db_find('app_entity_1', $users_id);
            $fields_access_schema = users::get_fields_access_schema($entity_id, $user_info['field_6']);
        } else
        {
            $is_email = false;
            $fields_access_schema = users::get_fields_access_schema($entity_id, $app_user['group_id']);
        }

        $entity_cfg = new entities_cfg($entity_id);

        $item_query = db_query("select e.* " . fieldtype_input_encrypted::prepare_query_select($entity_id) . " from app_entity_" . $entity_id . " e where id='" . $item_id . "'", false);
        $item = db_fetch_array($item_query);

        $html = '';
        $count = 0;

        $html = '';
        $fields_query = db_query("select f.*, fr.sort_order as form_rows_sort_order,right(f.forms_rows_position,1) as forms_rows_pos from app_fields f left join app_forms_rows fr on fr.id=LEFT(f.forms_rows_position,length(f.forms_rows_position)-2), app_forms_tabs t where f.type in ('fieldtype_image_map_nested','fieldtype_textarea_encrypted','fieldtype_video','fieldtype_iframe','fieldtype_google_map_directions','fieldtype_google_map','fieldtype_yandex_map','fieldtype_yandex_map_nested','fieldtype_yandex_map_directions','fieldtype_mind_map','fieldtype_image_map','fieldtype_todo_list','fieldtype_textarea','fieldtype_textarea_wysiwyg','fieldtype_attachments','fieldtype_onlyoffice','fieldtype_image','fieldtype_image_ajax','fieldtype_3dviewer','fieldtype_mapbbcode','fieldtype_dropbox','fieldtype_yandex_disk','fieldtype_google_drive') and  f.entities_id='" . db_input($entity_id) . "' and f.forms_tabs_id=t.id order by t.sort_order, t.name, form_rows_sort_order, forms_rows_pos,  f.sort_order, f.name");
        while ($field = db_fetch_array($fields_query))
        {
            //exclude fields in email
            if ($is_email and in_array($field['type'], fields_types::get_types_excluded_in_email()))
                continue;

            //check field access
            if (isset($fields_access_schema[$field['id']]))
            {
                if ($fields_access_schema[$field['id']] == 'hide')
                    continue;
            }

            $value = $value_original = $item['field_' . $field['id']];

            if ($field['type'] == 'fieldtype_attachments' and strlen($value) == 0)
                continue;

            if (strlen($entity_cfg->get('item_page_hidden_fields', '')))
            {
                if (in_array($field['id'], explode(',', $entity_cfg->get('item_page_hidden_fields', ''))))
                    continue;
            }

            $cfg = new fields_types_cfg($field['configuration']);


            $output_options = array('class' => $field['type'],
                'value' => $value,
                'field' => $field,
                'item' => $item,
                'is_item_page' => true,
                'path' => $current_path);

            if ($is_email)
            {
                $output_options['is_email'] = true;
            }

            $value = fields_types::output($output_options);

            if (strlen($value) > 0)
            {
                $field_name_html = '';

                $field_name_html .= fields::get_item_info_tooltip($field);

                //add dwonload All Attachments link if more then 1 files
                if ($field['type'] == 'fieldtype_attachments' and count(explode(',', $value_original)) > 1)
                {
                    $field_name_html .= '<span class="download-all-attachments"><a href="' . url_for('items/info', 'action=download_all_attachments&id=' . $field['id'] . '&path=' . $current_path) . '"><i class="fa fa-download"></i> ' . TEXT_DOWNLOAD_ALL_ATTACHMENTS . '</a></span>';
                    
                    if($cfg->get('allow_sort_order')=='manual_sorting' and users::has_access('update'))
                    {
                        $field_name_html .= '<span class="download-all-attachments">' . link_to_modalbox('<i class="fa fa-sort-amount-asc"></i> ' . TEXT_SORT, url_for('items/attachments_sort','field_id=' . $field['id'] . '&path=' . $current_path)). '</span>';
                    }
                }
                
                //download all onlyoffice files
                if ($field['type'] == 'fieldtype_onlyoffice' and count(explode(',', $value_original)) > 1 and !$is_email)
                {
                    $field_name_html .= '<span class="download-all-attachments"><a href="' . url_for('items/onlyoffice', 'action=download_all&field_id=' . $field['id'] . '&path=' . $current_path) . '"><i class="fa fa-download"></i> ' . TEXT_DOWNLOAD_ALL_ATTACHMENTS . '</a></span>';
                    
                    if($cfg->get('allow_sort_order')=='manual_sorting' and users::has_access('update'))
                    {
                        $field_name_html .= '<span class="download-all-attachments">' . link_to_modalbox('<i class="fa fa-sort-amount-asc"></i> ' . TEXT_SORT, url_for('items/onlyoffice_sort','field_id=' . $field['id'] . '&path=' . $current_path)). '</span>';
                    }
                }
                
                //file storage sorting
                if (in_array($field['type'],file_storage_field::get_types()) and count(explode(',', $value_original)) > 1 and !$is_email)
                {                                       
                    if($cfg->get('allow_sort_order')=='manual_sorting' and users::has_access('update'))
                    {
                        $field_name_html .= '<span class="download-all-attachments">' . link_to_modalbox('<i class="fa fa-sort-amount-asc"></i> ' . TEXT_SORT, url_for('items/file_storage_sort','field_id=' . $field['id'] . '&path=' . $current_path)). '</span>';
                    }
                }

                $html .= '
        	<div  class="form-group-' . $field['id'] . '">	
        	    ' . (($cfg->get('hide_field_name') != 1 and !in_array($field['id'], explode(',',$entity_cfg->get('item_page_hidden_field_names'))) ) ? '<div class="content_box_heading"><h4 class="media-heading">' . $field['name'] . $field_name_html . '</h4></div>' : '') . '
        	    <div class="content_box_content ' . $field['type'] . '">' . $value . '</div>
            </div>
        ';
            }
        }



        return $html;
    }

    public static function get_fields_values_cache($fields_cache, $path_array, $current_entity_id)
    {
        foreach ($path_array as $v)
        {
            $vv = explode('-', $v);
            $entity_id = $vv[0];
            $item_id = (isset($vv[1]) ? $vv[1] : 0);

            if ($item_id == 0 or $current_entity_id == $entity_id)
                break;

            $item_info = db_find('app_entity_' . $entity_id, $item_id);

            $fields_query = db_query("select f.* from app_fields f where f.type not in (" . fields_types::get_reserverd_types_list() . ") and  f.entities_id='" . db_input($entity_id) . "' order by f.sort_order, f.name");
            while ($field = db_fetch_array($fields_query))
            {
                $fields_cache[$field['id']] = $item_info['field_' . $field['id']];
            }
        }

        return $fields_cache;
    }

    public static function get_path_info($entities_id, $items_id, $current_item_info = false)
    {
        $path_array = items::get_path_array($entities_id, $items_id, array(), $current_item_info);

        $path_array = array_reverse($path_array);

        $cout = 0;
        $paent_path_list = array();
        $path_list = array();
        $name_list = array();
        $path_to_entity = array();
        foreach ($path_array as $v)
        {
            $path_list[] = $v['path'];


            if ($cout != (count($path_array) - 1))
            {
                $paent_path_list[] = $v['path'];
                $name_list[] = $v['name'];
            }

            if ($cout == (count($path_array) - 1))
            {
                $last = explode('-', $v['path']);
                $path_to_entity[] = $last[0];
            } else
            {
                $path_to_entity[] = $v['path'];
            }

            $cout++;
        }

        return array('parent_name' => implode('<br>', $name_list),
            'parent_path' => implode('/', $paent_path_list),
            'full_path' => implode('/', $path_list),
            'full_path_array' => $path_list,
            'path_to_entity' => implode('/', $path_to_entity),
        );

        //print_r($path_array);
    }

    public static function get_path_array($entities_id, $items_id, $path_array = array(), $current_item_info = false)
    {
        global $app_entities_cache, $items_holder;

        $entities = $app_entities_cache[$entities_id];

        if (!isset($items_holder[$entities_id][$items_id]))
        {
            if ($current_item_info)
            {
                $items = $items_holder[$entities_id][$items_id] = $current_item_info;
            } else
            {
                $items_query = db_query("select * from app_entity_" . $entities_id . " where id='" . $items_id . "'");
                $items = $items_holder[$entities_id][$items_id] = db_fetch_array($items_query);
            }
        } else
        {
            $items = $items_holder[$entities_id][$items_id];
        }

        if ($heading_field_id = fields::get_heading_id($entities_id))
        {
            $name = self::get_heading_field_value($heading_field_id, $items);
        } else
        {
            $name = $items['id'];
        }

        $path_array[] = array('path' => $entities_id . '-' . $items_id, 'name' => $name, 'entities_id' => $entities_id, 'items_id' => $items_id);

        if ($entities['parent_id'] > 0)
        {
            $path_array = items::get_path_array($entities['parent_id'], $items['parent_item_id']??0, $path_array);
        }

        return $path_array;
    }

    public static function parse_path($path)
    {
        $path_array = explode('/', $path);
        $item_array = explode('-', $path_array[count($path_array) - 1]);

        $entity_id = $item_array[0];
        $item_id = (isset($item_array[1]) ? $item_array[1] : 0);

        if (count($path_array) > 1)
        {
            $v = explode('-', $path_array[count($path_array) - 2]);
            $parent_entity_id = $v[0];
            $parent_entity_item_id = $v[1];
        } else
        {
            $parent_entity_id = 0;
            $parent_entity_item_id = 0;
        }

        return array('entity_id' => $entity_id,
            'item_id' => $item_id,
            'path_array' => $path_array,
            'parent_entity_id' => $parent_entity_id,
            'parent_entity_item_id' => $parent_entity_item_id);
    }

    public static function get_paretn_entity_id_by_path($path)
    {
        $entity_id = 0;
        $path_array = explode('/', $path);
        foreach ($path_array as $v)
        {
            $vv = explode('-', $v);

            $count = db_count('app_entities', $vv[0], 'parent_id');

            if ($count > 0 and isset($vv[1]))
            {
                $entity_id = $vv[0];
            }
        }

        return $entity_id;
    }

    public static function get_paretn_entity_item_id_by_path($path)
    {
        $item_id = 0;
        $path_array = explode('/', $path);
        foreach ($path_array as $v)
        {
            $vv = explode('-', $v);

            $count = db_count('app_entities', $vv[0], 'parent_id');

            if ($count > 0 and isset($vv[1]))
            {
                $item_id = $vv[1];
            }
        }

        return $item_id;
    }

    public static function get_sub_entities_list_by_path($path)
    {
        global $app_user;

        $parent_id = items::get_paretn_entity_id_by_path($path);

        $list = array();

        if ($parent_id > 0)
        {
            if ($app_user['group_id'] == 0)
            {
                $entities_query = db_query("select e.* from app_entities e where parent_id='" . db_input($parent_id) . "' order by e.sort_order, e.name");
            } else
            {
                $entities_query = db_query("select e.* from app_entities e, app_entities_access ea where e.id=ea.entities_id and length(ea.access_schema)>0 and ea.access_groups_id='" . db_input($app_user['group_id']) . "' and e.parent_id = '" . db_input($parent_id) . "' order by e.sort_order, e.name");
            }


            while ($entities = db_fetch_array($entities_query))
            {
                $list[] = $entities['id'];
            }
        }

        return $list;
    }

    public static function add_access_query_for_parent_entities($entities_id, $listing_sql_query = '')
    {
        global $app_user, $app_entities_cache;

        if ($app_user['group_id'] == 0)
            return '';

        $entity_info = $app_entities_cache[$entities_id];

        if ($entity_info['parent_id'] > 0)
        {
            $listing_sql_query = ' and e.parent_item_id in (select e.id from app_entity_' . $entity_info['parent_id'] . ' e where e.id>0 ' . items::add_access_query($entity_info['parent_id'], '') . ' ' . items::add_access_query_for_parent_entities($entity_info['parent_id']) . ')';
        }

        return $listing_sql_query;
    }

    //check users tree access for entity 1
    public static function add_access_query_for_user_parent_entities($entities_id, $listing_sql_query = '')
    {
        global $app_user, $app_entities_cache;

        if ($app_user['group_id'] == 0)
            return '';

        $entity_info = $app_entities_cache[$entities_id];

        if ($entity_info['parent_id'] > 0)
        {
            $listing_sql_query = ' and e.parent_item_id in (select e.id from app_entity_' . $entity_info['parent_id'] . ' e where ' . ($entity_info['parent_id'] == 1 ? 'e.id=' . $app_user['id'] : 'e.id>0') . ' ' . items::add_access_query_for_user_parent_entities($entity_info['parent_id']) . ')';
        }

        return $listing_sql_query;
    }

    public static function add_access_query($current_entity_id, $listing_sql_query, $force_access_query = false)
    {
        global $app_user, $current_path_array;

        $access_schema = users::get_entities_access_schema($current_entity_id, $app_user['group_id']);

        //get users entiteis tree
        $users_entities_tree = entities::get_tree(1);

        //get users entities id list
        $users_entities = array();
        foreach ($users_entities_tree as $v)
        {
            $users_entities[] = $v['id'];
        }                

        //force check users entities tree access
        if (in_array($current_entity_id, $users_entities) and users::has_access('view_assigned', $access_schema) and $app_user['group_id'] > 0)
        {
            $listing_sql_query .= self::add_access_query_for_user_parent_entities($current_entity_id);

            /* echo '<pre>';
              print_r($users_entities);
              print_r($listing_sql_query);
              exit(); */
        } 
        elseif ((users::has_access('view_assigned', $access_schema) and $app_user['group_id'] > 0) or $force_access_query)
        {            
            $users_fields = array();
            $fields_query = db_query("select f.id from app_fields f where f.type in ('fieldtype_users','fieldtype_users_ajax','fieldtype_user_roles','fieldtype_users_approve') and  f.entities_id='" . db_input($current_entity_id) . "'");
            while ($fields = db_fetch_array($fields_query))
            {
                $users_fields[] = $fields['id'];
            }

            $grouped_users_fields = array();
            $grouped_global_users_fields = array();
            $fields_query = db_query("select f.id, f.configuration from app_fields f where f.type in ('fieldtype_grouped_users') and  f.entities_id='" . db_input($current_entity_id) . "'");
            while ($fields = db_fetch_array($fields_query))
            {
                $cfg = new fields_types_cfg($fields['configuration']);

                if ($cfg->get('use_global_list') > 0)
                {
                    $grouped_global_users_fields[$cfg->get('use_global_list')] = $fields['id'];
                } else
                {
                    $grouped_users_fields[] = $fields['id'];
                }
            }

            $access_group_fields = array();
            $fields_query = db_query("select f.id from app_fields f where f.type in ('fieldtype_access_group') and  f.entities_id='" . db_input($current_entity_id) . "'");
            while ($fields = db_fetch_array($fields_query))
            {
                $access_group_fields[] = $fields['id'];
            }

            //if exist fields then check access by fields + created_by      
            //check users fields
            $sql_query_array = array();
            foreach ($users_fields as $id)
            {
                $sql_query_array[] = "(select count(*) as total from app_entity_" . $current_entity_id . "_values cv where  cv.items_id=e.id and cv.fields_id='" . $id . "' and cv.value='" . $app_user['id'] . "')>0";
            }

            //check gouped users
            foreach ($grouped_users_fields as $id)
            {
                $sql_query_array[] = "(select count(*) as total from app_entity_" . $current_entity_id . "_values cv where cv.items_id=e.id and cv.fields_id='" . $id . "' and cv.value in (select id from app_fields_choices fc where fc.fields_id='" . $id . "' and find_in_set(" . $app_user['id'] . ",fc.users)))>0";
            }

            //check gouped users with globallist
            foreach ($grouped_global_users_fields as $list_id => $id)
            {
                $sql_query_array[] = "(select count(*) as total from app_entity_" . $current_entity_id . "_values cv where cv.items_id=e.id and cv.fields_id='" . $id . "' and cv.value in (select id from app_global_lists_choices fc where fc.lists_id='" . $list_id . "' and find_in_set(" . $app_user['id'] . ",fc.users)))>0";
            }

            //check access group fields
            foreach ($access_group_fields as $id)
            {
                $sql_query_array[] = "(select count(*) as total from app_entity_" . $current_entity_id . "_values cv where cv.items_id=e.id and cv.fields_id='" . $id . "' and cv.value='" . $app_user['group_id'] . "')>0";
            }

            //check created by
            $sql_query_array[] = "e.created_by='" . $app_user['id'] . "'";
            
            //check user entity
            if($current_entity_id==1)
            {
                $sql_query_array[] = "e.id='" . $app_user['id'] . "'";
            }

            $listing_sql_query .= " and (" . implode(' or ', $sql_query_array) . ") ";
        }

        //add visibility access query
        $listing_sql_query .= records_visibility::add_access_query($current_entity_id);

        return $listing_sql_query;
    }

    public static function add_listing_order_query_by_entity_id($entities_id, $order_cause = 'asc', $alias = 'e')
    {
        $listing_order_query = " order by ";

        //if entity is Users then order by firstname/lastname
        if ($entities_id == 1)
        {
            $listing_order_query .= (CFG_APP_DISPLAY_USER_NAME_ORDER == 'firstname_lastname' ? "{$alias}.field_7 {$order_cause}, {$alias}.field_8 {$order_cause}" : "{$alias}.field_8 {$order_cause}, {$alias}.field_7 {$order_cause}");
        }
        //if exist haeading field then order by heading  
        elseif ($heading_id = fields::get_heading_id($entities_id))
        {
            $field_info = db_find('app_fields', $heading_id);

            switch ($field_info['type'])
            {
                case 'fieldtype_id':
                    $listing_order_query .= "{$alias}.id" . ' ' . $order_cause;
                    break;
                case 'fieldtype_date_added':
                    $listing_order_query .= "{$alias}.date_added" . ' ' . $order_cause;
                    break;
                case 'fieldtype_created_by':
                    $listing_order_query .= "{$alias}.created_by" . ' ' . $order_cause;
                    break;
                default:
                    $listing_order_query .= "{$alias}.field_{$heading_id} " . $order_cause;
                    break;
            }
        }
        //default order by ID
        else
        {
            $listing_order_query .= "{$alias}.id" . ' ' . $order_cause;
        }

        return $listing_order_query;
    }

    public static function check_unique($entities_id, $fields_id, $fields_value, $items_id = false,$unique_for_each_parent = false)
    {
        $field_info = db_find('app_fields', $fields_id);
        $cfg = new settings($field_info['configuration']);
        
        switch ($field_info['type'])
        {
            case 'fieldtype_input_date_extra':
                $f = new fieldtype_input_date_extra();
                $fields_value = $f->get_date_timestamp($fields_value, $cfg);
                break;
            case 'fieldtype_input_datetime':
            case 'fieldtype_input_date':
                $fields_value = get_date_timestamp($fields_value);
                break;
            case 'fieldtype_input_ip':
                $fields_value = ip2long($fields_value);
                break;
        }

        $check_query = db_query("select count(*) as total from app_entity_" . $entities_id . " where field_" . $field_info['id'] . "='" . db_input($fields_value) . "'" . ($items_id ? " and id!='" . db_input($items_id) . "'" : "") . ($unique_for_each_parent ? " and parent_item_id='" . $unique_for_each_parent . "'":""));
        $check = db_fetch_array($check_query);

        if($check['total']>0)
        {
            $msg = strlen(trim($cfg->get('unique_error_msg'))) ? trim($cfg->get('unique_error_msg')) : TEXT_UNIQUE_FIELD_VALUE_ERROR;
            return json_encode($msg);
        }
        else
        {
            return json_encode(true);
        }
        //return (int) $check['total'];
    }

    public static function get_send_to($entity_id, $item_id, $item = false)
    {

        if (!$item)
        {
            $item = db_find('app_entity_' . $entity_id, $item_id);
        }

        //start build $send_to array
        $send_to = array();

        //add assigned users to notification
        $fields_query = db_query("select f.* from app_fields f where f.type in ('fieldtype_users_approve','fieldtype_user_roles','fieldtype_grouped_users','fieldtype_users','fieldtype_users_ajax') and  f.entities_id='" . db_input($entity_id) . "' ");
        while ($field = db_fetch_array($fields_query))
        {
            $cfg = new fields_types_cfg($field['configuration']);

            //skip fields with disabled notification
            if ($cfg->get('disable_notification') == 1)
                continue;

            $field_value = $item['field_' . $field['id']];

            switch ($field['type'])
            {
                case 'fieldtype_grouped_users':

                    $send_to = $send_to + fieldtype_grouped_users::get_send_to($field_value, $cfg);

                    break;
                case 'fieldtype_users_approve':
                case 'fieldtype_user_roles':
                case 'fieldtype_users':
                case 'fieldtype_users_ajax':
                    if (!is_null($field_value) and strlen($field_value) > 0)
                    {
                        $send_to = array_merge($send_to, explode(',', $field_value));
                    }
                    break;
            }
        }

        $send_to = array_filter($send_to);

        return $send_to;
    }

    public static function send_new_item_nofitication($current_entity_id, $item_id, $app_send_to = false)
    {

        if (!$app_send_to)
        {
            $app_send_to = items::get_send_to($current_entity_id, $item_id);
        }

        if(is_ext_installed())
        {
            //sending sms
            $modules = new modules('sms');
            $sms = new sms($current_entity_id, $item_id);
            $sms->send_to = $app_send_to;
            $sms->send_insert_msg();

            //email rules
            $email_rules = new email_rules($current_entity_id, $item_id);
            $email_rules->send_insert_msg();
        }

        $breadcrumb = items::get_breadcrumb_by_item_id($current_entity_id, $item_id);
        $item_name = $breadcrumb['text'];

        $entity_cfg = new entities_cfg($current_entity_id);

        //subject for new item
        $subject = (strlen($entity_cfg->get('email_subject_new_item')) > 0 ? $entity_cfg->get('email_subject_new_item') . ' ' . $item_name : TEXT_DEFAULT_EMAIL_SUBJECT_NEW_ITEM . ' ' . $item_name);


        //Send notification if there are assigned users and items is new or there is changed fields or new assigned users
        if (count($app_send_to) > 0)
        {
            $users_notifications_type = 'new_item';

            //default email heading
            $heading = users::use_email_pattern_style('<div><a href="' . url_for('items/info', 'path=' . $current_entity_id . '-' . $item_id, true) . '"><h3>' . $subject . '</h3></a></div>', 'email_heading_content');

            //start sending email
            foreach (array_unique($app_send_to) as $send_to)
            {
                //prepare body
                //prepare body
                if ($entity_cfg->get('item_page_details_columns', '2') == 1)
                {
                    $body = users::use_email_pattern('single_column', array('email_single_column' => items::render_info_box($current_entity_id, $item_id, $send_to, false)));
                } else
                {
                    $body = users::use_email_pattern('single', array('email_body_content' => items::render_content_box($current_entity_id, $item_id, $send_to), 'email_sidebar_content' => items::render_info_box($current_entity_id, $item_id, $send_to)));
                }

                //echo $subject . $body;
                //exit();


                if (users_cfg::get_value_by_users_id($send_to, 'disable_notification') != 1)
                {
                    users::send_to(array($send_to), $subject, $heading . $body);
                }

                //add users notification
                users_notifications::add($subject, $users_notifications_type, $send_to, $current_entity_id, $item_id);
            }
        }

        return $subject;
    }

    static function get_paretn_users_list($entities_id, $parent_entity_item_id)
    {
        global $app_entities_cache, $app_fields_cache;

        $has_parent_users = false;
        $parent_users_list = [];

        $parent_entity_id = $app_entities_cache[$entities_id]['parent_id'];

        if ($parent_entity_id == 0 or $parent_entity_item_id == 0)
            return false;

        $path_array = items::get_path_array($parent_entity_id, $parent_entity_item_id);

        foreach ($path_array as $path_info)
        {
            $parent_users_fields = array();
            $parent_fields_query = db_query("select f.* from app_fields f where f.type in ('fieldtype_users','fieldtype_users_ajax','fieldtype_user_roles','fieldtype_users_approve','fieldtype_access_group','fieldtype_grouped_users') and  f.entities_id='" . db_input($path_info['entities_id']) . "'");
            while ($parent_field = db_fetch_array($parent_fields_query))
            {
                $has_parent_users = true;

                $parent_users_fields[] = $parent_field['id'];
            }

            if ($has_parent_users)
            {
                $parent_item_info = db_find('app_entity_' . $path_info['entities_id'], $path_info['items_id']);

                foreach ($parent_users_fields as $id)
                {
                    if (isset($parent_item_info['field_' . $id]) and strlen($parent_item_info['field_' . $id]))
                    {
                        $field = $app_fields_cache[$path_info['entities_id']][$id];
                        $cfg = new fields_types_cfg($field['configuration']);

                        //echo $field['type'] . '<br>';

                        switch ($field['type'])
                        {
                            case 'fieldtype_grouped_users':
                                $parent_users_list = array_merge(fieldtype_grouped_users::get_send_to($parent_item_info['field_' . $id], $cfg), $parent_users_list);
                                break;
                            case 'fieldtype_access_group':
                                $parent_users_list = array_merge(fieldtype_access_group::get_send_to($parent_item_info['field_' . $id]), $parent_users_list);
                                break;
                            default:
                                $parent_users_list = array_merge(explode(',', $parent_item_info['field_' . $id]), $parent_users_list);
                                break;
                        }
                    }
                }
            }

            $parent_users_list = array_unique($parent_users_list);

            //cancel check if has user field
            if ($has_parent_users)
            {
                return $parent_users_list;
            }
        }

        return false;
    }
    
    static function update_by_id($entity_id, $item_id, $data,$settings = [])
    {
        global $app_entities_cache, $app_fields_cache;
        
        $settings = new settings($settings,[
            'run_email_rules' => true,
            'run_process' => true
        ]);
        
        //check params
        if(!isset($app_entities_cache[$entity_id]) or !count($data)) return false;
        
        //check if item exit
        $item_query = db_query("select * from app_entity_{$entity_id} where id=" . (int)$item_id);
        if(!$item = db_fetch_array($item_query))
        {
            return false;
        }
        
        //reject change it
        unset($item['id']);
        
        $choices_values = new choices_values($entity_id);
        
        $sql_data = [];
        foreach($data as $field_key=>$value)
        {
            if(!isset($item[$field_key])) continue;
            
            $sql_data[$field_key] = $value;
            
            if(in_array($field_key,['created_by','parent_item_id']))
            {               
                $check_query = db_query("select id from app_fields where entities_id='" . $entity_id . "' and type='fieldtype_{$field_key}'");
                if($check = db_fetch($check_query))
                {
                    $field_id = $check->id;                    
                }                
            }
            else
            {
                $field_id = (int) str_replace('field_', '', $field_key);
            }
            
            //prepare choices values for fields with multiple values
                $options = array(
                    'class' => $app_fields_cache[$entity_id][$field_id]['type'],
                    'field' => array('id' => $field_id),
                    'value' => (strlen($value) ? explode(',', $value) : '')
                );

                $choices_values->prepare($options);
        }
        
        if(!count($sql_data))
        {
            return false;
        }
        
        $sql_data['date_updated'] = time();
        db_perform('app_entity_' . $entity_id, $sql_data, 'update', "id='" . $item_id . "'");
        
        //insert choices values for fields with multiple values
        $choices_values->process($item_id);
        
        fields_types::update_items_fields($entity_id, $item_id);
        
        if(is_ext_installed())
        {
            if($settings->get('run_email_rules'))
            {
                //sending sms
                $modules = new modules('sms');
                $sms = new sms($entity_id, $item_id);            
                $sms->send_edit_msg($item);

                //email rules
                $email_rules = new email_rules($entity_id, $item_id);
                $email_rules->send_edit_msg($item);
            }
            
            if($settings->get('run_process'))
            {
                //run actions after item update
                $processes = new processes($entity_id);
                $processes->run_after_update($item_id);
            }
        }

        return true;
    }
    
    static function insert($entity_id, $data)
    {
        global $app_entities_cache, $app_fields_cache;
        
        //check params
        if(!isset($app_entities_cache[$entity_id]) or !count($data)) return false;
        
        $item = db_show_columns("app_entity_" . $entity_id);
        
        //reject change it
        unset($item['id']);
        
        $choices_values = new choices_values($entity_id);
        
        $sql_data = [];
        foreach($data as $field_key=>$value)
        {
            if(!isset($item[$field_key])) continue;
            
            $sql_data[$field_key] = $value;
                        
            if(in_array($field_key,['parent_id']))
            {
                continue;
            }
            
            if(in_array($field_key,['created_by','parent_item_id']))
            {               
                $check_query = db_query("select id from app_fields where entities_id='" . $entity_id . "' and type='fieldtype_{$field_key}'");
                if($check = db_fetch($check_query))
                {
                    $field_id = $check->id;                    
                }                
            }
            else
            {
                $field_id = (int) str_replace('field_', '', $field_key);
            }
            
            //prepare choices values for fields with multiple values
                $options = array(
                    'class' => $app_fields_cache[$entity_id][$field_id]['type'],
                    'field' => array('id' => $field_id),
                    'value' => (strlen($value) ? explode(',', $value) : '')
                );

                $choices_values->prepare($options);
        }
        
        if(!count($sql_data))
        {
            return false;
        }
                
        $sql_data['date_added'] = time();
        db_perform('app_entity_' . $entity_id, $sql_data);
        $item_id = db_insert_id();
        
        //insert choices values for fields with multiple values
        $choices_values->process($item_id);
        
        fields_types::update_items_fields($entity_id, $item_id);
        
        if(is_ext_installed())
        {
            //sending sms
            $modules = new modules('sms');
            $sms = new sms($entity_id, $item_id);            
            $sms->send_insert_msg();

            //subscribe
            $modules = new modules('mailing');
            $mailing = new mailing($entity_id, $item_id);
            $mailing->subscribe();

            //email rules
            $email_rules = new email_rules($entity_id, $item_id);
            $email_rules->send_insert_msg();

            //run actions after item insert
            $processes = new processes($entity_id);
            $processes->run_after_insert($item_id);
        }

        return $item_id;
    }    
    
    static function count_by_reports_id($entity_id, $report_id)
    {        
        $items_query = new items_query($entity_id,['report_id'=>$report_id,'add_filters'=>true,'add_formula'=>true]);
        //echo $items_query->get_sql();
        $query = db_query($items_query->get_sql());
        return db_num_rows($query);
    }

}
