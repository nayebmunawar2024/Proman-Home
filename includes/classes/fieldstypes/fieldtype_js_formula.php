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

class fieldtype_js_formula
{

    public $options;

    function __construct()
    {
        $this->options = array('title' => TEXT_FIELDTYPE_JS_FORMULA_TITLE);
    }

    function get_configuration()
    {
        $cfg = array();

        $cfg[] = array('title' => TEXT_FORMULA . fields::get_available_fields_helper($_POST['entities_id'], 'fields_configuration_formula'), 'name' => 'formula', 'type' => 'code_small', 'tooltip' => TEXT_JS_FORMULA_TIP, 'params' => array('class' => 'form-control code'));

        $cfg[] = array('title' => tooltip_icon(TEXT_NUMBER_FORMAT_INFO) . TEXT_NUMBER_FORMAT, 'name' => 'number_format', 'type' => 'input', 'params' => array('class' => 'form-control input-small input-masked', 'data-mask' => '9/~/~'), 'default' => CFG_APP_NUMBER_FORMAT);
        $cfg[] = array('title' => tooltip_icon(TEXT_CALCULATE_TOTALS_INFO) . TEXT_CALCULATE_TOTALS, 'name' => 'calclulate_totals', 'type' => 'checkbox');
        $cfg[] = array('title' => tooltip_icon(TEXT_CALCULATE_AVERAGE_VALUE_INFO) . TEXT_CALCULATE_AVERAGE_VALUE, 'name' => 'calculate_average', 'type' => 'checkbox');
        $cfg[] = array('title' => TEXT_HIDE_FIELD_IF_EMPTY, 'name' => 'hide_field_if_empty', 'type' => 'checkbox', 'tooltip_icon' => TEXT_HIDE_FIELD_IF_EMPTY_TIP);

        $cfg[] = array('title' => TEXT_PREFIX, 'name' => 'prefix', 'type' => 'input', 'params' => array('class' => 'form-control input-small'));
        $cfg[] = array('title' => TEXT_SUFFIX, 'name' => 'suffix', 'type' => 'input', 'params' => array('class' => 'form-control input-small'));
        $cfg[] = array('title' => TEXT_NEGATIVE_NUMBER_COLOR, 'name' => 'negative_number_color', 'type' => 'colorpicker');

        return $cfg;
    }

    function render($field, $obj, $params = array())
    {
        global $app_fields_cache, $parent_entity_item_id, $app_global_vars, $app_user;
        
        $fields_access_schema = users::get_fields_access_schema($field['entities_id'],$app_user['group_id']);

        $cfg = new fields_types_cfg($field['configuration']);

        $formula = $js_formula = $cfg->get('formula');

        $js_funciton_name = 'form_handle_js_formula_' . $field['id'] . '()';
        $js_funciton_name_delay = 'setTimeout(function (){ ' . $js_funciton_name . '; ' . $this->inlucde_extra_js_fieldtypes($field) . '},10);';

        $html_change_hanlder = '';

        //start build funciton
        $html = '
    	<script>
    		function ' . $js_funciton_name . '
    		{
    				//alert(1)
    		';

        $field_use_global_list = [];
        //prepare app_choices_values
        if(preg_match_all("/get_value\(([^)]*)\)/", $formula, $matches))
        {
            //print_r($matches);
            $prepared_fields = array();
            foreach($matches[1] as $field_id)
            {
                $field_id = str_replace(array('[', ']'), '', $field_id);
                if(!in_array($field_id, $prepared_fields))
                {
                    $prepared_fields[] = $field_id;
                    //echo $field_id;
                    
                    $field_cfg = new settings($app_fields_cache[$field['entities_id']][$field_id]['configuration']);
                            
                    if((int)$field_cfg->get('use_global_list')>0)
                    {
                        $field_use_global_list[] = $field_id;
                        $fields_choices_query = db_query("select id,value from app_global_lists_choices where lists_id='" . $field_cfg->get('use_global_list') . "'");
                        while($fields_choices = db_fetch_array($fields_choices_query))
                        {
                            $html .= 'app_global_choices_values[' . $fields_choices['id'] . ']= ' . (strlen($fields_choices['value']) ? $fields_choices['value'] : 0) . ';' . "\n";
                        }
                    }
                    else
                    {                    
                        $fields_choices_query = db_query("select id,value from app_fields_choices where fields_id='" . $field_id . "'");
                        while($fields_choices = db_fetch_array($fields_choices_query))
                        {
                            $html .= 'app_choices_values[' . $fields_choices['id'] . ']= ' . (strlen($fields_choices['value']) ? $fields_choices['value'] : 0) . ';' . "\n";
                        }
                    }
                }
            }
        }

        //prepare fields values and change handler
        if(preg_match_all("/\[([^]]*)\]/", $formula, $matches))
        {
            //print_r($matches);

            $entities_id = $field['entities_id'];

            foreach($matches[1] as $field_id)
            {
                if(isset($app_fields_cache[$entities_id][$field_id]))
                {
                    //skip hidden fileds
                    if(isset($fields_access_schema[$field_id]))
                    {
                        $html .= 'var field_' . $field_id . ' = 0' . "\n";
                    }
                    else
                    {                    
                        switch($app_fields_cache[$entities_id][$field_id]['type'])
                        {
                            case 'fieldtype_parent_value':
                                $paretn_value = new fieldtype_parent_value();
                                $value = $paretn_value->output(['field' => $app_fields_cache[$entities_id][$field_id], 'item' => ['parent_item_id' => $parent_entity_item_id], 'output_db_value' => true]);
                                $value = str_replace([' ', ','], ['', '.'], $value);
                                $value = (is_numeric($value) ? $value : 0);
                                $html .= 'var field_' . $field_id . ' = ' . $value . "\n";
                                break;
                            case 'fieldtype_input_numeric':
                                $html .= 'var field_' . $field_id . ' = ($("#fields_' . $field_id . '").val().length>0) ? Number($("#fields_' . $field_id . '").val()):0;' . "\n";
                                $html_change_hanlder .= '$("#fields_' . $field_id . '").on("input",function(){ ' . $js_funciton_name_delay . '})' . "\n";
                                break;
                            case 'fieldtype_js_formula':
                                $html .= 'var field_' . $field_id . ' = ($("#fields_' . $field_id . '").val().length>0) ? Number($("#fields_' . $field_id . '").val()):0;' . "\n";
                                break;
                            case 'fieldtype_dropdown_multiple':
                                $html .= 'var field_' . $field_id . ' = $("#fields_' . $field_id . '").val();' . "\n";
                                $html_change_hanlder .= '$("#fields_' . $field_id . '").change(function(){ ' . $js_funciton_name_delay . '})' . "\n";
                                break;
                            case 'fieldtype_dropdown_multilevel':
                                $html .= 'var field_' . $field_id . ' = new Array();' . "\n";
                                $html .= '$(".field_' . $field_id . '").each(function(){ field_' . $field_id . '.push($(this).val()); })' . "\n";
                                $html_change_hanlder .= '$(".field_' . $field_id . '").change(function(){ ' . $js_funciton_name_delay . ' })' . "\n";
                                break;
                            case 'fieldtype_checkboxes':
                                $html .= 'var field_' . $field_id . ' = new Array();' . "\n";
                                $html .= '$(".field_' . $field_id . ':checked").each(function(){ field_' . $field_id . '.push($(this).val()); })' . "\n";
                                $html_change_hanlder .= '$(".field_' . $field_id . '").change(function(){ ' . $js_funciton_name_delay . '})' . "\n";
                                break;
                            case 'fieldtype_radioboxes':
                                $html .= 'var field_' . $field_id . ' = ($(".field_' . $field_id . ':checked").val()>0) ? Number($(".field_' . $field_id . ':checked").val()):0;' . "\n";
                                $html_change_hanlder .= '$(".field_' . $field_id . '").change(function(){ ' . $js_funciton_name_delay . '})' . "\n";
                                break;
                            case 'fieldtype_dropdown':
                                $html .= 'var field_' . $field_id . ' = ($("#fields_' . $field_id . '").val()>0) ? Number($("#fields_' . $field_id . '").val()):0;' . "\n";
                                $html_change_hanlder .= '$("#fields_' . $field_id . '").change(function(){ ' . $js_funciton_name_delay . '})' . "\n";
                                break;
                            case 'fieldtype_boolean_checkbox':
                                $html .= 'var field_' . $field_id . ' = $("#fields_' . $field_id . '").is(":checked");' . "\n";
                                $html_change_hanlder .= '$("#fields_' . $field_id . '").change(function(){ ' . $js_funciton_name_delay . '})' . "\n";
                                break;
                            default:
                                $html .= '
                                                            var field_' . $field_id . ' = 0;
                                                            ';
                                break;
                        }
                    }

                    $html .= 'if($(".form-group-' . $field_id . '").css("display") == "none"){ field_' . $field_id . '=0; }' . "\n";
                }

                //prepare fields
                $js_formula = str_replace('[' . $field_id . ']', 'field_' . $field_id, $js_formula);
            }
        }

        //set app_get_choices_values funciton 
        $js_formula = str_replace('get_value(', 'app_get_choices_values(', $js_formula);
        
        foreach($field_use_global_list as $id)
        {
            $js_formula = str_replace('app_get_choices_values(field_' . $id . ')', 'app_get_global_choices_values(field_' . $id . ')', $js_formula);
        }

        $js_formula = $app_global_vars->apply_to_text($js_formula);

        //echo $js_formula;
        //try calculate js formula to value    
        $html .= '
    	try{	                          
    		 value = ' . $js_formula . ';
    		 value_html = value;';

        //toFixed() returns a string, with the number written with a specified number of decimals:
        $decimals = 2;
        $dec_point = '.';
        $thousands_sep = '';
        if(strlen($cfg->get('number_format')) > 0)
        {
            $format = explode('/', str_replace('*', '', $cfg->get('number_format')));

            $decimals = $format[0];
            $dec_point = $format[1];
            $thousands_sep = $format[2];

            $html .= '
    		 value_html = number_format(value,"' . $decimals . '","' . $dec_point . '","' . $thousands_sep . '")';
        }

        //set value to field
        $html .= '
    		 
    		 $("#fields_' . $field['id'] . '").val(value).change();
    		 
    		 $("#fields_' . $field['id'] . '_html_value").html("' . $cfg->get('prefix') . '"+value_html+"' . $cfg->get('suffix') . '")
    		
    		} 
    		catch (err) {
					alert("' . TEXT_JS_FORMULA_ERROR . ': ' . str_replace(array("\n", "\r", "\n\r"), '', addslashes($js_formula)) . '"+"\n"+err)  				
				}
    		 			    			   
    	 }
							
			 $(function(){ 				
    		' . $html_change_hanlder . '
    		' . ($params['is_new_item'] ? $js_funciton_name : '') . '
    	 })
    	</script>	
    		';

        //$html = '';

        return $html . '<div id="fields_' . $field['id'] . '_html_value" class="form-control-static js-formula-value">' . $cfg->get('prefix') . number_format((float) $obj['field_' . $field['id']], $decimals, $dec_point, $thousands_sep) . $cfg->get('suffix') . '</div>' . input_hidden_tag('fields[' . $field['id'] . ']', $obj['field_' . $field['id']]);
    }

    function process($options)
    {
        return db_prepare_input($options['value']);
    }

    function output($options)
    {        
        $value = $options['value'];

        //just return value if not numeric (not numeric values can be returned using IF operator)
        if(!is_numeric($value))
        {
            return $value;
        }
        
        $cfg = new fields_types_cfg($options['field']['configuration']);
        
        //return non-formated value if export
        if(isset($options['is_export']) and !isset($options['is_print']))
        {
            if(strlen($cfg -> get('number_format')) > 0 and strlen($value) > 0)
            {
                $format = explode('/', str_replace('*', '', $cfg -> get('number_format')));
                $value = number_format($value, $format[0], '.', '');
            }
            
            return $value;
        }

        //return value using number format
        if(strlen($cfg->get('number_format')) > 0 and strlen($value) > 0)
        {
            $format = explode('/', str_replace('*', '', $cfg->get('number_format')));


            $value = number_format($value, $format[0], $format[1], $format[2]);
        }
        elseif(strstr($value, '.'))
        {
            $value = number_format((float) $value, 2, '.', '');
        }        

        //add prefix and sufix
        $value = (strlen($value) ? $cfg->get('prefix') . $value . $cfg->get('suffix') : '');
        
        //color of negative number
        if(strlen($cfg->get('negative_number_color')) and is_numeric($options['value']) and $options['value']<0)
        {
            $value = '<span style="color: ' . $cfg->get('negative_number_color') . '">' . $value . '</span>';
        }

        return $value;
    }

    function reports_query($options)
    {
        $filters = $options['filters'];
        $sql_query = $options['sql_query'];

        $sql = reports::prepare_numeric_sql_filters($filters, $options['prefix']);

        if(count($sql) > 0)
        {
            $sql_query[] = implode(' and ', $sql);
        }

        return $sql_query;
    }

    function inlucde_extra_js_fieldtypes($current_field)
    {
        $html = '';
        $fields_query = db_query("select f.id from app_fields f, app_forms_tabs t where f.forms_tabs_id=t.id and f.type='fieldtype_js_formula' and f.id!='" . $current_field['id'] . "' and f.entities_id='" . $current_field['entities_id'] . "' order by t.sort_order, t.name, f.sort_order, f.name");
        while($fields = db_fetch_array($fields_query))
        {
            $html .= ' form_handle_js_formula_' . $fields['id'] . '();';
        }

        return $html;
    }

}
