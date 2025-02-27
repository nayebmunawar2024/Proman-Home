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

class fieldtype_entity_multilevel
{

    public $options;

    function __construct()
    {
        $this->options = array('title' => TEXT_FIELDTYPE_ENTITY_MULTILEVEL_TITLE);
    }

    function get_configuration()
    {
        $cfg = array();

        $cfg[TEXT_SETTINGS][] = array('title' => TEXT_SELECT_ENTITY,
            'name' => 'entity_id',
            'tooltip' => TEXT_FIELDTYPE_ENTITY_MULTILEVEL_SELECT_ENTITY_TOOLTIP,
            'type' => 'dropdown',
            'choices' => entities::get_choices(),
            'params' => array('class' => 'form-control input-xlarge', 'onChange' => 'fields_types_ajax_configuration(\'fields_for_search_box\',this.value); fields_types_ajax_configuration(\'parent_value_box\',this.value)'),
        );
        
        $cfg[TEXT_SETTINGS][] = array('name' => 'parent_value_box', 'type' => 'ajax', 'html' => '<script>fields_types_ajax_configuration(\'parent_value_box\',$("#fields_configuration_entity_id").val())</script>');

        $cfg[TEXT_SETTINGS][] = array('title' => TEXT_WIDHT,
            'name' => 'width',
            'type' => 'dropdown',
            'choices' => array('input-medium' => TEXT_INPUT_MEDIUM, 'input-large' => TEXT_INPUT_LARGE, 'input-xlarge' => TEXT_INPUT_XLARGE),
            'tooltip_icon' => TEXT_ENTER_WIDTH,
            'params' => array('class' => 'form-control input-medium'));

        $cfg[TEXT_SETTINGS][] = array('title' => TEXT_HIDE_PLUS_BUTTON, 'name' => 'hide_plus_button', 'type' => 'checkbox');

        $cfg[TEXT_SETTINGS][] = array('title' => tooltip_icon(TEXT_DISPLAY_NAME_AS_LINK_INFO) . TEXT_DISPLAY_NAME_AS_LINK, 'name' => 'display_as_link', 'type' => 'checkbox');

        $cfg[TEXT_SETTINGS][] = array('title' => TEXT_HIDE_FIELD_IF_EMPTY, 'name' => 'hide_field_if_empty', 'type' => 'checkbox', 'tooltip_icon' => TEXT_HIDE_FIELD_IF_EMPTY_TIP);

        //TEXT_FIELDS
        $cfg[TEXT_FIELDS][] = array('name' => 'fields_for_search_box', 'type' => 'ajax', 'html' => '<script>fields_types_ajax_configuration(\'fields_for_search_box\',$("#fields_configuration_entity_id").val())</script>');


        return $cfg;
    }

    function get_ajax_configuration($name, $value)
    {
        global $app_entities_cache;

        $cfg = array();

        switch ($name)
        {
            case 'parent_value_box':
                $entities_id = $value;
                
                $choices = [];
                $choices[''] = '';
                $fields_query = db_query("select f.*, t.name as tab_name from app_fields f, app_forms_tabs t where f.type in ('fieldtype_entity','fieldtype_entity_ajax','fieldtype_entity_multilevel') and  f.entities_id='" . $_POST['entities_id'] . "' and f.forms_tabs_id=t.id order by t.sort_order, t.name, f.sort_order, f.name");
                while ($fields = db_fetch_array($fields_query))
                {
                    $field_cfg = new fields_types_cfg($fields['configuration']);

                    if ($field_cfg->get('entity_id') == $app_entities_cache[$entities_id]['parent_id'])
                    {
                        $choices[$fields['id']] = $fields['name'];
                    }
                }

                if(count($choices)>1)
                {
                    $cfg[] = array('title' => VALUE_FROM_PARENT_ENTITY,
                        'name' => 'force_parent_item_id',
                        'type' => 'dropdown',
                        'choices' => $choices,
                        'tooltip_icon' => TEXT_FIELDTYPE_ENTITY_MULTILEVEL_PARENT_FIELD_TIP,
                        'tooltip'=>TEXT_FIELDTYPE_ENTITY_MULTILEVEL_PARENT_FIELD_VALUE_TIP,
                        'params' => array('class' => 'form-control input-xlarge'));
                }
                
                break;
            case 'fields_for_search_box':
                $entities_id = $value;

                $choices = [];

                $fields_query = db_query("select f.*, t.name as tab_name from app_fields f, app_forms_tabs t where is_heading = 0 and f.type not in ('fieldtype_action','fieldtype_parent_item_id') and  f.entities_id='" . $entities_id . "' and f.forms_tabs_id=t.id order by t.sort_order, t.name, f.sort_order, f.name");
                while ($fields = db_fetch_array($fields_query))
                {
                    $choices[$fields['id']] = fields_types::get_option($fields['type'], 'name', $fields['name']) . ' (#' . $fields['id'] . ')';
                }

                $cfg[] = array('title' => TEXT_FIELDS_IN_POPUP,
                    'name' => 'fields_in_popup',
                    'type' => 'dropdown',
                    'choices' => $choices,
                    'tooltip_icon' => TEXT_FIELDS_IN_POPUP_RELATED_ITEMS,
                    'tooltip' => TEXT_SORT_ITEMS_IN_LIST,
                    'params' => array('class' => 'form-control chosen-select chosen-sortable input-xlarge', 'multiple' => 'multiple'));

                $choices = [];

                $fields_query = db_query("select f.*, t.name as tab_name from app_fields f, app_forms_tabs t where f.type in (" . fields_types::get_types_for_search_list() . ") and  f.entities_id='" . $entities_id . "' and f.forms_tabs_id=t.id order by t.sort_order, t.name, f.sort_order, f.name");
                while ($fields = db_fetch_array($fields_query))
                {
                    $choices[$fields['id']] = fields_types::get_option($fields['type'], 'name', $fields['name']);
                }

                $cfg[] = array('title' => TEXT_SEARCH_BY_FIELDS,
                    'name' => 'fields_for_search',
                    'type' => 'dropdown',
                    'choices' => $choices,
                    'tooltip_icon' => TEXT_SEARCH_BY_FIELDS_INFO,
                    'params' => array('class' => 'form-control chosen-select input-xlarge', 'multiple' => 'multiple'));


                $cfg[] = array('title' => TEXT_HEADING_TEMPLATE . fields::get_available_fields_helper($entities_id, 'fields_configuration_heading_template'), 'name' => 'heading_template', 'type' => 'textarea', 'tooltip_icon' => TEXT_HEADING_TEMPLATE_INFO, 'tooltip' => TEXT_ENTER_TEXT_PATTERN_INFO, 'params' => array('class' => 'form-control input-xlarge'));

                $cfg[] = array(
                    'title' => TEXT_COPY_VALUES .
                    fields::get_available_fields_helper($entities_id, 'fields_configuration_copy_values', entities::get_name_by_id($entities_id)) .
                    '<div style="padding-top: 2px;">' . fields::get_available_fields_helper($_POST['entities_id'], 'fields_configuration_copy_values', entities::get_name_by_id($_POST['entities_id'])) . '</div>',
                    'name' => 'copy_values', 'type' => 'textarea', 'tooltip' => TEXT_COPY_FIELD_VALUES_INFO, 'params' => array('class' => 'form-control input-xlarge')
                );


                

                break;
        }

        return $cfg;
    }

    function render($field, $obj, $params = array())
    {
        global $app_module_path, $app_layout, $app_action, $app_entities_cache, $app_user;

        $cfg = new fields_types_cfg($field['configuration']);

        $entities_levels = array_reverse(entities::get_parents($cfg->get('entity_id')));

        //check if there are parent entities
        if (!count($entities_levels))
        {
            return '';
        }

        if (strlen($cfg->get('force_parent_item_id')))
        {
            $entities_levels = [];
            $entities_levels[] = $cfg->get('entity_id');
        }
        else
        {
            $entities_levels[] = $cfg->get('entity_id');
        }



        //print_r($entities_levels);

        $html = '';
        $script = '';
        $script_function = '';
        $html_on_change = '';

        $path_array = [];
        $value = ($obj['field_' . $field['id']] > 0 ? $obj['field_' . $field['id']] : '');

        if (strlen($value))
        {
            $path_array = items::get_path_array($cfg->get('entity_id'), $value);
        }

        for ($i = 0; $i < count($entities_levels); $i++)
        {
            $entities_id = $entities_levels[$i];
            $next_entities_id = (isset($entities_levels[$i + 1]) ? $entities_levels[$i + 1] : false);
            $previous_entities_id = (isset($entities_levels[$i - 1]) ? $entities_levels[$i - 1] : false);

            $attributes = array('class' => 'form-control entity-multilevel' . $field['id'] . ' field_' . $field['id'] . ' '. $cfg->get('width') . (($field['is_required'] == 1 and $entities_id == $cfg->get('entity_id')) ? ' required fieldtype_entity_multilevel' : ''));
            $attributes['data-placeholder'] = entities::get_name_by_id($entities_id);
            $attributes['data-entity-id'] = $entities_id;

            $choices = [];

            foreach ($path_array as $path_info)
            {
                if ($path_info['entities_id'] == $entities_id)
                {
                    $choices[$path_info['items_id']] = $path_info['name'];
                }
            }

            $field_name = ($entities_id == $cfg->get('entity_id') ? 'fields[' . $field['id'] . ']' : 'fields' . $field['id'] . '_entity' . $entities_id );
            $field_id = ($entities_id == $cfg->get('entity_id') ? 'fields_' . $field['id'] : 'fields' . $field['id'] . '_entity' . $entities_id );

            if (strlen($cfg->get('force_parent_item_id')))
            {
                $parent_entity_item_id_val = '$("#fields_' . $cfg->get('force_parent_item_id') . '").val()';
            }
            else
            {
                $parent_entity_item_id_val = ($previous_entities_id ? '$("#fields' . $field['id'] . '_entity' . $previous_entities_id . '").val()' : '0');
            }

            //add "+" button    	
            if ($entities_id == $cfg->get('entity_id'))
            {
                $button_add_entity_id_check = (isset($entities_levels[$i - 1]) ? $entities_levels[$i - 1] : 0);

                $button_add_html = '';
                if ($cfg->get('hide_plus_button') != 1 and $app_action != 'account' and $app_action != 'processes' and $app_layout != 'public_layout.php' and (users::has_access_to_entity($cfg->get('entity_id'), 'create') or users::has_access_to_entity($cfg->get('entity_id'), 'update')) and $cfg->get('entity_id') != 1 and!isset($_GET['is_submodal']))
                {
                    $url_params = 'is_submodal=true&redirect_to=parent_modal&refresh_field=' . $field['id'];

                    $submodal_url = url_for('items/form', $url_params);

                    if(users::has_access_to_entity($cfg->get('entity_id'), 'update'))
                    {
                        $button_add_html .= '<button type="button" id="btn_submodal_edit_item_' . $field['id'] . '" data-item-id=0 class="btn btn-default btn-submodal-open btn-submodal-open-chosen" data-parent-entity-item-id="' . $params['parent_entity_item_id'] . '" data-field-id="' . $field['id'] . '" data-submodal-url="" data-submodal-url-tmp="' . $submodal_url . '"><i class="fa fa-edit" aria-hidden="true"></i></button>';
                    }
                    
                    if(users::has_access_to_entity($cfg->get('entity_id'), 'create'))
                    {
                        $button_add_html .= '<button id="btn_submodal_open' . $field['id'] . '"  data-item-id=0 type="button" class="btn btn-default btn-submodal-open btn-submodal-open-chosen hidden" data-parent-entity-item-id="' . $params['parent_entity_item_id'] . '" data-field-id="' . $field['id'] . '" data-submodal-url="" data-submodal-url-tmp="' . $submodal_url . '"><i class="fa fa-plus" aria-hidden="true"></i></button>';
                    }    
                }

                if (strlen($button_add_html))
                {
                    $html .= '
                        <div class="dropdown-with-plus-btn ' . $cfg->get('width') . '">
                            <div class="left"><p class="slect2_tag" style="margin: 0">' . select_tag($field_name, $choices, $value, $attributes) . '</p></div>
                            <div class="right">' . $button_add_html . '</div>
                         </div>';
                }
                else
                {
                    $html .= '<p class="slect2_tag" style="margin: 0">' . select_tag($field_name, $choices, $value, $attributes) . '</p>';
                }

            }
            else
            {
                $html .= '<p class="slect2_tag">' . select_tag($field_name, $choices, $value, $attributes) . '</p>';

                $button_add_html = '';
            }

            $script .= '
                let is_form_row_' . $field_id . ' = $("#' . $field_id . '").parents(".forms-rows").length;
                    
    		$("#' . $field_id . '").select2({		      
		      width: (is_form_row_' . $field_id . '==0 ? ' . self::get_select2_width_by_class($cfg->get('width'), (strlen($button_add_html) ? true : false)) . ':"100%"),		      
		      ' . ((in_array($app_layout, ['public_layout.php']) or in_array($app_module_path, ['users/account']) or $app_user['id'] == 0) ? '' : 'dropdownParent: $("#ajax-modal"),') . '
		      "language":{
		        "noResults" : function () { return "' . addslashes(TEXT_NO_RESULTS_FOUND) . '"; },
                            "noResults" : function () { return "' . addslashes(TEXT_NO_RESULTS_FOUND) . '"; },
                            "searching" : function () { return "' . addslashes(TEXT_SEARCHING) . '"; },
                            "errorLoading" : function () { return "' . addslashes(TEXT_RESULTS_COULD_NOT_BE_LOADED) . '"; },
                            "loadingMore" : function () { return "' . addslashes(TEXT_LOADING_MORE_RESULTS) . '"; },
                            "removeAllItems" : function () { return "' . addslashes(TEXT_DELETE_SELECTED) . '"; },
                            "removeItem" : function () { return "' . addslashes(TEXT_DELETE) . '"; },
		      },	
		    	allowClear: true,		    	
		      ajax: {
        		url: "' . url_for('dashboard/select2_ml_json', 'action=select_items&form_type=' . $app_module_path . '&entity_id=' . $entities_id . '&field_id=' . $field['id']) . '",
        		dataType: "json",
        		cache: false,		
        		data: function (params) {
				      var query = {
				        search: params.term,
				        page: params.page || 1,
        				parent_entity_item_id: ' . $parent_entity_item_id_val . '
				      }
				
				      // Query parameters will be ?search=[term]&page=[page]
				      return query;
				    },        				        				
        	},        				
					templateResult: function (d) { return $(d.html); },      		        			
	    	}).on("select2:unselecting", function () {
                        $(this).on("select2:opening", function (ev) {
                            ev.preventDefault();
                            $(this).off("select2:opening");
                        });
                    });
    			';

            //action to reset dropdown values

            if (strlen($cfg->get('force_parent_item_id')))
            {
                 $script_function .= '
                    
                    function force_parent_item_' . $field['id'] . '_' . $cfg->get('force_parent_item_id') . '_change(reset_value = true)
                    {
                        if(reset_value)
                        {
                            $("#' . $field_id . '").empty().trigger("change");
                        }
                        
                            
                        let val = $("#fields_' . $cfg->get('force_parent_item_id') . '").val()
                                                        		
                        if(val>0)
                        {								
                            path = "' . $app_entities_cache[$cfg->get('entity_id')]['parent_id'] . '-"+val+"/' . $cfg->get('entity_id') . '";
                            $("#btn_submodal_open' . $field['id'] . '").attr("data-submodal-url",$("#btn_submodal_open' . $field['id'] . '").attr("data-submodal-url-tmp")+"&path="+path)
                                
                            $("#btn_submodal_edit_item_' . $field['id'] . '").attr("data-submodal-url",$("#btn_submodal_open' . $field['id'] . '").attr("data-submodal-url-tmp")+"&path="+path)

                            $("#btn_submodal_open' . $field['id'] . '").removeClass("hidden")
                        }
                        else
                        {
                            $("#btn_submodal_open' . $field['id'] . '").addClass("hidden")	
                        }
                    }
                    ';
                    
                 $html_on_change .= '
                    force_parent_item_' . $field['id'] . '_' . $cfg->get('force_parent_item_id') . '_change(false)
                        
                    $("#fields_' . $cfg->get('force_parent_item_id') . '").change(function (e) {
                            force_parent_item_' . $field['id'] . '_' . $cfg->get('force_parent_item_id') . '_change()		
                    });
    			';
            }
            elseif ($previous_entities_id)
            {
                $html_on_change .= '
    			$("#fields' . $field['id'] . '_entity' . $previous_entities_id . '").change(function (e) {
						$("#' . $field_id . '").empty().trigger("change");	
								
						button_add_entity_id_check' . $field['id'] . '()
      		});
    			';
            }
        }


        //copy fields valuss
        if (strlen($cfg->get('copy_values')))
        {
            $html .= '<div id="fields_' . $field['id'] . '_select2_on"></div>';

            $html_on_change .= '
    			$("#fields_' . $field['id'] . '").on("select2:select", function (e) {
      			var data = e.params.data;
    				$("#fields_' . $field['id'] . '_select2_on").load("' . url_for('dashboard/select2_json', 'action=copy_values&form_type=' . $app_module_path . '&entity_id=' . $cfg->get('entity_id') . '&field_id=' . $field['id']) . '",{item_id:data.id})	
      		});
    			';
        }

        //remove ruquired errro msg
        $html_on_change .= '
    			$("#fields_' . $field['id'] . '").change(function (e) {
                            $("#fields_' . $field['id'] . '-error").hide();
                                
                            prepare_add_edit_button_' . $field['id'] . '()    
                        });
    			';

        $html .= '
    	<script>
        
        ' . $script_function . '
            

        function prepare_add_edit_button_' . $field['id'] . '()
        {
            let value = $("#fields_' . $field['id'] . '").val()

            if($("#fields_' . $field['id'] . '").attr("multiple")=="multiple" || $("#btn_submodal_edit_item_' . $field['id'] . '").length==0)
            {
                $("#btn_submodal_edit_item_' . $field['id'] . '").attr("data-item-id",0).hide()
                return false
            }

            if(value>0)
            {
                $("#btn_submodal_open' . $field['id'] . '").hide()
                $("#btn_submodal_edit_item_' . $field['id'] . '").attr("data-item-id",value).show()
            }
            else
            {
                $("#btn_submodal_open' . $field['id'] . '").show()
                $("#btn_submodal_edit_item_' . $field['id'] . '").attr("data-item-id",0).hide()

            }
        }
    	
    //check if we can display "+" button	
    	function button_add_entity_id_check' . $field['id'] . '()
    	{    		
    		check_val = $("#fields' . $field['id'] . '_entity' . $button_add_entity_id_check . '").val()
                    
                if(typeof check_val == "undefined")
                {
                    return false
                }
    		
    		if(check_val>0)
    		{
    			$("#btn_submodal_open' . $field['id'] . '").removeClass("hidden")
    			
    			path = "";
    			$(".entity-multilevel' . $field['id'] . '").each(function(){
    				path = path+"/"+$(this).attr("data-entity-id")
    				if($(this).val()>0)
    				{
    					path = path+"-"+$(this).val();	
  					}
  				})
    					
    			path = path.substr(1);
    			//console.log(path)
    					
    			$("#btn_submodal_open' . $field['id'] . '").attr("data-submodal-url",$("#btn_submodal_open' . $field['id'] . '").attr("data-submodal-url-tmp")+"&path="+path)
                        $("#btn_submodal_edit_item_' . $field['id'] . '").attr("data-submodal-url",$("#btn_submodal_open' . $field['id'] . '").attr("data-submodal-url-tmp")+"&path="+path)
                }
    		else
    		{
                    $("#btn_submodal_open' . $field['id'] . '").addClass("hidden")		
  		}                                
    				
        }
    					
    		
    	$(function(){
            prepare_add_edit_button_' . $field['id'] . '()

            ' . $script . '

            ' . $html_on_change . '

            button_add_entity_id_check' . $field['id'] . '()		
        })
        		
    	</script>
    ';

        $html .= '<div style="clear: both"></div>' . fields_types::custom_error_handler($field['id']) ;
        
        return $html;
    }

    function process($options)
    {
        return (is_array($options['value']) ? implode(',', $options['value']) : $options['value']);
    }

    function output($options)
    {
        global $app_user;

        if (strlen($options['value']) == 0)
        {
            return '';
        }

        $cfg = new fields_types_cfg($options['field']['configuration']);

        $fields_in_popup_cfg = '';

        if (is_array($cfg->get('fields_in_popup')))
        {
            $fields_in_popup_cfg = implode(',', $cfg->get('fields_in_popup'));
        }

        //prepare sql if not export
        $items_info_formula_sql = '';
        if (!isset($options['is_export']))
        {
            $fields_access_schema = users::get_fields_access_schema($cfg->get('entity_id'), $app_user['group_id']);

            $fields_in_listing = fields::get_heading_id($cfg->get('entity_id')) . (strlen($fields_in_popup_cfg) ? ',' . $fields_in_popup_cfg : '');
            $items_info_formula_sql = fieldtype_formula::prepare_query_select($cfg->get('entity_id'), '', false, array('fields_in_listing' => $fields_in_listing));
        }

        $output = array();
        foreach (explode(',', $options['value']) as $item_id)
        {
            $items_info_sql = "select e.* {$items_info_formula_sql} from app_entity_" . $cfg->get('entity_id') . " e where e.id='" . db_input($item_id) . "'";
            $items_query = db_query($items_info_sql);
            if ($item = db_fetch_array($items_query))
            {
                $name = items::get_heading_field($cfg->get('entity_id'), $item['id']);

                //get fields in popup in not export
                if (!isset($options['is_export']))
                {
                    $fields_in_popup = fields::get_items_fields_data_by_id($item, $fields_in_popup_cfg, $cfg->get('entity_id'), $fields_access_schema);
                    $popup_html = '';
                    if (count($fields_in_popup) > 0)
                    {
                        $popup_html = app_render_fields_popup_html($fields_in_popup);

                        $name = '<span ' . $popup_html . '>' . $name . '</span>';
                    }

                    if ($cfg->get('display_as_link') == 1)
                    {
                        $path_info = items::get_path_info($cfg->get('entity_id'), $item['id']);

                        $name = '<a href="' . url_for('items/info', 'path=' . $path_info['full_path']) . '">' . $name . '</a>';
                    }
                }

                $output[] = $name;
            }
        }


        if (isset($options['is_export']))
        {
            return implode(', ', $output);
        }
        else
        {
            return implode('<br>', $output);
        }
    }

    function reports_query($options)
    {
        $filters = $options['filters'];
        $sql_query = $options['sql_query'];

        $prefix = (strlen($options['prefix']) ? $options['prefix'] : 'e');

        $sql_query[] = $prefix . '.field_' . $filters['fields_id'] . ($filters['filters_condition'] == 'include' ? ' in ' : ' not in ') . '(' . $filters['filters_values'] . ') ';

        return $sql_query;
    }

    static function get_select2_width_by_class($class, $has_add_button)
    {
        if (is_mobile())
        {
            return '($("body").width()-70' . ($has_add_button ? '-37' : '') . ')';
        }

        switch ($class)
        {
            case 'input-small':
                $width = '"120px"';
                break;
            case 'input-medium':
                $width = '"240px"';
                break;
            case 'input-large':
                $width = '"320px"';
                break;
            case 'input-xlarge':
                $width = '"480px"';
                break;
            default:
                $width = '"100%"';
                break;
        }

        return $width;
    }

    static function render_heading_template($item, $entity_info, $field_entity_info, $cfg, $get_html = true)
    {
        $html = '';
        $text = '';

        $field_heading_id = fields::get_heading_id($entity_info['id']);

        if (strlen($heading_template = $cfg->get('heading_template')) and $get_html)
        {
            $fieldtype_text_pattern = new fieldtype_text_pattern();
            $html = $fieldtype_text_pattern->output_singe_text($heading_template, $entity_info['id'], $item);
        }


        if ($cfg->get('entity_id') == 1)
        {
            $text = $app_users_cache[$item['id']]['name'];
        }
        elseif ($field_heading_id > 0)
        {
            $text = items::get_heading_field_value($field_heading_id, $item);
        }
        else
        {
            $text = $item['id'];
        }

        return ['text' => $text, 'html' => '<div>' . (strlen($html) ? $html : $text) . '</div>'];
    }

}
