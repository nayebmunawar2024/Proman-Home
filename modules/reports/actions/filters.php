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

$current_reports_info_query = db_query("select * from app_reports where id='" . _get::int('reports_id') . "'");
if(!$current_reports_info = db_fetch_array($current_reports_info_query))
{
    $alerts->add(TEXT_REPORT_NOT_FOUND, 'error');
    redirect_to('reports/');
}

switch($app_module_action)
{
    case 'save':

        $values = '';

        if(isset($_POST['values']))
        {
            if(is_array($_POST['values']))
            {
                $values = implode(',', $_POST['values']);
            }
            else
            {
                $values = $_POST['values'];
            }
        }
        
        $sql_data = array('reports_id' => (isset($_GET['parent_reports_id']) ? $_GET['parent_reports_id'] : $_GET['reports_id']),
            'fields_id' => (int)$_POST['fields_id'],
            'filters_condition' => isset($_POST['filters_condition']) ? $_POST['filters_condition'] : '',
            'filters_values' => $values,
            'is_active' => $_POST['is_active']??0,
        );

        if(isset($_GET['id']))
        {
            db_perform('app_reports_filters', $sql_data, 'update', "id='" . db_input($_GET['id']) . "'");
            $filters_id = $_GET['id'];
        }
        else
        {
            db_perform('app_reports_filters', $sql_data);
            $filters_id = db_insert_id();
        }

        if(isset($_POST['save_as_template']))
        {
            $filters_info = db_find('app_reports_filters', $filters_id);

            $check_query = db_query("select count(*) as total from app_reports_filters_templates where fields_id='" . db_input($filters_info['fields_id']) . "' and filters_condition='" . db_input($filters_info['filters_condition']) . "' and filters_values='" . db_input($filters_info['filters_values']) . "' and users_id='" . db_input($app_logged_users_id) . "'");
            $check = db_fetch_array($check_query);

            if($check['total'] == 0 and strlen($filters_info['filters_values']) > 0)
            {
                $sql_data = array(
                    'fields_id' => $filters_info['fields_id'],
                    'filters_condition' => $filters_info['filters_condition'],
                    'filters_values' => $filters_info['filters_values'],
                    'users_id' => $app_logged_users_id,
                );

                db_perform('app_reports_filters_templates', $sql_data);
            }
        }

        break;
    case 'delete':
        if(isset($_GET['id']))
        {
            if($_GET['id'] == 'all')
            {
                db_query("delete from app_reports_filters where reports_id='" . db_input((isset($_GET['parent_reports_id']) ? $_GET['parent_reports_id'] : $_GET['reports_id'])) . "'");
                $alerts->add(TEXT_WARN_DELETE_ALL_FILTERS_SUCCESS, 'success');
            }
            else
            {
                db_query("delete from app_reports_filters where id='" . db_input($_GET['id']) . "'");
                //$alerts->add(TEXT_WARN_DELETE_FILTER_SUCCESS,'success');
            }
        }
        break;

    case 'use_filters_template':
        if(isset($_GET['templates_id']))
        {
            $template_info = db_find('app_reports_filters_templates', $_GET['templates_id']);

            if(isset($_GET['id']))
            {
                $sql_data = array(
                    'filters_condition' => $template_info['filters_condition'],
                    'filters_values' => $template_info['filters_values'],
                );

                db_perform('app_reports_filters', $sql_data, 'update', "id='" . db_input($_GET['id']) . "'");
            }
        }
        break;
    case 'delete_filters_templates':

        db_query("delete from app_reports_filters_templates where id='" . db_input($_GET['templates_id']) . "' and users_id='" . db_input($app_logged_users_id) . "'");

        exit();
        break;

    case 'set_field_fielter_value':

        $field_info_query = db_query("select id, entities_id, type from app_fields where id='" . _post::int('field_id') . "'");
        $field_info = db_fetch_array($field_info_query);

        switch($field_info['type'])
        {
            case 'fieldtype_input':
            case 'fieldtype_text_pattern_static':
            case 'fieldtype_input_encrypted':
            case 'fieldtype_textarea_encrypted':
                $filters_condition = (isset($_POST['search_type_match']) ? 'search_type_match' : 'include');
                break;
            case 'fieldtype_date_added':
            case 'fieldtype_date_updated':
            case 'fieldtype_input_date':
            case 'fieldtype_input_date_extra':
            case 'fieldtype_input_datetime':
            case 'fieldtype_dynamic_date':
            case 'fieldtype_jalali_calendar':
                $filters_condition = 'filter_by_days';
                break;
            default:
                $filters_condition = 'include';
                break;
        }

        if(isset($_POST['field_val']))
        {
            $filters_values = (is_array($_POST['field_val']) ? implode(',', $_POST['field_val']) : $_POST['field_val']);
        }
        else
        {
            $filters_values = '';
        }

        $field_id = _post::int('field_id');
        $reports_id = _get::int('reports_id');
        
        //reset selected items if filter set
        $app_selected_items[$reports_id] = [];

        //find paretn reprot id if it's need
        $reports_id = filters_panels::get_report_id_by_field_id($reports_id, $field_id);

        $sql_data = array(
            'reports_id' => $reports_id,
            'fields_id' => _post::int('field_id'),
            'filters_condition' => $filters_condition,
            'filters_values' => $filters_values,
            'is_active' => 1,
        );

        $reports_filters_query = db_query("select * from app_reports_filters where fields_id='" . $field_id . "' and reports_id='" . $reports_id . "'");
        if($reports_filters = db_fetch_array($reports_filters_query))
        {
            db_perform('app_reports_filters', $sql_data, 'update', "id='" . db_input($reports_filters['id']) . "'");
        }
        else
        {
            db_perform('app_reports_filters', $sql_data);
        }

        exit();
        break;

    case 'set_multiple_fields_fielter_values':

        foreach($_POST['fields_values'] as $field_id => $field_val)
        {
            $field_info_query = db_query("select id, type from app_fields where id='" . $field_id . "'");
            $field_info = db_fetch_array($field_info_query);

            switch($field_info['type'])
            {
                case 'fieldtype_date_added':
                case 'fieldtype_date_updated':
                case 'fieldtype_input_date':
                case 'fieldtype_input_datetime':
                case 'fieldtype_dynamic_date':
                case 'fieldtype_jalali_calendar':
                    $filters_condition = 'filter_by_days';
                    break;
                default:
                    $filters_condition = 'include';
                    break;
            }

            $filters_values = (is_array($field_val) ? implode(',', $field_val) : $field_val);

            $reports_id = _get::int('reports_id');
            $reports_id = filters_panels::get_report_id_by_field_id($reports_id, $field_id);

            $sql_data = array(
                'reports_id' => $reports_id,
                'fields_id' => $field_id,
                'filters_condition' => $filters_condition,
                'filters_values' => $filters_values,
                'is_active' => 1,
            );

            $reports_filters_query = db_query("select * from app_reports_filters where fields_id='" . $field_id . "' and reports_id='" . $reports_id . "'");
            if($reports_filters = db_fetch_array($reports_filters_query))
            {
                db_perform('app_reports_filters', $sql_data, 'update', "id='" . db_input($reports_filters['id']) . "'");
            }
            else
            {
                db_perform('app_reports_filters', $sql_data);
            }
        }

        exit();
        break;

    case 'delete_field_fielter_value':

        $reports_id = _get::int('reports_id');
        $reports_id = filters_panels::get_report_id_by_field_id($reports_id, _post::int('field_id'));

        $reports_filters_query = db_query("select * from app_reports_filters where fields_id='" . _post::int('field_id') . "' and reports_id='" . $reports_id . "'");
        if($reports_filters = db_fetch_array($reports_filters_query))
        {
            db_delete_row('app_reports_filters', $reports_filters['id']);
        }
        exit();
        break;

    case 'reset_panel_filters':
        $fields_list = [];
        $fields_query = db_query("select pf.*, f.name as field_name, f.type as field_type from app_filters_panels_fields pf, app_fields f where pf.fields_id=f.id and pf.panels_id='" . _post::int('panels_id') . "' order by pf.sort_order");
        while($fields = db_fetch_array($fields_query))
        {
            $reports_id = filters_panels::get_report_id_by_field_id(_get::int('reports_id'), $fields['fields_id']);

            db_query("delete from app_reports_filters where reports_id='" . $reports_id . "' and fields_id='" . $fields['fields_id'] . "'");
        }

        exit();
        break;
}

if(strlen($app_module_action) > 0)
{
    //reset current users filter name if do any actions
    $app_current_users_filter[$current_reports_info['id']] = '';

    plugins::handle_action('filters_redirect');

    switch($app_redirect_to)
    {
        case 'listing':
            redirect_to('items/items', 'path=' . $app_path);
            break;
        case 'report':
            redirect_to('reports/view', 'reports_id=' . $_GET['reports_id']);
            break;
        default:
            redirect_to('reports/filters', 'reports_id=' . $_GET['reports_id']);
            break;
    }
}
