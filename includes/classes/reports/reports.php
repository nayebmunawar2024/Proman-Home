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

class reports
{

    public static function copy($reports_id, $new_report_type = '')
    {
        $reports_list[] = $reports_id;
        $reports_list = reports::get_parent_reports($reports_id, $reports_list);

        $reports_list = array_reverse($reports_list);

        //print_rr($reports_list);
        //exit();

        $new_reports_id = 0;
        $parent_reports_id = 0;

        foreach ($reports_list as $reports_id)
        {
            $reports_query = db_query("select * from app_reports where id='" . $reports_id . "'");
            if ($reports = db_fetch_array($reports_query))
            {
                unset($reports['id']);
                $reports['name'] = $reports['name'] . ' (' . TEXT_EXT_NAME_COPY . ')';
                $reports['parent_id'] = $parent_reports_id;

                db_perform('app_reports', $reports);
                $new_reports_id = $parent_reports_id = db_insert_id();

                $filters_query = db_query("select * from app_reports_filters where reports_id='" . $reports_id . "'");
                while ($filters = db_fetch_array($filters_query))
                {
                    unset($filters['id']);
                    $filters['reports_id'] = $new_reports_id;

                    db_perform('app_reports_filters', $filters);
                }
            }
        }
        
        if(strlen($new_report_type))
        {
            db_query("update app_reports set reports_type='" . db_input($new_report_type) . "' where id={$new_reports_id}");
        }
        
        return $new_reports_id;
    }

    public static function get_default_entity_report_id($entity_id, $reports_type)
    {
        $reports_info = reports::create_default_entity_report($entity_id, $reports_type);

        return $reports_info['id'];
    }

    public static function create_default_entity_report($entity_id, $reports_type, $path_array = array())
    {
        global $app_logged_users_id;

        $where_str = '';

        //fitler reports by parent item
        if (count($path_array) > 1)
        {
            $parent_path_array = explode('-', $path_array[count($path_array) - 2]);

            $parent_entity_id = $parent_path_array[0];
            $parent_item_id = $parent_path_array[1];

            $where_str = " and parent_entity_id='" . $parent_entity_id . "' and parent_item_id='" . $parent_item_id . "'";
        } else
        {
            $parent_entity_id = 0;
            $parent_item_id = 0;
        }

        $reports_info_query = db_query("select * from app_reports where entities_id='" . db_input($entity_id) . "' and reports_type='" . $reports_type . "' and created_by='" . $app_logged_users_id . "' " . $where_str);
        if (!$reports_info = db_fetch_array($reports_info_query))
        {
            $default_reports_query = db_query("select * from app_reports where entities_id='" . db_input($entity_id) . "' and reports_type='default'");
            $default_reports = db_fetch_array($default_reports_query);

            $sql_data = array('name' => '',
                'entities_id' => $entity_id,
                'reports_type' => $reports_type,
                'in_menu' => 0,
                'in_dashboard' => 0,
                'listing_order_fields' => (isset($default_reports['listing_order_fields']) ? $default_reports['listing_order_fields'] : ''),
                'created_by' => $app_logged_users_id,
                'parent_entity_id' => $parent_entity_id,
                'parent_item_id' => $parent_item_id,
            );
            db_perform('app_reports', $sql_data);

            $reports_id = db_insert_id();

            if ($default_reports)
            {
                $filters_query = db_query("select rf.*, f.name from app_reports_filters rf left join app_fields f on rf.fields_id=f.id where rf.reports_id='" . db_input($default_reports['id']) . "' order by rf.id");
                while ($v = db_fetch_array($filters_query))
                {
                    $sql_data = array('reports_id' => $reports_id,
                        'fields_id' => $v['fields_id'],
                        'filters_condition' => $v['filters_condition'],
                        'filters_values' => $v['filters_values'],
                    );

                    db_perform('app_reports_filters', $sql_data);
                }
            }

            $reports_info_query = db_query("select * from app_reports where id='" . db_input($reports_id) . "'");
            $reports_info = db_fetch_array($reports_info_query);
        }

        //check if parent reports was not set
        if ($reports_info['parent_id'] == 0 and $reports_type != 'entity')
        {
            reports::auto_create_parent_reports($reports_info['id']);

            $reports_info = db_find('app_reports', $reports_info['id']);
        }

        return $reports_info;
    }

    public static function get_parent_reports($reports_id, $paretn_reports = array())
    {
        $report_info = db_find('app_reports', $reports_id);

        if ($report_info['parent_id'] > 0)
        {
            $paretn_reports[] = $report_info['parent_id'];

            $paretn_reports = reports::get_parent_reports($report_info['parent_id'], $paretn_reports);
        }

        return $paretn_reports;
    }

    public static function auto_create_parent_reports($reports_id)
    {
        global $app_logged_users_id;

        $report_info = db_find('app_reports', $reports_id);
        $entity_info = db_find('app_entities', $report_info['entities_id']);

        if ($entity_info['parent_id'] > 0 and $report_info['parent_id'] == 0)
        {
            $sql_data = array('name' => '',
                'entities_id' => $entity_info['parent_id'],
                'reports_type' => 'parent',
                'in_menu' => 0,
                'in_dashboard' => 0,
                'created_by' => $app_logged_users_id,
            );

            db_perform('app_reports', $sql_data);

            $insert_id = db_insert_id();

            db_perform('app_reports', array('parent_id' => $insert_id), 'update', "id='" . db_input($reports_id) . "' and created_by='" . $app_logged_users_id . "'");

            reports::auto_create_parent_reports($insert_id);
        }
    }

    public static function delete_reports_by_item_id($entity_id, $item_id)
    {
        $report_info_query = db_query("select * from app_reports where parent_entity_id='" . $entity_id . "' and parent_item_id='" . $item_id . "'");
        while ($report_info = db_fetch_array($report_info_query))
        {
            self::delete_reports_by_id($report_info['id']);
        }
    }

    public static function delete_reports_by_id($reports_id)
    {
        $report_info_query = db_query("select * from app_reports where id='" . db_input($reports_id) . "'");
        if ($report_info = db_fetch_array($report_info_query))
        {
            //delete paretn reports
            self::delete_parent_reports($report_info['id']);

            db_query("delete from app_reports where id='" . db_input($report_info['id']) . "'");
            db_query("delete from app_reports_filters where reports_id='" . db_input($report_info['id']) . "'");

            //delete users filters
            $filters_query = db_query("select * from app_users_filters where reports_id='" . db_input($report_info['id']) . "'");
            while ($filters = db_fetch_array($filters_query))
            {
                db_query("delete from app_users_filters where id='" . db_input($filters['id']) . "'");
                db_query("delete from app_user_filters_values where filters_id='" . db_input($filters['id']) . "'");
            }
        }
    }

    public static function delete_reports_by_type($type)
    {
        $report_info_query = db_query("select * from app_reports where reports_type='" . $type . "'");
        if ($report_info = db_fetch_array($report_info_query))
        {
            self::delete_reports_by_id($report_info['id']);
        }
    }

    public static function delete_parent_reports($reports_id)
    {
        $paretn_reports = reports::get_parent_reports($reports_id);

        if (count($paretn_reports) > 0)
        {
            foreach ($paretn_reports as $id)
            {
                db_query("delete from app_reports where id='" . db_input($id) . "'");
                db_query("delete from app_reports_filters where reports_id='" . db_input($id) . "'");
            }
        }
    }

    public static function prepare_filters_having_query($sql_query_array)
    {
        $sql_query = '';

        if (count($sql_query_array) > 0)
        {
            $sql_query = ' having (' . implode(' and ', $sql_query_array) . ')';
        }

        return $sql_query;
    }

    public static function add_filters_query($reports_id, $listing_sql_query='', $prefix = '', $is_parent_report = false,$exclude_fields = [])
    {
        global $sql_query_having, $app_entities_cache;

        $reports_info_query = db_query("select * from app_reports where id='" . db_input($reports_id) . "'");
        if ($reports_info = db_fetch_array($reports_info_query))
        {

            $sql_query = array();

            $filters_query = db_query("select rf.*, f.name,f.type from app_reports_filters rf left join app_fields f on rf.fields_id=f.id where rf.reports_id='" . db_input($reports_info['id']) . "' and is_active=1 " . (count($exclude_fields) ? " and f.id not in (" . implode(',',$exclude_fields). ")":"") . " order by rf.id");
            while ($filters = db_fetch_array($filters_query))
            {
                if ($filters['filters_condition'] == 'empty_value')
                {
                    switch ($filters['type'])
                    {
                        case 'fieldtype_date_updated':
                            $sql_query[] = "e.date_updated=0";
                            break;
                        case 'fieldtype_date_added':
                            $sql_query[] = "e.date_added=0";
                            break;
                        case 'fieldtype_input_date':
                        case 'fieldtype_input_datetime':
                        case 'fieldtype_input_date_extra':
                        case 'fieldtype_dropdown':
                        case 'fieldtype_radioboxes':
                        case 'fieldtype_progress':
                        case 'fieldtype_image_map':
                        case 'fieldtype_entity_multilevel':
                        case 'fieldtype_stages':
                        case 'fieldtype_autostatus':
                        case 'fieldtype_time':
                        case 'fieldtype_jalali_calendar':
                            $sql_query[] = "field_" . $filters['fields_id'] . "=0";
                            break;
                        default:
                            $sql_query[] = "length(field_" . $filters['fields_id'] . ")=0";
                            break;
                    }
                } elseif ($filters['filters_condition'] == 'not_empty_value')
                {
                    switch ($filters['type'])
                    {
                        case 'fieldtype_date_updated':
                            $sql_query[] = "e.date_updated>0";
                            break;
                        case 'fieldtype_date_added':
                            $sql_query[] = "e.date_added>0";
                            break;
                        case 'fieldtype_input_date':
                        case 'fieldtype_input_datetime':
                        case 'fieldtype_input_date_extra':
                        case 'fieldtype_dropdown':
                        case 'fieldtype_radioboxes':
                        case 'fieldtype_progress':
                        case 'fieldtype_image_map':
                        case 'fieldtype_entity_multilevel':
                        case 'fieldtype_stages':
                        case 'fieldtype_autostatus':
                        case 'fieldtype_time':
                        case 'fieldtype_jalali_calendar':
                            $sql_query[] = "field_" . $filters['fields_id'] . ">0";
                            break;
                        default:
                            $sql_query[] = "length(field_" . $filters['fields_id'] . ")>0";
                            break;
                    }
                } 
                elseif (in_array($filters['type'], fields_types::get_types_for_search()) and !in_array($filters['type'],['fieldtype_tags']))
                {
                    $sql_query = self::add_search_qeury($filters, $reports_info['entities_id'], $sql_query);
                } 
                elseif (strlen($filters['filters_values']) > 0)
                {
                    $sql_query = fields_types::reports_query(array('class' => $filters['type'], 'filters' => $filters, 'entities_id' => $reports_info['entities_id'], 'sql_query' => $sql_query, 'prefix' => $prefix));
                }
            }

            //add filters queries
            if (count($sql_query) > 0)
            {
                $listing_sql_query .= ' and (' . implode(' and ', $sql_query) . ')';
            }

            //add having queries for paretn report only
            if ($is_parent_report and isset($sql_query_having[$reports_info['entities_id']]))
            {
                $listing_sql_query .= reports::prepare_filters_having_query($sql_query_having[$reports_info['entities_id']]);
            }
            
            //check if there are filters in paret reports
            if(!$is_parent_report and $reports_info['parent_id']>0)
            {
                $has_filters_in_paretn_reports = self::has_filters_in_paretn_reports($reports_info['parent_id'], $reports_info['entities_id']);
                
                //echo 'OK='. (int)$has_filters_in_paretn_reports;
            }
            
            if($is_parent_report)
            {
                $has_filters_in_paretn_reports = true;
            }

            //add filters for parent report if exist
            if ($reports_info['parent_id'] > 0)
            {                
                if($has_filters_in_paretn_reports)
                {
                    $report_info_query = db_query("select r.entities_id, (select group_concat(rf.fields_id) from app_reports_filters rf where rf.reports_id=r.id) as filters_fields from app_reports r where r.id='" . db_input($reports_info['parent_id']) . "'");
                    if ($report_info = db_fetch_array($report_info_query))
                    {
                        /**
                         * The sql query "(select item_id from (select e.id ..." need to prepare filters by formula fileds with using having
                         */

                        //print_rr($report_info);

                        $listing_sql_query .= ' and e.parent_item_id in (select item_id from (select e.id as item_id ' . fieldtype_formula::prepare_query_select($report_info['entities_id'], '',false,['fields_in_query'=>$report_info['filters_fields']]) . ' from app_entity_' . $report_info['entities_id'] . ' e where e.id>0 ' . items::add_access_query($report_info['entities_id'], '') . ' ' . reports::add_filters_query($reports_info['parent_id'], '', '', true) . ') as parent_entity_' . $report_info['entities_id'] . ' )';

                    }
                }
            } 
            elseif ($app_entities_cache[$reports_info['entities_id']]['parent_id'] > 0 and in_array($reports_info['reports_type'], ['default','common','common_filters'])) //check access for report type 'default' where parent_id=0
            {
                $listing_sql_query .= items::add_access_query_for_parent_entities($reports_info['entities_id']);
            }
        }

        return $listing_sql_query;
    }
    
    public static function has_filters_in_paretn_reports($parent_report_id, $entity_id)
    {
        global $app_user;
        
        $parent_entities = entities::get_parents($entity_id);
        
        if(!count($parent_entities))
        {
            return false;
        }
        
        $parent_reports = reports::get_parent_reports($parent_report_id,[$parent_report_id]);
        
        $count_filters_query = db_query("select count(*) as total from app_reports_filters where reports_id in (" . implode(',',$parent_reports) . ")");
        $count_filters = db_fetch_array($count_filters_query);
                        
        $count_access_query = db_query("select count(*) as total from app_entities_access where find_in_set('view_assigned',access_schema) and entities_id in (" . implode(',',$parent_entities) . ") and access_groups_id='" . $app_user['group_id'] . "'",false);
        $count_access = db_fetch_array($count_access_query);
        
        $count_rules_query = db_query("select count(*) as total from app_records_visibility_rules where is_active=1 and entities_id in (" . implode(',',$parent_entities) . ") and find_in_set(" . $app_user['group_id'] . ",users_groups)",false);
        $count_rules = db_fetch_array($count_rules_query);
        
        //print_rr($parent_reports);
        //print_rr('Filters: ' . $count_filters['total']);
        //print_rr($parent_entities);
        //print_rr('Access: ' . $count_access['total']);
        //print_rr('Rules: ' . $count_rules['total']);
        
        if($count_filters['total']>0 or ($count_access['total']>0 and $app_user['group_id']>0) or $count_rules['total']>0)
        {
            return true;
        }
        else
        {
            return false;
        }        
    }

    public static function add_search_qeury($field, $current_entity_id, $main_sql_query)
    {
        global $search_keywords, $sql_query_having;

        $filters_values = $field['filters_values'];

        //print_rr($field);
        
        $sql_query = [];

        if (app_parse_search_string($filters_values, $search_keywords))
        {

            $sql_query = array();

            /**
             *  search in fields
             */
            {

                //handle search by ID
                if ($field['type'] == 'fieldtype_id')
                {
                    if (is_numeric($search_keywords[0]))
                        $sql_query[] = "e.id='" . db_input($search_keywords[0]) . "'";
                }
                //handle search by phone
                elseif ($field['type'] == 'fieldtype_phone')
                {
                    if (strlen(preg_replace('/\D/', '', $filters_values)))
                        $sql_query[] = "rukovoditel_regex_replace('[^0-9]','',e.field_" . $field['fields_id'] . ") like '%" . db_input(preg_replace('/\D/', '', $filters_values)) . "%'";
                }
                //handle search by IP
                elseif ($field['type'] == 'fieldtype_input_ip')
                {
                    if ($ip = ip2long($filters_values))
                    {
                        $sql_query[] = "e.field_" . $field['fields_id'] . " = " . $ip;
                    } 
                    else
                    {
                        $sql_query[] = "e.field_" . $field['fields_id'] . " = -1";
                    }
                }
                //handle search by tag
                elseif ($field['type'] == 'fieldtype_tags')
                {
                    $cfg = new fields_types_cfg($field['configuration']);
                    
                    $keyword = str_replace(['and','or'],' ',implode('',$search_keywords));
                    
                    if(substr($keyword,0,1)=='=')
                    {
                        $where_sql = " and name = '" . substr($keyword,1) . "'";
                    }
                    else
                    {
                        $where_sql = " and name like '%" . $keyword . "%'";
                    }
                    
                    if($cfg->get('use_global_list')>0)
                    {
                        $sql_query[] = "(select count(*) as total from app_entity_" . $current_entity_id . "_values cv where cv.items_id=e.id and cv.fields_id='" . $field['fields_id'] . "' and cv.value in (select id from app_global_lists_choices fc where fc.lists_id='" . $cfg->get('use_global_list') . "' {$where_sql}))>0";
                    }
                    else
                    {                       
                       $sql_query[] = "(select count(*) as total from app_entity_" . $current_entity_id . "_values cv where  cv.items_id=e.id and cv.fields_id='" . $field['fields_id'] . "' and cv.value in (select id from app_fields_choices fc where fc.fields_id='" . $field['fields_id'] . "' {$where_sql}))>0";
                    }                                        
                }
                //handle search by entity
                elseif ($field['type'] == 'fieldtype_entity')
                {
                    $cfg = new fields_types_cfg($field['configuration']);
                    if ($heading_field_id = fields::get_heading_id($cfg->get('entity_id')))
                    {
                        $where_str = "select es.id from app_entity_" . $cfg->get('entity_id') . " as es where es.id='" . (int) $filters_values . "'";

                        $where_str .= " or (";
                        for ($i = 0, $n = sizeof($search_keywords); $i < $n; $i++)
                        {
                            switch ($search_keywords[$i])
                            {
                                case '(':
                                case ')':
                                    $where_str .= " " . $search_keywords[$i] . " ";
                                    break;
                                case 'and':
                                case 'or':
                                    $search_type = $search_keywords[$i];
                                    $where_str .= " " . $search_type . " ";
                                    break;
                                default:
                                    $keyword = $search_keywords[$i];

                                    if(substr($keyword,0,1)=='=')
                                    {
                                        $where_str .= "es.field_" . $heading_field_id . " = '" . db_input(substr($keyword,1)) . "'";
                                    }
                                    else
                                    {
                                        $where_str .= "es.field_" . $heading_field_id . " like '%" . db_input($keyword) . "%'";
                                    }
                                    break;
                            }
                        }
                        $where_str .= ")";
                    } else
                    {
                        $where_str = (int) $filters_values;
                    }

                    $sql_query[] = "(select count(*) from app_entity_" . $current_entity_id . "_values as cv where cv.items_id=e.id and cv.fields_id='" . db_input($field['fields_id']) . "' and cv.value in (" . $where_str . "))>0";
                } elseif (in_array($field['type'], ['fieldtype_input_encrypted', 'fieldtype_textarea_encrypted']))
                {
                    $where_str = "(";
                    for ($i = 0, $n = sizeof($search_keywords); $i < $n; $i++)
                    {
                        switch ($search_keywords[$i])
                        {
                            case '(':
                            case ')':
                                $where_str .= " " . $search_keywords[$i] . " ";
                                break;
                            case 'and':
                            case 'or':
                                $search_type = ($field['filters_condition'] == 'search_type_match' ? 'and' : $search_keywords[$i]);
                                $where_str .= " " . $search_type . " ";
                                break;
                            default:
                                $keyword = $search_keywords[$i];

                                if(substr($keyword,0,1)=='=')
                                {
                                    $where_str .= "field_" . $field['fields_id'] . " = '" . db_input(substr($keyword,1)) . "'";
                                }
                                else
                                {
                                    $where_str .= "field_" . $field['fields_id'] . " like '%" . db_input($keyword) . "%'";
                                }

                                break;
                        }
                    }
                    $where_str .= ")";

                    $sql_query_having[$current_entity_id][] = $where_str;
                } elseif (isset($search_keywords) && (sizeof($search_keywords) > 0))
                {
                    $where_str = "(";
                    for ($i = 0, $n = sizeof($search_keywords); $i < $n; $i++)
                    {
                        switch ($search_keywords[$i])
                        {
                            case '(':
                            case ')':
                                $where_str .= " " . $search_keywords[$i] . " ";
                                break;
                            case 'and':
                            case 'or':
                                $search_type = ($field['filters_condition'] == 'search_type_match' ? 'and' : $search_keywords[$i]);
                                $where_str .= " " . $search_type . " ";
                                break;
                            default:
                                $keyword = $search_keywords[$i];

                                if(substr($keyword,0,1)=='=')
                                {
                                    $where_str .= "e.field_" . $field['fields_id'] . " = '" . db_input(substr($keyword,1)) . "'";
                                }
                                else
                                {
                                    $where_str .= "e.field_" . $field['fields_id'] . " like '%" . db_input($keyword) . "%'";
                                }

                                break;
                        }
                    }
                    $where_str .= ")";

                    $sql_query[] = $where_str;
                }
            }
        }

        if (count($sql_query) > 0)
        {
            //print_r($sql_query);

            $main_sql_query[] = implode(' or ', $sql_query);

            //print_r($main_sql_query);
        }

        return $main_sql_query;
    }

    public static function add_order_query($reports_order_fields, $entities_id)
    {
        global $app_heading_fields_cache, $app_fields_cache;

        $listing_sql_query_join = '';
        $listing_sql_query = '';
        $listing_sql_query_from = '';

        $listing_order_fields_id = array();
        $listing_order_fields = array();
        $listing_order_clauses = array();

        foreach (explode(',', $reports_order_fields??'') as $key => $order_field)
        {
            if (strlen($order_field) == 0)
                continue;

            $order = explode('_', $order_field);

            $alias = 'fc' . $key;

            $field_id = $order[0];
            $order_cause = $order[1];

            //prepare sql for order by last comment date
            if ($field_id == 'lastcommentdate')
            {
                $listing_order_fields[] = "(select comments.date_added from app_comments comments where comments.items_id=e.id and comments.entities_id='{$entities_id}' order by comments.date_added desc limit 1) " . $order_cause;

                continue;
            }

            //prepare order for fields
            $field_info_query = db_query("select * from app_fields where id='" . db_input((int) $field_id) . "'");
            if ($field_info = db_fetch_array($field_info_query))
            {
                $listing_order_fields_id[] = $field_id;
                $listing_order_clauses[$field_id] = $order_cause;
                $field_cfg = new fields_types_cfg($field_info['configuration']);


                if (in_array($field_info['type'], array('fieldtype_created_by', 'fieldtype_date_added', 'fieldtype_id', 'fieldtype_date_updated')))
                {
                    $listing_order_fields[] = 'e.' . str_replace('fieldtype_', '', $field_info['type']) . ' ' . $order_cause;
                } elseif ($field_info['type'] == 'fieldtype_dropdown_multilevel' and $field_cfg->get('value_displya_own_column'))
                {
                    $field_id_array = explode('-', $field_id);
                    $level = $field_id_array[1];
                    $field_id = (int) $field_id;

                    if ($level == 0)
                    {
                        $field_name_to_join = "SUBSTRING_INDEX(field_" . $field_id . ",','," . ($level + 1) . ")";
                    } else
                    {
                        $field_name_to_join = "REPLACE(SUBSTRING_INDEX(REPLACE(field_" . $field_id . ",SUBSTRING_INDEX(field_" . $field_id . ",','," . $level . "),''),','," . ($level + 1) . "),',','')";
                    }

                    if ($field_cfg->get('use_global_list') > 0)
                    {
                        $listing_sql_query_join .= " left join app_global_lists_choices {$alias} on {$alias}.id=" . $field_name_to_join;
                    } else
                    {
                        $listing_sql_query_join .= " left join app_fields_choices {$alias} on {$alias}.id=" . $field_name_to_join; //field_" . (int)$field_id . "_level_" . $level;
                    }

                    $listing_order_fields[] = "{$alias}.sort_order " . $order_cause . ", {$alias}.name " . $order_cause;
                } elseif (in_array($field_info['type'], array('fieldtype_stages','fieldtype_dropdown', 'fieldtype_dropdown_multiple', 'fieldtype_checkboxes', 'fieldtype_radioboxes', 'fieldtype_grouped_users', 'fieldtype_dropdown_multilevel')))
                {
                    if ($field_cfg->get('use_global_list') > 0)
                    {
                        $listing_sql_query_join .= " left join app_global_lists_choices {$alias} on {$alias}.id=e.field_" . $field_id;
                    } else
                    {
                        $listing_sql_query_join .= " left join app_fields_choices {$alias} on {$alias}.id=e.field_" . $field_id;
                    }

                    $listing_order_fields[] = "{$alias}.sort_order " . $order_cause . ", {$alias}.name " . $order_cause;
                } elseif (in_array($field_info['type'], array('fieldtype_entity', 'fieldtype_entity_ajax', 'fieldtype_entity_multilevel')))
                {
                    $entity_info_query = db_query("select * from app_entities where id='" . $field_cfg->get('entity_id') . "'");
                    if ($entity_info = db_fetch_array($entity_info_query))
                    {
                        //if entity is Users then order by firstname/lastname
                        if ($entity_info['id'] == 1)
                        {
                            $listing_sql_query_join .= " left join app_entity_{$entity_info['id']} {$alias} on {$alias}.id=e.field_" . $field_id;
                            $listing_order_fields[] = (CFG_APP_DISPLAY_USER_NAME_ORDER == 'firstname_lastname' ? "{$alias}.field_7 {$order_cause}, {$alias}.field_8 {$order_cause}" : "{$alias}.field_8 {$order_cause}, {$alias}.field_7 {$order_cause}");
                        }
                        //if exist haeading field then order by heading  
                        elseif ($heading_id = fields::get_heading_id($entity_info['id']))
                        {

                            if ($app_heading_fields_cache[$heading_id]['type'] == 'fieldtype_id')
                            {
                                $listing_order_fields[] = 'e.field_' . $field_id . ' ' . $order_cause;
                            } elseif (in_array($app_heading_fields_cache[$heading_id]['type'], array('fieldtype_created_by', 'fieldtype_date_added', 'fieldtype_date_updated')))
                            {
                                $listing_sql_query_join .= " left join app_entity_{$entity_info['id']} {$alias} on {$alias}.id=e.field_" . $field_id;
                                $listing_order_fields[] = "{$alias}." . str_replace('fieldtype_', '', $app_heading_fields_cache[$heading_id]['type']) . ' ' . $order_cause;
                            } else
                            {
                                $listing_sql_query_join .= " left join app_entity_{$entity_info['id']} {$alias} on {$alias}.id=e.field_" . $field_id;
                                $listing_order_fields[] = "{$alias}.field_{$heading_id} " . $order_cause;
                            }
                        }
                        //default order by ID
                        else
                        {
                            $listing_order_fields[] = 'e.field_' . $field_id . ' ' . $order_cause;
                        }
                    }
                } elseif (in_array($field_info['type'], array(
                            'fieldtype_input_numeric',
                            'fieldtype_input_numeric_comments',
                            'fieldtype_date_added',
                            'fieldtype_input_date',
                            'fieldtype_input_datetime',
                            'fieldtype_js_formula',
                            'fieldtype_auto_increment',
                            'fieldtype_input_date_extra',
                        )))
                {
                    $listing_order_fields[] = '(e.field_' . $field_id . '+0) ' . $order_cause;
                } elseif (in_array($field_info['type'], ['fieldtype_mysql_query']))
                {
                    $cfg = new fields_types_cfg($app_fields_cache[$entities_id][$field_id]['configuration']);
                    if ($cfg->get('dinamic_query') != 1 and preg_match('/sum|min|max|count/', $cfg->get('select_query')))
                    {
                        $listing_order_fields[] = '(field_' . $field_id . '+0) ' . $order_cause;
                    } else
                    {
                        $listing_order_fields[] = '(field_' . $field_id . ') ' . $order_cause;
                    }
                } elseif (in_array($field_info['type'], array(
                            'fieldtype_formula',
                            'fieldtype_months_difference',
                            'fieldtype_years_difference',
                            'fieldtype_hours_difference',
                            'fieldtype_days_difference',
                            'fieldtype_dynamic_date',
                            'fieldtype_related_records',
                        )))
                {
                    $listing_order_fields[] = '(field_' . $field_id . ') ' . $order_cause;
                } 
                elseif (in_array($field_info['type'], array('fieldtype_parent_item_id')))
                {
                    $entity_info = db_find('app_entities', $field_info['entities_id']);
                    if ($entity_info['parent_id'] > 0)
                    {
                        if ($heading_id = fields::get_heading_id($entity_info['parent_id']))
                        {
                            switch($app_fields_cache[$entity_info['parent_id']][$heading_id]['type'])
                            {
                                case 'fieldtype_id':
                                    $listing_order_fields[] = 'e.parent_item_id ' . $order_cause;
                                    break;
                                case 'fieldtype_date_added':
                                    $listing_sql_query_join .= " left join app_entity_{$entity_info['parent_id']} {$alias} on {$alias}.id=e.parent_item_id";
                                    $listing_order_fields[] = "{$alias}.date_added " . $order_cause;
                                    break;
                                case 'fieldtype_created_by':
                                    $listing_sql_query_join .= " left join app_entity_{$entity_info['parent_id']} {$alias} on {$alias}.id=e.parent_item_id";
                                    $listing_order_fields[] = "{$alias}.created_by " . $order_cause;                                    
                                    break;
                                default:
                                    $listing_sql_query_join .= " left join app_entity_{$entity_info['parent_id']} {$alias} on {$alias}.id=e.parent_item_id";
                                    $listing_order_fields[] = "{$alias}.field_{$heading_id} " . $order_cause;
                                    break;
                            }
                            
                        } 
                        else
                        {
                            $listing_order_fields[] = 'e.parent_item_id ' . $order_cause;
                        }
                    }
                } elseif (in_array($field_info['type'], array('fieldtype_attachments', 'fieldtype_input_file')))
                {
                    $listing_order_fields[] = 'SUBSTRING(e.field_' . $field_id . ',LOCATE("_",e.field_' . $field_id . ')) ' . $order_cause;
                } else
                {
                    $listing_order_fields[] = 'e.field_' . $field_id . ' ' . $order_cause;
                }
            }
        }

        if (count($listing_order_fields) > 0)
        {
            $listing_sql_query .= " order by " . implode(',', $listing_order_fields);
        } else
        {
            $listing_sql_query .= " order by e.id ";
        }

        return array('listing_sql_query' => $listing_sql_query,
            'listing_sql_query_join' => $listing_sql_query_join,
            'listing_order_fields_id' => $listing_order_fields_id,
            'listing_order_fields' => $listing_order_fields,
            'listing_sql_query_from' => $listing_sql_query_from,
            'listing_order_clauses' => $listing_order_clauses);
    }

    static function prepare_dates_sql_filters_operator($value)
    {             
        $value = trim($value);
        
        switch(true)
        {
            case substr($value,0,2)=='<=':
                $operator = '<=';
                break;
            case substr($value,0,1)=='<':
                $operator = '<';
                break;
            case substr($value,0,2)=='>=':
                $operator = '>=';
                break;            
            case substr($value,0,1)=='>':
                $operator = '>';
                break; 
            case substr($value,0,2)=='!=':
                $operator = '!=';
                break; 
            default:
                $operator = '=';
                break;
        }
        
        return $operator;
    }
    
    public static function prepare_dates_sql_filters($filters, $prefix = 'e')
    {
                
        if ($prefix === false)
        {            
            $prefix = '';
        } 
        else
        {            
            $prefix = (strlen($prefix) ? $prefix . '.' : 'e.');
        }

        if ($filters['type'] == 'fieldtype_date_added')
        {
            $field_name = $prefix . 'date_added';
        } 
        elseif ($filters['type'] == 'fieldtype_date_updated')
        {
            $field_name = $prefix . 'date_updated';
        }
        else
        {
            $field_name = $prefix . 'field_' . $filters['fields_id'];
        }

        //to fix issue with FROM_UNIXTIME that return -1 hour difference then php		
        {
            //$field_name = $field_name . '+3600';
        }
        
        //print_rr($filters);
        
        $field = db_find('app_fields',$filters['fields_id']);
        $cfg = new settings($field['configuration']);
        

        $sql = array();

        $values = explode(',', $filters['filters_values']);

        switch ($filters['filters_condition'])
        {
            case 'filter_by_days':
                if (strlen($values[0]) > 0)
                {
                    $sql_or = array();                    
                    foreach (explode('&', $values[0]) as $v)
                    {
                        $use_function = (strstr($v, '-') ? 'DATE_SUB' : 'DATE_ADD');
                        $operator = self::prepare_dates_sql_filters_operator($v);
                        $v = str_replace(array('+', '-','<','>','=','"',"!"), '', trim($v));  
                        $minutes = ($filters['type'] == 'fieldtype_input_datetime' and in_array($operator,['>','<','>=','<='])) ? ' %H:%i':'';
                        
                        if($field['type']=='fieldtype_input_date_extra' and $cfg->get('timepicker')==1 and in_array($operator,['>','<','>=','<=']))
                        {
                            $minutes = ' %H:%i';
                        }

                        $sql_or[] = "FROM_UNIXTIME(" . $field_name . ",'%Y-%m-%d{$minutes}'){$operator}date_format(" . $use_function . "(now(),INTERVAL " . (int) $v . " DAY),'%Y-%m-%d{$minutes}')";
                    }

                    if (count($sql_or) > 0)
                        $sql[] = "(" . implode(' or ', $sql_or) . ")";
                } else
                {
                    if (strlen($values[1]) > 0)
                    {
                        if ($filters['type'] == 'fieldtype_jalali_calendar')
                        {
                            $values[1] = fieldtype_jalali_calendar::jalali_date_to_gregorian($values[1]);
                        }
                        
                        $sql[] = $field_name . ">=" . get_date_timestamp($values[1]);                      
                    }

                    if (strlen($values[2]) > 0)
                    {
                        if ($filters['type'] == 'fieldtype_jalali_calendar')
                        {
                            $values[2] = fieldtype_jalali_calendar::jalali_date_to_gregorian($values[2]);
                        }

                        $sql[] = $field_name . "<=" .  (strlen($values[2])==10 ? get_date_timestamp($values[2])+86399 : get_date_timestamp($values[2]));                                                
                    }
                    
                    $sql[] = "{$field_name}!=0";
                }
                break;
            case 'filter_by_week':

                $values = strlen($values[0]) > 0 ? $values[0] : 0;

                switch (CFG_APP_FIRST_DAY_OF_WEEK)
                {
                    case 0:
                        $myslq_date_format = '%Y-%V';
                        break;
                    case 1:
                        $myslq_date_format = '%Y-%v';
                        break;
                    default:
                        $myslq_date_format = '%Y-%v';
                        break;
                }

                $sql_or = array();
                foreach (explode('&', $values) as $v)
                {
                    $use_function = (strstr($v, '-') ? 'DATE_SUB' : 'DATE_ADD');
                    $operator = self::prepare_dates_sql_filters_operator($v);
                    $v = str_replace(array('+', '-','<','>','=','"',"!"), '', trim($v));

                    $sql_or[] = "FROM_UNIXTIME(" . $field_name . ",'" . $myslq_date_format . "'){$operator}date_format(" . $use_function . "(now(),INTERVAL " . (int) $v . " WEEK),'" . $myslq_date_format . "')";
                }

                if (count($sql_or) > 0)
                    $sql[] = "(" . implode(' or ', $sql_or) . ")";

                break;
            case 'filter_by_month':

                $values = strlen($values[0]) > 0 ? $values[0] : 0;

                $sql_or = array();
                foreach (explode('&', $values) as $v)
                {
                    $use_function = (strstr($v, '-') ? 'DATE_SUB' : 'DATE_ADD');
                    $operator = self::prepare_dates_sql_filters_operator($v);
                    $v = str_replace(array('+', '-','<','>','=','"',"!"), '', trim($v));

                    $sql_or[] = "FROM_UNIXTIME(" . $field_name . ",'%Y-%m'){$operator}date_format(" . $use_function . "(now(),INTERVAL " . (int) $v . " MONTH),'%Y-%m')";
                }

                if (count($sql_or) > 0)
                    $sql[] = "(" . implode(' or ', $sql_or) . ")";

                break;
            case 'filter_by_year':
                $values = strlen($values[0]) > 0 ? $values[0] : 0;

                $sql_or = array();
                foreach (explode('&', $values) as $v)
                {
                    $use_function = (strstr($v, '-') ? 'DATE_SUB' : 'DATE_ADD');
                    $operator = self::prepare_dates_sql_filters_operator($v);
                    $v = str_replace(array('+', '-','<','>','=','"',"!"), '', trim($v));

                    $sql_or[] = "FROM_UNIXTIME(" . $field_name . ",'%Y'){$operator}date_format(" . $use_function . "(now(),INTERVAL " . (int) $v . " YEAR),'%Y')";
                }

                if (count($sql_or) > 0)
                    $sql[] = "(" . implode(' or ', $sql_or) . ")";
                break;
            case 'filter_by_overdue':
                $sql[] = "FROM_UNIXTIME(" . $field_name . ",'%Y-%m-%d')<date_format(now(),'%Y-%m-%d') and " . str_replace('+3600', '', $field_name) . ">0";
                break;
            case 'filter_by_overdue_with_time':
                $sql[] = "FROM_UNIXTIME(" . $field_name . ",'%Y-%m-%d %H:%i')<date_format(now(),'%Y-%m-%d %H:%i') and " . str_replace('+3600', '', $field_name) . ">0";
                break;
        }

        return $sql;
    }

    public static function prepare_numeric_sql_filters($filters, $prefix = 'e')
    {
        $values = preg_split("/(&|\|)/", $filters['filters_values'], 0, PREG_SPLIT_DELIM_CAPTURE);

        if (strlen($values[0]) > 0)
        {
            $values[1] = (isset($values[1]) ? $values[1] : '');

            if ($values[1] == '|')
            {
                $values = array_merge(array('', '|'), $values);
            } else
            {
                $values = array_merge(array('', '&'), $values);
            }
        }

        $sql = array();
        $sql_and = array();
        $sql_or = array();

        if (strlen($prefix))
            $prefix .= '.';

        for ($i = 1; $i < count($values); $i += 2)
        {
            if (!isset($values[$i + 1]))
                continue;

            if (preg_match("/!=|>=|<=|>|</", $values[$i + 1], $matches))
            {
                $operator = $matches[0];
                $value = (float) str_replace($matches[0], '', $values[$i + 1]);
            } elseif (!is_numeric($values[$i + 1]))
            {
                $operator = '=';
                $value = "'" . substr($values[$i + 1], 0, 100) . "'";
            } else
            {
                $operator = '=';
                $value = (float) $values[$i + 1];
            }

            switch ($values[$i])
            {
                case '|':
                    $sql_or[] = $prefix . 'field_' . $filters['fields_id'] . $operator . $value;
                    break;
                case '&':
                    $sql_and[] = $prefix . 'field_' . $filters['fields_id'] . $operator . $value;
                    break;
            }
        }

        //print_r($sql_or);
        //print_r($sql_and);

        if (count($sql_or) > 0)
            $sql[] = "(" . implode(' or ', $sql_or) . ")";
        if (count($sql_and) > 0)
            $sql[] = "(" . implode(' and ', $sql_and) . ")";

        return $sql;
    }

    public static function render_filters_dropdown_menu($report_id, $path = '', $redirect_to = 'report', $parent_reports_id = 0)
    {
        $url_params = '';

        if (strlen($path) > 0)
        {
            $url_params = '&path=' . $path;
        }

        $parent_reports_param = '';
        if ($parent_reports_id > 0)
        {
            $url_params .= '&parent_reports_id=' . $parent_reports_id;

            $report_info = db_find('app_reports', $parent_reports_id);
        } else
        {
            $report_info = db_find('app_reports', $report_id);
        }

        $entity_info = db_find('app_entities', $report_info['entities_id']);



        $count_filters = 0;
        $html = '<ul class="dropdown-menu" role="menu">';
        $html .= '<li>' . link_to_modalbox(TEXT_FILTERS_FOR_ENTITY_SHORT . ': <b>' . $entity_info['name'] . '</b>', url_for('reports/filters_form', 'reports_id=' . $report_id . '&redirect_to=' . $redirect_to . $url_params)) . '</li>';
        $html .= '<li class="divider"></li>';

        $filters_query = db_query("select rf.*, f.name, f.type from app_reports_filters rf, app_fields f  where rf.fields_id=f.id and rf.reports_id='" . db_input(($parent_reports_id > 0 ? $parent_reports_id : $report_id)) . "' order by rf.id");
        while ($v = db_fetch_array($filters_query))
        {

            $edit_url = url_for('reports/filters_form', 'id=' . $v['id'] . '&reports_id=' . $report_id . '&redirect_to=' . $redirect_to . $url_params);
            $delete_url = url_for('reports/filters', 'action=delete&id=' . $v['id'] . '&reports_id=' . $report_id . '&redirect_to=' . $redirect_to . $url_params);

            if (in_array($v['filters_condition'], array('empty_value', 'not_empty_value', 'filter_by_overdue', 'filter_by_overdue_with_time')))
            {
                $fitlers_values = reports::get_condition_name_by_key($v['filters_condition']);
            } else
            {
                $fitlers_values = reports::render_filters_values($v['fields_id'], $v['filters_values'], '<br>', $v['filters_condition']);
            }

            $html .= '
        <li class="dropdown-submenu">' . link_to_modalbox(fields_types::get_option($v['type'], 'name', $v['name']), $edit_url) . '
          <ul class="dropdown-menu">
            <li class="filters-values-content">
              ' . link_to_modalbox($fitlers_values, $edit_url) . '
            </li>
            <li class="divider"></li>
            <li>
      				' . link_to('<i class="fa fa-trash-o"></i> ' . TEXT_BUTTON_REMOVE_FILTER, $delete_url) . '
      			</li>
          </ul>
        </li>
      ';

            $count_filters++;
        }
        $html .= '
      <li class="divider"></li>
			<li>
				' . link_to_modalbox('<i class="fa fa-plus-circle"></i> ' . TEXT_BUTTON_ADD_NEW_REPORT_FILTER, url_for('reports/filters_form', 'reports_id=' . $report_id . '&redirect_to=' . $redirect_to . $url_params)) . '
			</li>
      ' . ($count_filters > 0 ? '      
      <li>
				' . link_to('<i class="fa fa-trash-o"></i> ' . TEXT_BUTTON_REMOVE_ALL_FILTERS, url_for('reports/filters', 'action=delete&id=all&reports_id=' . $report_id . '&redirect_to=' . $redirect_to . $url_params)) . '
			</li>' : '') . '
    </ul>';

        return $html;
    }

    public static function render_filters_values($fields_id, $filters_values, $separator = '<br>', $filters_condition='')
    {
        global $app_choices_cache, $app_users_cache, $app_global_choices_cache;

        $field_info = db_find('app_fields', $fields_id);

        $html = '';

        switch ($field_info['type'])
        {
            case 'fieldtype_related_mail':
                if($filters_values=='has_related_emails')
                {
                    $html = TEXT_HAS_RELATED_EMALS;
                }
                elseif($filters_values=='has_unread_emails')
                {
                    $html = TEXT_HAS_UNREAD_RELATED_EMALS;
                }
                else
                {
                    $html = TEXT_NO_RELATED_EMALS;
                }
                
                break;
            case 'fieldtype_user_accessgroups':
                $list = array();
                foreach (explode(',', $filters_values) as $id)
                {
                    if (strlen($name = access_groups::get_name_by_id($id)))
                    {
                        $list[] = $name;
                    }
                }

                $html = implode($separator, $list);
                break;
            case 'fieldtype_user_status':
                $html = ($filters_values == 1 ? TEXT_ACTIVE : TEXT_INACTIVE);
                break;
            case 'fieldtype_parent_item_id':

                $entity_info = db_find('app_entities', $field_info['entities_id']);

                $output = array();
                foreach (explode(',', $filters_values) as $item_id)
                {
                    $items_info_sql = "select e.* from app_entity_" . $entity_info['parent_id'] . " e where e.id='" . db_input($item_id) . "'";
                    $items_query = db_query($items_info_sql);
                    if ($item = db_fetch_array($items_query))
                    {
                        $output[] = items::get_heading_field($entity_info['parent_id'], $item['id']);
                    }
                }

                $html = implode($separator, $output);

                break;
            case 'fieldtype_related_records':
                $filters_values = (strlen($filters_values) ? explode(',', $filters_values) : [0 => '']);
                $html = ($filters_values[0] == 'include' ? TEXT_FILTERS_DISPLAY_WITH_RELATED_RECORDS : TEXT_FILTERS_DISPLAY_WITHOUT_RELATED_RECORDS);
                break;
            case 'fieldtype_entity_multilevel':
            case 'fieldtype_entity_ajax':
            case 'fieldtype_entity':

                $cfg = fields_types::parse_configuration($field_info['configuration']);

                $field_heading_id = 0;
                $fields_query = db_query("select f.* from app_fields f where f.is_heading=1 and  f.entities_id='" . db_input($cfg['entity_id']) . "'");
                if ($fields = db_fetch_array($fields_query))
                {
                    $field_heading_id = $fields['id'];
                }

                $output = array();
                foreach (explode(',', $filters_values) as $item_id)
                {
                    $items_info_sql = "select e.* from app_entity_" . $cfg['entity_id'] . " e where e.id='" . db_input($item_id) . "'";
                    $items_query = db_query($items_info_sql);
                    if ($item = db_fetch_array($items_query))
                    {
                        if ($cfg['entity_id'] == 1)
                        {
                            $output[] = $app_users_cache[$item['id']]['name'];
                        } 
                        elseif ($field_heading_id > 0)
                        {
                            $output[] = items::get_heading_field_value($field_heading_id, $item);
                        } 
                        else
                        {
                            $output[] = $item['id'];
                        }
                    }
                }

                $html = implode($separator, $output);
                break;
            case 'fieldtype_formula':
            case 'fieldtype_input_numeric':
            case 'fieldtype_input_numeric_comments':
            case 'fieldtype_years_difference':
            case 'fieldtype_hours_difference':
            case 'fieldtype_days_difference':
            case 'fieldtype_mysql_query':
            case 'fieldtype_auto_increment':
            case 'fieldtype_php_code':
                $html = $filters_values;
                break;
            case 'fieldtype_access_group':

                $list = array();
                foreach (explode(',', $filters_values) as $id)
                {
                    if ($id == 'current_user_group_id')
                    {
                        $list[] = TEXT_CURRENT_USER_GROUP;
                    }
                    else
                    {
                        $list[] = access_groups::get_name_by_id($id);
                    }
                }

                $html = implode($separator, $list);
                break;
            case 'fieldtype_autostatus':
            case 'fieldtype_checkboxes':
            case 'fieldtype_radioboxes':
            case 'fieldtype_dropdown':
            case 'fieldtype_dropdown_multiple':
            case 'fieldtype_dropdown_multilevel':
            case 'fieldtype_grouped_users':
            case 'fieldtype_image_map':
            case 'fieldtype_tags':
            case 'fieldtype_stages':
            case 'fieldtype_color':

                $cfg = new fields_types_cfg($field_info['configuration']);


                $list = array();
                foreach (explode(',', $filters_values) as $id)
                {
                    if ($cfg->get('use_global_list') > 0)
                    {
                        if (isset($app_global_choices_cache[$id]))
                        {
                            $list[] = $app_global_choices_cache[$id]['name'];
                        }
                    } else
                    {
                        if (isset($app_choices_cache[$id]))
                        {
                            $list[] = $app_choices_cache[$id]['name'];
                        }
                    }
                }

                $html = implode($separator, $list);

                break;
            case 'fieldtype_progress':
                $list = array();
                foreach (explode(',', $filters_values) as $v)
                {
                    $list[] = $v . '%';
                }
                $html = implode($separator, $list);
                break;
            case 'fieldtype_boolean_checkbox':
            case 'fieldtype_boolean':
                $html = fieldtype_boolean::get_boolean_value($field_info, $filters_values);
                break;
            case 'fieldtype_date_added':
            case 'fieldtype_date_updated':
            case 'fieldtype_input_date':
            case 'fieldtype_input_datetime':
            case 'fieldtype_input_date_extra':
            case 'fieldtype_dynamic_date':
            case 'fieldtype_jalali_calendar':
                $values = explode(',', $filters_values);

                if (strlen($values[0]) > 0)
                {
                    if (in_array($filters_condition, array('empty_value', 'not_empty_value', 'filter_by_overdue')))
                    {
                        $html = '';
                    } else
                    {
                        switch ($filters_condition)
                        {
                            case 'filter_by_days':
                                $html = TEXT_FILTER_BY_DAYS;
                                break;
                            case 'filter_by_week':
                                $html = TEXT_FILTER_BY_WEEK;
                                break;
                            case 'filter_by_month':
                                $html = TEXT_FILTER_BY_MONTH;
                                break;
                            case 'filter_by_year':
                                $html = TEXT_FILTER_BY_YEAR;
                                break;
                        }

                        $html .= ': ' . $values[0];
                    }
                } elseif ($field_info['type'] == 'fieldtype_jalali_calendar')
                {
                    if (strlen($values[1]) > 0)
                    {
                        $html = TEXT_DATE_FROM . ': ' . $values[1] . ' ';
                    }

                    if (strlen($values[2]) > 0)
                    {
                        $html .= TEXT_DATE_TO . ': ' . $values[2] . ' ';
                    }
                } else
                {
                    if (strlen($values[1]) > 0)
                    {
                        $value = ($field_info['type'] == 'fieldtype_input_date' ? format_date(get_date_timestamp($values[1])) : format_date_time(get_date_timestamp($values[1])));
                        $html = TEXT_DATE_FROM . ': ' . $value . ' ';
                    }

                    if (strlen($values[2]) > 0)
                    {
                        $value = ($field_info['type'] == 'fieldtype_input_date' ? format_date(get_date_timestamp($values[2])) : format_date_time(get_date_timestamp($values[2])));
                        $html .= TEXT_DATE_TO . ': ' . $value . ' ';
                    }
                }


                break;
            case 'fieldtype_created_by':
            case 'fieldtype_user_roles':
            case 'fieldtype_users_approve':
            case 'fieldtype_users':
            case 'fieldtype_users_ajax':
                $list = array();
                foreach (explode(',', $filters_values) as $id)
                {
                    if (isset($app_users_cache[$id]))
                    {
                        $list[] = $app_users_cache[$id]['name'];
                    }

                    if ($id == 'current_user_id')
                    {
                        $list[] = TEXT_CURRENT_USER;
                    }
                }

                $html = implode($separator, $list);
                break;
        }

        return $html;
    }

    public static function get_condition_name_by_key($condition)
    {
        switch ($condition)
        {
            case 'include':
                return TEXT_CONDITION_INCLUDE;
                break;
            case 'exclude':
                return TEXT_CONDITION_EXCLUDE;
                break;
            case 'empty_value':
                return TEXT_CONDITION_EMPTY_VALUE;
                break;
            case 'not_empty_value':
                return TEXT_CONDITION_NOT_EMPTY_VALUE;
                break;
            case 'filter_by_overdue':
                return TEXT_FILTER_BY_OVERDUE_DATE;
                break;
            case 'filter_by_overdue_with_time':
                return TEXT_OVERDUE_DATE_WITH_TIME;
                break;
            default:
                return TEXT_CONDITION_INCLUDE;
                break;
        }
    }

    public static function get_count_fixed_columns($reports_id, $has_with_selected = 1)
    {
        $reports_info_query = db_query("select * from app_reports where id='" . db_input($reports_id) . "'");
        if ($reports_info = db_fetch_array($reports_info_query))
        {
            $cfg = entities::get_cfg($reports_info['entities_id']);

            $number_fixed_field = (int) $cfg['number_fixed_field_in_listing'];
            $number_fixed_field = ($number_fixed_field > 0 ? ($number_fixed_field + $has_with_selected) : 0);

            return $number_fixed_field;
        }
    }

    public static function force_filter_by($filter_by)
    {
        $filter_by = explode(':', $filter_by);

        $field_query = db_query("select id, type, entities_id from app_fields where id='" . db_input($filter_by[0]) . "'");
        if ($field = db_fetch_array($field_query))
        {
            switch ($field['type'])
            {
                case 'fieldtype_created_by':
                    return " and e.created_by='" . $filter_by[1] . "'";
                    break;
                case 'fieldtype_parent_item_id':
                    return " and e.parent_item_id='" . $filter_by[1] . "'";
                    break;
                case 'fieldtype_entity_multilevel':
                case 'fieldtype_dropdown':
                case 'fieldtype_autostatus':
                case 'fieldtype_radioboxes':
                case 'fieldtype_stages':
                    return " and e.field_" . $filter_by[0] . " = '" . $filter_by[1] . "'";
                    break;
                default:
                    return " and (select count(*) from app_entity_" . $field['entities_id'] . "_values as cv where cv.items_id=e.id and cv.fields_id='" . db_input($field['id']) . "' and cv.value in (" . $filter_by[1] . "))>0";
                    break;
            }
        }
    }

    static function count_filters_by_reports_id($reports_id)
    {
        $count_filters = 0;
        $reports_list = [];
        $reports_list[] = $reports_id;
        $reports_list = reports::get_parent_reports($reports_id, $reports_list);

        foreach ($reports_list as $report_id)
        {
            $count_filters += db_count('app_reports_filters', $report_id, 'reports_id');
        }

        return $count_filters;
    }
    
    static function count_filters_by_reports_type($entities_id, $reports_type)
    {
        $count_filters = 0;
        $reports_info_query = db_query("select * from app_reports where entities_id='" . db_input($entities_id) . "' and reports_type='" . $reports_type . "'");
        if ($reports_info = db_fetch_array($reports_info_query))
        {
            $count_filters = self::count_filters_by_reports_id($reports_info['id']);
        }
        
        return $count_filters;
    }
    
    static function auto_create_report_by_type($entities_id, $reports_type, $is_report_by_user=false)
    {
        global $app_user;
        
        $reports_info_query = db_query("select * from app_reports where entities_id='" . db_input($entities_id) . "' and reports_type='" . $reports_type . "' " . ($is_report_by_user ? " and created_by=" . $app_user['id']:""));
        if (!$reports_info = db_fetch_array($reports_info_query))
        {
            $sql_data = array('name' => '',
                'entities_id' => $entities_id,
                'reports_type' => $reports_type,
                'in_menu' => 0,
                'in_dashboard' => 0,
                'listing_order_fields' => '',
                'created_by' => $app_user['id'],
            );

            db_perform('app_reports', $sql_data);
            $insert_id = db_insert_id();

            reports::auto_create_parent_reports($insert_id);
            
            return $insert_id;
        }
        else
        {
            return $reports_info['id'];
        }
    }
    
    static function get_reports_id_by_type($entities_id, $type, $is_report_by_user=false)
    {
        global $app_user;
        
        $reports_query = db_query("select * from app_reports where entities_id='" . db_input($entities_id). "' and reports_type='{$type}'" . ($is_report_by_user ? " and created_by=" . $app_user['id']:""));
        if($reports = db_fetch_array($reports_query))
        {
            return $reports['id']; 
        }
        else
        {
            return false;
        }
    }

}
