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

class fieldtype_autostatus
{

    public $options;

    function __construct()
    {
        $this->options = array('title' => TEXT_FIELDTYPE_AUTOSTATUS_TITLE, 'has_choices' => true);
    }

    function get_configuration()
    {
        $cfg = array();

        $cfg[TEXT_SETTINGS][] = array('title' => TEXT_NOTIFY_WHEN_CHANGED, 'name' => 'notify_when_changed', 'type' => 'checkbox', 'tooltip_icon' => TEXT_NOTIFY_WHEN_CHANGED_TIP);

        $cfg[TEXT_STAGES_PANEL][] = array('title' => TEXT_TYPE, 'name' => 'panel_type', 'type' => 'dropdown', 'params' => array('class' => 'form-control input-medium'),
            'choices' => ['' => ''] + stages_panel::get_type_choices());

        $cfg[TEXT_STAGES_PANEL][] = array('title' => TEXT_COLOR, 'name' => 'color', 'type' => 'colorpicker');

        $cfg[TEXT_STAGES_PANEL][] = array('title' => TEXT_ACTIVE_ITEM_COLOR, 'name' => 'color_active', 'type' => 'colorpicker');
        
        
        $cfg[TEXT_ACTION][] = array('html'=>'<p>' . TEXT_FIELDTYPE_AUTOSTATUS_ACTION_TIP . '</p>','type'=>'html');
        
        if(is_ext_installed())
        {
            $processes_chocies = [];
            $processes_chocies[0] = '';
            $processes_query = db_query("select id, name from app_ext_processes where entities_id='" . _post::int('entities_id') . "' order by sort_order, name");
            while($processes = db_fetch_array($processes_query))
            {
                $processes_chocies[$processes['id']] = $processes['name'];
            }

            foreach(fields_choices::get_choices(_POST('id'), false) as $choice_id => $choice_name)
            {
                $cfg[TEXT_ACTION][] = array(
                    'title' => fields_choices::prepare_choice_name($choice_name),
                    'name' => 'run_process_for_choice_' . $choice_id,
                    'type' => 'dropdown',
                    'choices' => $processes_chocies,
                    'params' => array('class' => 'form-control input-large'));
            }
        }
        else
        {
            $cfg[TEXT_ACTION][] = array('html' => '<div class="alert alert-warning">' . TEXT_EXTENSION_REQUIRED . '</div>', 'type' => 'html');
        }

        return $cfg;
    }

    function render($field, $obj, $params = array())
    {
        return '<p><table><tr><td>' . fields_choices::render_value($obj['field_' . $field['id']]) . '</td></tr></table></p>' . input_hidden_tag('fields[' . $field['id'] . ']', $obj['field_' . $field['id']]);
    }

    function process($options)
    {
        return $options['value'];
    }

    function output($options)
    {
        return fields_choices::render_value($options['value']);
    }

    function reports_query($options)
    {
        $filters = $options['filters'];
        $sql_query = $options['sql_query'];

        $prefix = (strlen($options['prefix']) ? $options['prefix'] : 'e');

        $sql_query[] = $prefix . '.field_' . $filters['fields_id'] . ($filters['filters_condition'] == 'include' ? ' in ' : ' not in ') . '(' . $filters['filters_values'] . ') ';

        return $sql_query;
    }

    static function set($entities_id, $items_id)
    {
        global $sql_query_having, $app_changed_fields, $app_choices_cache;

        $fields_query = db_query("select * from app_fields where entities_id='" . db_input($entities_id) . "' and type='fieldtype_autostatus'");
        while ($fields = db_fetch_array($fields_query))
        {
            $cfg = new fields_types_cfg($fields['configuration']);

            foreach (fields_choices::get_tree($fields['id'], 0, [], 0, '', '', true) as $choices)
            {
                $reports_info_query = db_query("select * from app_reports where entities_id='" . db_input($entities_id) . "' and reports_type='fields_choices" . $choices['id'] . "'");
                if ($reports_info = db_fetch_array($reports_info_query))
                {
                    $sql_query_having = array();

                    $listing_sql_query = reports::add_filters_query($reports_info['id'], '');

                    //prepare having query for formula fields
                    if (isset($sql_query_having[$entities_id]))
                    {
                        $listing_sql_query .= reports::prepare_filters_having_query($sql_query_having[$entities_id]);
                    }

                    
                    $item_info_query = db_query("select e.* " . fieldtype_formula::prepare_query_select($entities_id, '') . " from app_entity_" . $entities_id . " e where e.id='" . db_input($items_id) . "' " . $listing_sql_query);                                            
                    if ($item_info = db_fetch_array($item_info_query))
                    {
                        if ($choices['id'] != $item_info['field_' . $fields['id']] and $cfg->get('notify_when_changed') == 1)
                        {
                            $app_changed_fields[] = array(
                                'name' => $fields['name'],
                                'value' => $app_choices_cache[$choices['id']]['name'],
                                'fields_id' => $fields['id'],
                                'fields_value' => $choices['id'],
                            );
                        }

                        $sql_data = array(
                            'field_' . $fields['id'] => $choices['id']
                        );

                        db_perform('app_entity_' . $entities_id, $sql_data, 'update', "id='" . db_input($items_id) . "'");
                        
                        //run process
                        if(is_ext_installed() and ($process_id = (int)$cfg->get('run_process_for_choice_' . $choices['id']))>0 and $choices['id'] != $item_info['field_' . $fields['id']])
                        {
                            $process_info_query = db_query("select * from app_ext_processes where id={$process_id}");
                            if($process_info = db_fetch_array($process_info_query))
                            {
                                $_post_fields = $_POST['fields'] ?? []; //save post fields
                                $_POST['fields'] = []; //reset post fields

                                $processes = new processes($entities_id);
                                $processes->items_id = $items_id;
                                $processes->run($process_info, false, true);

                                $_POST['fields'] = $_post_fields; //restore post fields;
                            }
                        }

                        //break from current fields choices
                        break;
                    }
                }
            }
        }

        return true;
    }

}
