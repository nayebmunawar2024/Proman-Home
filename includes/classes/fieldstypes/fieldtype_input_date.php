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

class fieldtype_input_date
{

    public $options;

    function __construct()
    {
        $this->options = array('title' => TEXT_FIELDTYPE_INPUT_DATE_TITLE);
    }

    function get_configuration()
    {
        $cfg = array();

        $cfg[TEXT_SETTINGS][] = array('title' => TEXT_NOTIFY_WHEN_CHANGED, 'name' => 'notify_when_changed', 'type' => 'checkbox', 'tooltip_icon' => TEXT_NOTIFY_WHEN_CHANGED_TIP);

        $cfg[TEXT_SETTINGS][] = array('title' => TEXT_HIDE_FIELD_IF_EMPTY, 'name' => 'hide_field_if_empty', 'type' => 'checkbox', 'tooltip_icon' => TEXT_HIDE_FIELD_IF_EMPTY_TIP);

        $cfg[TEXT_SETTINGS][] = array('title' => TEXT_DATE_FORMAT, 'name' => 'date_format', 'type' => 'input', 'tooltip' => TEXT_DEFAULT . ': ' . CFG_APP_DATE_FORMAT . ', ' . TEXT_DATE_FORMAT_IFNO, 'params' => array('class' => 'form-control input-small'));
        
        
        
        $cfg[TEXT_RESTRICTIONS][] = array(
            'title' => TEXT_MIN_DATE,
            'name' => 'min_date',
            'type' => 'input',
            'tooltip_icon' => TEXT_DEFAULT_DATE_INFO,
            'params' => array('class' => 'form-control input-small', 'type' => 'number'));

        $cfg[TEXT_RESTRICTIONS][] = array(
            'title' => TEXT_MAX_DATE,
            'name' => 'max_date',
            'type' => 'input',
            'tooltip_icon' => TEXT_DEFAULT_DATE_INFO,
            'params' => array('class' => 'form-control input-small', 'type' => 'number'));
        
        $cfg[TEXT_RESTRICTIONS][] = array(
            'title' => TEXT_DO_NOT_APPLY_RESTRICTIONS_FOR,
            'name' => 'restrictions_off',
            'type' => 'dropdown',
            'choices' => access_groups::get_choices(),
            'tooltip' => TEXT_SELECT_USERS_GROUPS,
            'params' => array('class' => 'form-control input-xlarge chosen-select', 'multiple' => 'multiple'));
        
        //extra
        $cfg[TEXT_EXTRA][] = array('title' => TEXT_SHOW_WEEK_NUMBERS, 'name' => 'show_week_numbers', 'type' => 'checkbox');

        $cfg[TEXT_EXTRA][] = array('title' => TEXT_DEFAULT_DATE,
            'name' => 'default_value',
            'type' => 'input',
            'tooltip_icon' => TEXT_DEFAULT_DATE_INFO,
            'params' => array('class' => 'form-control input-small', 'type' => 'number'));
               

        $cfg[TEXT_EXTRA][] = array('title' => TEXT_IS_UNIQUE_FIELD_VALUE, 'name' => 'is_unique', 'type' => 'dropdown', 'choices' => fields_types::get_is_unique_choices(_POST('entities_id')), 'tooltip_icon' => TEXT_IS_UNIQUE_FIELD_VALUE_TIP, 'params' => array('class' => 'form-control input-large'));
        $cfg[TEXT_EXTRA][] = array('title' => TEXT_ERROR_MESSAGE, 'name' => 'unique_error_msg', 'type' => 'input', 'tooltip_icon' => TEXT_UNIQUE_FIELD_VALUE_ERROR_MSG_TIP, 'tooltip' => TEXT_DEFAULT . ': ' . TEXT_UNIQUE_FIELD_VALUE_ERROR, 'params' => array('class' => 'form-control input-xlarge'));


        $cfg[TEXT_COLOR][] = array('title' => TEXT_OVERDUE_DATES,
            'name' => 'background',
            'type' => 'colorpicker',
            'tooltip_icon' => TEXT_DATE_BACKGROUND_TOOLTIP);
        
        $cfg[TEXT_COLOR][] = array(
            'title' => TEXT_IGNORE_CURRENT_DAY,
            'name' => 'ignore_current_day',
            'type' => 'checkbox');
        

        $cfg[TEXT_COLOR][] = array('title' => TEXT_DAYS_BEFORE_DATE,
            'name' => 'day_before_date',
            'type' => 'input-with-colorpicker',
            'tooltip_icon' => TEXT_DAYS_BEFORE_DATE_TIP);

        $cfg[TEXT_COLOR][] = array('title' => TEXT_DAYS_BEFORE_DATE . ' 2',
            'name' => 'day_before_date2',
            'type' => 'input-with-colorpicker',
            'tooltip_icon' => TEXT_DAYS_BEFORE_DATE_TIP);


        $choices = ['' => ''];

        $fields_query = db_query("select * from app_fields where type in ('fieldtype_stages','fieldtype_dropdown','fieldtype_radioboxes','fieldtype_dropdown_multiple','fieldtype_tags','fieldtype_checkboxes','fieldtype_autostatus') and entities_id='" . db_input($_POST['entities_id']) . "'");
        while($fields = db_fetch_array($fields_query))
        {
            $choices[$fields['id']] = $fields['name'];
        }


        $cfg[TEXT_COLOR][] = array('title' => TEXT_DISABLE_COLOR,
            'name' => 'disable_color_by_field',
            'type' => 'dropdown',
            'choices' => $choices,
            'tooltip_icon' => TEXT_DISABLE_COLOR_BY_FIELD_TIP,
            'params' => array('class' => 'form-control input-large', 'onChange' => 'fields_types_ajax_configuration(\'disable_color_by_field_values\',this.value)'),
        );

        $cfg[TEXT_COLOR][] = array('name' => 'disable_color_by_field_values', 'type' => 'ajax', 'html' => '<script>fields_types_ajax_configuration(\'disable_color_by_field_values\',$("#fields_configuration_disable_color_by_field").val())</script>');

        return $cfg;
    }

    function get_ajax_configuration($name, $value)
    {
        $cfg = array();

        switch($name)
        {
            case 'disable_color_by_field_values':
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
                            'name' => 'disable_color_by_field_choices',
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

    function render($field, $obj, $params = array())
    {
        global $app_user;
        
        $cfg = new settings($field['configuration']);

        if(strlen($obj['field_' . $field['id']]) > 0 and $obj['field_' . $field['id']] != 0)
        {
            $value = date('Y-m-d', $obj['field_' . $field['id']]);
        }
        else
        {
            $value = '';
        }

        if(!isset($params['is_new_item']))
        {
            $params['is_new_item'] = false;
        }

        //handle default value            
        if($params['is_new_item'] == true and strlen($cfg->get('default_value')) > 0 and (strlen($obj['field_' . $field['id']]) == 0 or $obj['field_' . $field['id']] == 0))
        {
            $value = date('Y-m-d', strtotime("+" . (int) $cfg->get('default_value') . " day"));
        }

        $attributes = array('class' => 'form-control fieldtype_input_date field_' . $field['id'] . 
            ($field['is_required'] == 1 ? ' required' : '') . 
            ($cfg->get('is_unique') > 0 ? ' is-unique' : ''));

        $attributes = fields_types::prepare_uniquer_error_msg_param($attributes, $cfg);

        //handle extra attributes
        $exra_attributes = [];
                
        if(!$cfg->isset('restrictions_off') or !in_array($app_user['group_id'],$cfg->get('restrictions_off')))
        {
            if(strlen($cfg->get('min_date')))
            {
                if(substr($cfg->get('min_date'),0,1)=='-')
                {                
                    $exra_attributes[] = 'data-date-start-date="' . date('Y-m-d', strtotime($cfg->get('min_date') . " day")) . '"';
                }
                else
                {
                    $exra_attributes[] = 'data-date-start-date="' . date('Y-m-d', strtotime("+" . (int) $cfg->get('min_date') . " day")) . '"';
                }
            }

            if(strlen($cfg->get('max_date')))
            {
                $exra_attributes[] = 'data-date-end-date="' . date('Y-m-d', strtotime("+" . (int) $cfg->get('max_date') . " day")) . '"';
            }

            if(strlen($cfg->get('min_date')) or strlen($cfg->get('max_date')))
            {
                $attributes['readonly'] = 'readonly';
            }
        }
        
        
        if($cfg->get('show_week_numbers')==1)
        {
            $exra_attributes[] = 'data-date-calendar-weeks="true"';
        }

        

        return '
            <div class="input-group input-medium date datepicker" ' . implode(' ', $exra_attributes) . '>' . 
                input_tag('fields[' . $field['id'] . ']', $value, $attributes) . '<span class="input-group-btn"><button class="btn btn-default date-set" type="button"><i class="fa fa-calendar"></i></button></span>
            </div>' . fields_types::custom_error_handler($field['id']);
    }

    function process($options)
    {
        global $app_changed_fields;

        $value = !is_numeric($options['value']) ? (int) get_date_timestamp($options['value']) : (int) $options['value'];

        if(!$options['is_new_item'])
        {
            $cfg = new fields_types_cfg($options['field']['configuration']);

            if($value != $options['current_field_value'] and $cfg->get('notify_when_changed') == 1)
            {
                $app_changed_fields[] = array(
                    'name' => $options['field']['name'],
                    'value' => format_date($value),
                    'fields_id' => $options['field']['id'],
                    'fields_value' => $value,
                );
            }
        }

        return $value;
    }

    function output($options)
    {
        $cfg = new fields_types_cfg($options['field']['configuration']);

        if(isset($options['is_export']) and strlen($options['value']) > 0 and $options['value'] != 0)
        {
            return format_date($options['value'], $cfg->get('date_format'));
        }
        elseif(strlen($options['value']) > 0 and $options['value'] != 0)
        {
            $html = format_date($options['value'], $cfg->get('date_format'));

            //return simple value if color is disabled
            if(strlen($cfg->get('disable_color_by_field')))
            {
                if(isset($options['item']['field_' . $cfg->get('disable_color_by_field')]))
                {
                    if(is_array($cfg->get('disable_color_by_field_choices')))
                        foreach($cfg->get('disable_color_by_field_choices') as $choices_id)
                        {
                            if(in_array($choices_id, explode(',', $options['item']['field_' . $cfg->get('disable_color_by_field')])))
                            {
                                return $html;
                            }
                        }
                }
            }

            $check_date = $cfg->get('ignore_current_day')==1 ? date('Y-m-d',strtotime("-1 day")) : date('Y-m-d');
            $check_time = $cfg->get('ignore_current_day')==1 ? strtotime("-1 day") : time();
            
            //highlight field if overdue date    
            if((date('Y-m-d', $options['value']) == $check_date or $options['value'] < $check_time) and strlen($cfg->get('background')) > 0)
            {
                $html = render_bg_color_block($cfg->get('background'), format_date($options['value'], $cfg->get('date_format')));
            }

            //highlight field before due date
            if(strlen($cfg->get('day_before_date')) > 0 and strlen($cfg->get('day_before_date_color')) > 0 and $options['value'] > time())
            {
                if($options['value'] < strtotime('+' . $cfg->get('day_before_date') . ' day'))
                {
                    $html = render_bg_color_block($cfg->get('day_before_date_color'), format_date($options['value'], $cfg->get('date_format')));
                }
            }

            //highlight 2 field before due date      
            if(strlen($cfg->get('day_before_date2')) > 0 and strlen($cfg->get('day_before_date2_color')) > 0 and $options['value'] > time())
            {
                if($options['value'] < strtotime('+' . $cfg->get('day_before_date2') . ' day'))
                {
                    $html = render_bg_color_block($cfg->get('day_before_date2_color'), format_date($options['value'], $cfg->get('date_format')));
                }
            }

            //return single value      
            return $html;
        }
        else
        {
            return '';
        }
    }

    function reports_query($options)
    {
        $filters = $options['filters'];
        $sql_query = $options['sql_query'];

        $sql = reports::prepare_dates_sql_filters($filters, $options['prefix']);

        if(count($sql) > 0)
        {
            $sql_query[] = implode(' and ', $sql);
        }

        return $sql_query;
    }

}
