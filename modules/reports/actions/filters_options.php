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

$is_internal = (isset($_POST['is_internal']) ? true : false);

$field_info = db_find('app_fields', $_POST['fields_id']);
$filter_info = db_find('app_reports_filters', $_POST['id']);

$html = '';

//default conditions
if(in_array($field_info['type'], array('fieldtype_created_by', 'fieldtype_parent_item_id')))
{
    $condition_array = array('include' => TEXT_CONDITION_INCLUDE, 'exclude' => TEXT_CONDITION_EXCLUDE);
}
else
{
    $condition_array = array('include' => TEXT_CONDITION_INCLUDE, 'exclude' => TEXT_CONDITION_EXCLUDE, 'empty_value' => TEXT_CONDITION_EMPTY_VALUE, 'not_empty_value' => TEXT_CONDITION_NOT_EMPTY_VALUE);
}

//add signature requried for appove field
if($field_info['type'] == 'fieldtype_users_approve')
{
    $condition_array['include_signature'] = TEXT_SIGNATURE_REQUIRED;
}


$condition_html = '
    <div class="form-group">
        <label class="col-md-3 control-label" for="filters_condition">' . tooltip_icon(TEXT_FILTERS_CONDITION_TOOLTIP) . TEXT_FILTERS_CONDITION . '</label>
        <div class="col-md-9">	
              ' . select_tag('filters_condition', $condition_array, $filter_info['filters_condition'], array('class' => 'form-control input-medium')) . '      
        </div>			
    </div>  
';

switch($field_info['type'])
{
    case 'fieldtype_input_file':
    case 'fieldtype_attachments':
    case 'fieldtype_image':
    case 'fieldtype_image_ajax':
    case 'fieldtype_onlyoffice':
    case 'fieldtype_3dviewer':    
        
        $condition_array = [
            'empty_value' => TEXT_CONDITION_EMPTY_VALUE, 
            'not_empty_value' => TEXT_CONDITION_NOT_EMPTY_VALUE,            
        ];
        $html = '
            <div class="form-group">
                <label class="col-md-3 control-label" for="filters_condition">' . TEXT_FILTERS_CONDITION . '</label>
                <div class="col-md-9">	
                      ' . select_tag('filters_condition', $condition_array, $filter_info['filters_condition'], array('class' => 'form-control input-large')) . '      
                </div>			
            </div> 
            ';
        
        break;
    
    case 'fieldtype_related_mail':
        $condition_array = [
            'has_related_emails' => TEXT_HAS_RELATED_EMALS, 
            'has_unread_emails' => TEXT_HAS_UNREAD_RELATED_EMALS,
            'no_related_emails' => TEXT_NO_RELATED_EMALS,
        ];
        $html = '
            <div class="form-group">
                <label class="col-md-3 control-label" for="filters_condition">' . TEXT_FILTERS_CONDITION . '</label>
                <div class="col-md-9">	
                      ' . select_tag('values[]', $condition_array, $filter_info['filters_condition'], array('class' => 'form-control input-large')) . '      
                </div>			
            </div> 
            ';
        break;
    case 'fieldtype_user_accessgroups':

        $choices = access_groups::get_choices();

        $attributes = array('class' => 'form-control chosen-select',
            'multiple' => 'multiple',
            'data-placeholder' => TEXT_SELECT_SOME_VALUES);

        $html = $condition_html .
                '<div class="form-group" id="filter-by-values">
	        	<label class="col-md-3 control-label" for="values">' . TEXT_FILTER_BY_VALUES . '</label>
	          <div class="col-md-9">
	        	  ' . select_tag('values[]', $choices, explode(',', $filter_info['filters_values']), $attributes) . '
	          </div>
        </div>';
        break;
    case 'fieldtype_user_status':
        $choices = array('1' => TEXT_ACTIVE, '0' => TEXT_INACTIVE);

        $html = $condition_html .
                '<div class="form-group" id="filter-by-values">
	        	<label class="col-md-3 control-label" for="values">' . TEXT_FILTER_BY_VALUES . '</label>
	          <div class="col-md-9">
	        	  ' . select_tag('values[]', $choices, explode(',', $filter_info['filters_values']), array('class' => 'form-control')) . '
	          </div>
        </div>';

        break;
    case 'fieldtype_parent_item_id':
        $choices = array();
        $entity_info = db_find('app_entities', $field_info['entities_id']);

        if($entity_info['parent_id'] > 0)
        {
            $items_query = db_query("select e.* from app_entity_" . $entity_info['parent_id'] . " e where e.id>0 " . items::add_access_query($entity_info['parent_id'], '') . ' ' . items::add_access_query_for_parent_entities($entity_info['parent_id']) . ' ' . items::add_listing_order_query_by_entity_id($entity_info['parent_id']));
            while($items = db_fetch_array($items_query))
            {
                $choices[$items['id']] = items::get_heading_field($entity_info['parent_id'], $items['id']);
            }
        }

        $attributes = array('class' => 'form-control chosen-select',
            'multiple' => 'multiple',
            'data-placeholder' => TEXT_SELECT_SOME_VALUES);

        $html = $condition_html .
                '<div class="form-group" id="filter-by-values">
        	<label class="col-md-3 control-label" for="values">' . TEXT_FILTER_BY_VALUES . '</label>
          <div class="col-md-9">	
        	  ' . select_tag('values[]', $choices, explode(',', $filter_info['filters_values']), $attributes) . '            
          </div>			
        </div>';
        break;
    case 'fieldtype_related_records':

        $cfg = fields_types::parse_configuration($field_info['configuration']);
        $entity_info = db_find('app_entities', $cfg['entity_id']);

        $filters_values = (strlen($filter_info['filters_values']) ? explode(',', $filter_info['filters_values']) : [0 => '']);

        $html = '
        <div class="form-group">
        	<label class="col-md-3 control-label" for="filters_condition">' . TEXT_FILTERS_DISPLAY . '</label>
          <div class="col-md-9">	
        	  ' . select_tag('values[]', array('include' => TEXT_FILTERS_DISPLAY_WITH_RELATED_RECORDS, 'exclude' => TEXT_FILTERS_DISPLAY_WITHOUT_RELATED_RECORDS), $filters_values[0], array('class' => 'form-control')) . '            
          </div>			
        </div>  
      ';

        if($entity_info['parent_id'] == 0 or ($entity_info['parent_id'] > 0 and strlen($app_path)))
        {

            $table_info = related_records::get_related_items_table_name($field_info['entities_id'], $entity_info['id']);

            $where_sql = [];
            if($entity_info['parent_id'] > 0 and strlen($app_path))
            {
                $path_info = items::parse_path($app_path);
                $where_sql[] = " e.parent_item_id='" . $path_info['parent_entity_item_id'] . "'";
            }

            $where_sql[] = "e.id in (select ri.entity_" . $entity_info['id'] . "_items_id from " . $table_info['table_name'] . " ri)";

            $choices = [];
            $items_query = db_query("select e.* from app_entity_" . $entity_info['id'] . " e where " . implode(' and ', $where_sql));
            while($items = db_fetch_array($items_query))
            {
                $choices[$items['id']] = items::get_heading_field($entity_info['id'], $items['id'], $items);
            }

            $html .= '
              <div class="form-group">
                      <label class="col-md-3 control-label" for="filters_condition">' . TEXT_FILTER_BY_VALUES . '</label>
                <div class="col-md-9">	
                        ' . select_tag('values[]', $choices, $filter_info['filters_values'], array('class' => 'form-control chosen-select', 'multiple' => true)) . '            
                </div>			
              </div>  
            ';
        }
        break;
    case 'fieldtype_entity_multilevel':
    case 'fieldtype_entity_ajax':
    case 'fieldtype_entity':
        $cfg = fields_types::parse_configuration($field_info['configuration']);

        $entity_info = db_find('app_entities', $cfg['entity_id']);

        $listing_sql_query = '';
        $listing_sql_query_join = '';
        $parent_entity_item_id = 0;

        if($entity_info['parent_id'] > 0 and isset($_POST['path']) and $entity_info['parent_id'] == $app_entities_cache[$field_info['entities_id']]['parent_id'])
        {
            $path_array = explode('/', $_POST['path']);
            $v = explode('-', $path_array[count($path_array) - 2]);
            $parent_entity_item_id = $v[1];

            $listing_sql_query .= " and e.parent_item_id='" . db_input($parent_entity_item_id) . "'";
        }

        $default_reports_query = db_query("select * from app_reports where entities_id='" . db_input($cfg['entity_id']) . "' and reports_type='entityfield" . $field_info['id'] . "'");
        if($default_reports = db_fetch_array($default_reports_query))
        {
            $listing_sql_query = reports::add_filters_query($default_reports['id'], $listing_sql_query);

            $info = reports::add_order_query($default_reports['listing_order_fields'], $cfg['entity_id']);
            $listing_sql_query .= $info['listing_sql_query'];
            $listing_sql_query_join .= $info['listing_sql_query_join'];
        }
        else
        {
            $listing_sql_query .= " order by e.id";
        }

        $field_heading_id = 0;
        $fields_query = db_query("select f.* from app_fields f where f.is_heading=1 and  f.entities_id='" . db_input($cfg['entity_id']) . "'");
        if($fields = db_fetch_array($fields_query))
        {
            $field_heading_id = $fields['id'];
        }

        $choices = array();

        $listing_sql = "select e.* from app_entity_" . $cfg['entity_id'] . " e " . $listing_sql_query_join . " where e.id>0 " . $listing_sql_query;
        $items_query = db_query($listing_sql);
        while($item = db_fetch_array($items_query))
        {
            if($cfg['entity_id'] == 1)
            {
                $choices[$item['id']] = $app_users_cache[$item['id']]['name'];
            }
            elseif($field_heading_id > 0)
            {
                //add paretn item name if exist
                $parent_name = '';
                if($parent_entity_item_id != $item['parent_item_id'] and $entity_info['parent_id'] > 0)
                {
                    $parent_name = items::get_heading_field($entity_info['parent_id'], $item['parent_item_id']) . ' > ';
                }

                $choices[$item['id']] = $parent_name . items::get_heading_field_value($field_heading_id, $item);
            }
            else
            {
                $choices[$item['id']] = $item['id'];
            }
        }


        $attributes = array('class' => 'form-control chosen-select',
            'multiple' => 'multiple',
            'data-placeholder' => TEXT_SELECT_SOME_VALUES);

        $html = $condition_html .
                '<div class="form-group">
        	<label class="col-md-3 control-label" for="values">' . TEXT_FILTER_BY_VALUES . '</label>
          <div class="col-md-9">
            ' . select_tag('values[]', $choices, explode(',', $filter_info['filters_values']), $attributes) . '            	        	              
          </div>			
        </div>';


        break;
    case 'fieldtype_access_group':
        $choices = [];
        $choices['current_user_group_id'] = TEXT_CURRENT_USER_GROUP;
        $choices = $choices + fieldtype_access_group::get_choices($field_info);

        $attributes = array('class' => 'form-control chosen-select',
            'multiple' => 'multiple',
            'data-placeholder' => TEXT_SELECT_SOME_VALUES);

        $html = $condition_html .
                '<div class="form-group" id="filter-by-values">
        	<label class="col-md-3 control-label" for="values">' . TEXT_FILTER_BY_VALUES . '</label>
          <div class="col-md-9">
        	  ' . select_tag('values[]', $choices, $filter_info['filters_values'], $attributes) . '
          </div>
        </div>';
        break;
    case 'fieldtype_image_map':
    case 'fieldtype_autostatus':
    case 'fieldtype_checkboxes':
    case 'fieldtype_radioboxes':
    case 'fieldtype_dropdown':
    case 'fieldtype_dropdown_multiple':
    case 'fieldtype_dropdown_multilevel':
    case 'fieldtype_grouped_users':
    case 'fieldtype_tags':
    case 'fieldtype_stages':
    case 'fieldtype_color':

        $cfg = new fields_types_cfg($field_info['configuration']);

        if($cfg->get('use_global_list') > 0)
        {
            $choices = global_lists::get_choices($cfg->get('use_global_list'), false);
        }
        else
        {
            $choices = fields_choices::get_choices($field_info['id'], false);
        }

        $attributes = array('class' => 'form-control chosen-select',
            'multiple' => 'multiple',
            'data-placeholder' => TEXT_SELECT_SOME_VALUES);

        $html = $condition_html .
                '<div class="form-group" id="filter-by-values">
        	<label class="col-md-3 control-label" for="values">' . TEXT_FILTER_BY_VALUES . '</label>
          <div class="col-md-9">	
        	  ' . select_tag('values[]', $choices, explode(',', $filter_info['filters_values']), $attributes) . '            
          </div>			
        </div>';

        break;
    case 'fieldtype_boolean_checkbox':
    case 'fieldtype_boolean':

        $choices = fieldtype_boolean::get_choices($field_info);

        $html = $condition_html .
                '<div class="form-group" id="filter-by-values">
        	<label class="col-md-3 control-label" for="values">' . TEXT_FILTER_BY_VALUES . '</label>
          <div class="col-md-9">	
        	  ' . select_tag('values', $choices, $filter_info['filters_values'], array('class' => 'form-control input-small')) . '            
          </div>			
        </div>';
        break;
    case 'fieldtype_progress':

        $cfg = new fields_types_cfg($field_info['configuration']);

        $choices = array();
        $choices['0'] = '0%';

        for($i = $cfg->get('step'); $i <= 100; $i += $cfg->get('step'))
        {
            $choices[$i] = $i . '%';
        }

        $attributes = array('class' => 'form-control chosen-select',
            'multiple' => 'multiple',
            'data-placeholder' => TEXT_SELECT_SOME_VALUES);

        $html = $condition_html .
                '<div class="form-group" id="filter-by-values">
        	<label class="col-md-3 control-label" for="values">' . TEXT_FILTER_BY_VALUES . '</label>
          <div class="col-md-9">
        	  ' . select_tag('values[]', $choices, $filter_info['filters_values'], $attributes) . '
          </div>
        </div>';
        break;
    case 'fieldtype_created_by':
    case 'fieldtype_user_roles':
    case 'fieldtype_users_approve':
    case 'fieldtype_users':
    case 'fieldtype_users_ajax':

        $access_schema = users::get_entities_access_schema_by_groups($field_info['entities_id']);

        $entity_access_schema = users::get_entities_access_schema($field_info['entities_id'], $app_user['group_id']);

        /**
         *  if user have View Only Own access
         *  then we allows to see users from items which assigned to him only
         *  other users should be hidden
         */
        $users_query_assigned_only = '';
        if(users::has_access('view_assigned', $entity_access_schema) and $app_user['group_id'] > 0)
        {
            //special check for Created By field since valuse not stored in table app_entity_X_values
            if($field_info['type'] == 'fieldtype_created_by')
            {
                $users_fields_list = array();
                $users_fields_query = db_query("select * from app_fields where type = 'fieldtype_users'");
                while($users_fields = db_fetch_array($users_fields_query))
                {
                    $users_fields_list[] = $users_fields['id'];
                }

                $users_query_assigned_only_subquery = '';

                if(count($users_fields_list))
                {
                    $users_query_assigned_only_subquery = " where e.id in (select cvi.items_id from app_entity_" . $field_info['entities_id'] . "_values cvi where cvi.fields_id in (" . implode(',', $users_fields_list) . "))";
                }

                $users_query_assigned_only = " where u.id in (select e.created_by from app_entity_" . $field_info['entities_id'] . " e " . $users_query_assigned_only_subquery . ")";
            }
            else
            {
                $users_query_assigned_only = " where u.id in (select cv.value from app_entity_" . $field_info['entities_id'] . "_values cv where cv.fields_id='" . db_input($field_info['id']) . "' and cv.items_id in (select cvi.items_id from app_entity_" . $field_info['entities_id'] . "_values cvi where cvi.fields_id='" . db_input($field_info['id']) . "' and cvi.value='" . db_input($app_user['id']) . "'))";
            }
        }

        $choices = array();
        $choices['current_user_id'] = TEXT_CURRENT_USER;

        $users_query = db_query("select u.*,a.name as group_name from app_entity_1 u left join app_access_groups a on a.id=u.field_6 " . $users_query_assigned_only . " order by u.field_8, u.field_7");
        while($users = db_fetch_array($users_query))
        {
            if(!isset($access_schema[$users['field_6']]))
            {
                $access_schema[$users['field_6']] = array();
            }

            if($users['field_6'] == 0 or in_array('view', $access_schema[$users['field_6']]) or in_array('view_assigned', $access_schema[$users['field_6']]))
            {
                $group_name = ((isset($users['group_name']) and strlen($users['group_name'])) > 0 ? $users['group_name'] : TEXT_ADMINISTRATOR);
                $choices[$group_name][$users['id']] = $app_users_cache[$users['id']]['name'];
            }
        }

        $attributes = array('class' => 'form-control chosen-select',
            'multiple' => 'multiple',
            'data-placeholder' => TEXT_SELECT_SOME_VALUES);

        $html = $condition_html .
                '<div class="form-group" id="filter-by-values">
        	<label class="col-md-3 control-label" for="values">' . TEXT_FILTER_BY_USERS . '</label>
          <div class="col-md-9">	
        	  ' . select_tag('values[]', $choices, explode(',', $filter_info['filters_values']), $attributes) . '            
          </div>			
        </div>';
        break;
    case 'fieldtype_input_numeric':
    case 'fieldtype_input_numeric_comments':
    case 'fieldtype_formula':
    case 'fieldtype_js_formula':
    case 'fieldtype_months_difference':
    case 'fieldtype_years_difference':
    case 'fieldtype_hours_difference':
    case 'fieldtype_days_difference':
    case 'fieldtype_mysql_query':
    case 'fieldtype_auto_increment':
    case 'fieldtype_php_code':


        $condition_array = array('include' => TEXT_CONDITION_INCLUDE, 'empty_value' => TEXT_CONDITION_EMPTY_VALUE, 'not_empty_value' => TEXT_CONDITION_NOT_EMPTY_VALUE);


        $condition_html = '
  <div class="form-group">
  	<label class="col-md-3 control-label" for="filters_condition">' . tooltip_icon(TEXT_FILTERS_CONDITION_TOOLTIP) . TEXT_FILTERS_CONDITION . '</label>
    <div class="col-md-9">
  	  ' . select_tag('filters_condition', $condition_array, $filter_info['filters_condition'], array('class' => 'form-control input-medium')) . '
    </div>
  </div>
';

        $html = $condition_html . '
          <div class="form-group">
          	<label class="col-md-3 control-label" for="values">' . TEXT_VALUES . '</label>
            <div class="col-md-9">	
          	  ' . input_tag('values', $filter_info['filters_values'], array('class' => 'form-control')) . '
              ' . tooltip_text(TEXT_FILTERS_NUMERIC_FIELDS_TOOLTIP) . '
            </div>			
          </div>  
        ';

        break;
    case 'fieldtype_date_added':
    case 'fieldtype_date_updated':
    case 'fieldtype_input_date':
    case 'fieldtype_input_datetime':
    case 'fieldtype_input_date_extra':
    case 'fieldtype_dynamic_date':
    case 'fieldtype_jalali_calendar':

        //own conditions for date fields
        $condition_array = array(
            'filter_by_days' => TEXT_FILTER_BY_DAYS,
            'filter_by_week' => TEXT_FILTER_BY_WEEK,
            'filter_by_month' => TEXT_FILTER_BY_MONTH,
            'filter_by_year' => TEXT_FILTER_BY_YEAR,
            'filter_by_overdue' => TEXT_FILTER_BY_OVERDUE_DATE,
            'filter_by_overdue_with_time' => TEXT_OVERDUE_DATE_WITH_TIME,
            'empty_value' => TEXT_CONDITION_EMPTY_VALUE,
            'not_empty_value' => TEXT_CONDITION_NOT_EMPTY_VALUE
        );

        $condition_html = '
        <div class="form-group">
        	<label class="col-md-3 control-label" for="filters_condition">' . TEXT_FILTERS_CONDITION . '</label>
          <div class="col-md-9">	
        	  ' . select_tag('filters_condition', $condition_array, $filter_info['filters_condition'], array('class' => 'form-control input-large', 'id' => 'filters_condition_dates_filter')) . '            
          </div>			
        </div>  
      ';

        if(strlen($filter_info['filters_values']))
        {
            $values = explode(',', $filter_info['filters_values']);
        }
        else
        {
            $values = ['0' => '', '1' => '', '2' => ''];
        }

        $datepicker = '';
        $attributes = ['class' => 'form-control'];
        switch($field_info['type'])
        {
            case 'fieldtype_input_date_extra':                
                $cfg = new fields_types_cfg($field_info['configuration']);                
                $datepicker = $cfg->get('timepicker') =='1' ? 'datetimepicker-field':'datepicker';
                break;
            case 'fieldtype_input_date':
                $datepicker = 'datepicker';
                break;
            case 'fieldtype_jalali_calendar':
                $cfg = new fields_types_cfg($field_info['configuration']);
                $attributes = ['class' => 'form-control ' . ($cfg->get('time_picker') == 1 ? 'jalali-datetimepicker' : 'jalali-datepicker')];
                break;
            default:
                $datepicker = 'datetimepicker-field';
                break;
        }

        $html = $condition_html . '
          <div class="form-group" id="filter-by-value">
          	<label class="col-md-3 control-label control-label-filter" id="filter_by_days" for="values_0">' . TEXT_FILTER_BY_DAYS . '</label>
            <label class="col-md-3 control-label control-label-filter" id="filter_by_week" style="display:none" for="values_0">' . TEXT_FILTER_BY_WEEK . '</label>
            <label class="col-md-3 control-label control-label-filter" id="filter_by_month" style="display:none" for="values_0">' . TEXT_FILTER_BY_MONTH . '</label>
            <label class="col-md-3 control-label control-label-filter" id="filter_by_year"  style="display:none" for="values_0">' . TEXT_FILTER_BY_YEAR . '</label>            
            <div class="col-md-9">	
          	  ' . input_tag('values[0]', $values[0], array('class' => 'form-control')) . '
              <div id="filter_by_days_tooltip" class="control-tooltip">' . tooltip_text(TEXT_FILTER_BY_DAYS_TOOLTIP) . '</div>
              <div id="filter_by_week_tooltip" class="control-tooltip" style="display:none">' . tooltip_text(TEXT_FILTER_BY_WEEK_TOOLTIP) . '</div>
              <div id="filter_by_month_tooltip" class="control-tooltip" style="display:none">' . tooltip_text(TEXT_FILTER_BY_MONTH_TOOLTIP) . '</div>
              <div id="filter_by_year_tooltip" class="control-tooltip" style="display:none">' . tooltip_text(TEXT_FILTER_BY_YEAR_TOOLTIP) . '</div>
            </div>			
          </div>  
          
        <div id="filter-by-date-period"> 
          <div class="form-group">
          	<label class="col-md-3 control-label" for="values">' . TEXT_FILTER_BY_DATES . '</label>
            <div class="col-md-9">	
          	  <p class="form-control-static">' . TEXT_FILTER_BY_DATES_TOOLTIP . '</p>              
            </div>			
          </div>
                    
          <div class="form-group">
          	<label class="col-md-3 control-label" for="values">' . TEXT_DATE_FROM . '</label>
            <div class="col-md-9">	
          	  <div class="input-group input-medium date ' . $datepicker . '">' . input_tag('values[1]', $values[1]??'', $attributes) . '<span class="input-group-btn"><button class="btn btn-default date-set" type="button"><i class="fa fa-calendar"></i></button></span></div>              
            </div>			
          </div> 
          
          <div class="form-group">
          	<label class="col-md-3 control-label" for="values">' . TEXT_DATE_TO . '</label>
            <div class="col-md-9">	
          	  <div class="input-group input-medium date ' . $datepicker . '">' . input_tag('values[2]', $values[2]??'', $attributes) . '<span class="input-group-btn"><button class="btn btn-default date-set" type="button"><i class="fa fa-calendar"></i></button></span></div>              
            </div>			
          </div>
         </div> 
        ';

        $html .= '
              
              
    <script>
      function filters_condition_dates_filter()
      {
        $(".control-label-filter").hide();
        $(".control-tooltip").hide();
        $("#filter-by-value").hide();
        $("#filter-by-date-period").hide();
        
        condition = $("#filters_condition_dates_filter").val()
        
        switch(condition)
        {
          case "filter_by_days":
              $("#filter-by-value").show();
              $("#filter-by-date-period").show();
              $("#filter_by_days").show()
              $("#filter_by_days_tooltip").show()
            break;
          case "filter_by_week":
              $("#filter-by-value").show();
              $("#filter_by_week").show()
              $("#filter_by_week_tooltip").show()
            break;  
          case "filter_by_month":
              $("#filter-by-value").show();
              $("#filter_by_month").show()
              $("#filter_by_month_tooltip").show()
            break;
          case "filter_by_year":
              $("#filter-by-value").show();
              $("#filter_by_year").show()
              $("#filter_by_year_tooltip").show()
            break;
        }
      }
      
      $(function() {
      
         $("#filters_condition_dates_filter").change(function(){
            filters_condition_dates_filter()
         })
         
         filters_condition_dates_filter();
         
                                          
         $(".datepicker").click(function(){                 
           $("#values_0").val("")
         })
         
         $("#values_0").click(function(){                 
           $("#values_1").val("")
           $("#values_2").val("")
         })                     
      });
    </script>  
              ';
        break;
}



if(strlen($html) and!$is_internal)
{
    $html .= '
		<div class="form-group">
	  	<label class="col-md-3 control-label" for="save_as_template">' . tooltip_icon(TEXT_SAVE_AS_TEMPLATE_INFO) . TEXT_SAVE_AS_TEMPLATE . '</label>
	    <div class="col-md-9">
	  	  <p class="form-control-static">' . input_checkbox_tag('save_as_template', 1) . '</p>
	    </div>
	  </div>';
}

$html .= '
  <script>
    function check_filters_condition()
    {
      if($("#filters_condition").val()=="empty_value" || $("#filters_condition").val()=="not_empty_value")
      {
        $("#filter-by-values").hide()
      }
      else
      {
        $("#filter-by-values").show()          
      }
    }  
  
    $("#filters_condition").change(function(){      
       check_filters_condition();
    })
    
    check_filters_condition();
        
  </script>
';


echo $html;


exit();
