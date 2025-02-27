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

class fieldtype_dropdown
{
  public $options;
  
  function __construct()
  {
    $this->options = array('title' => TEXT_FIELDTYPE_DROPDOWN_TITLE,'has_choices'=>true);
  }
  
  function get_configuration()
  {
    $cfg = array();
    
    $cfg[TEXT_SETTINGS][] = array('title'=>TEXT_NOTIFY_WHEN_CHANGED, 'name'=>'notify_when_changed','type'=>'checkbox','tooltip_icon'=>TEXT_NOTIFY_WHEN_CHANGED_TIP);
    
    $cfg[TEXT_SETTINGS][] = array('title'=>TEXT_DEFAULT_TEXT, 
                   'name'=>'default_text',
                   'type'=>'input',                   
                   'tooltip_icon'=>TEXT_DEFAULT_TEXT_INFO,
                   'params'=>array('class'=>'form-control input-medium'));
    
    $cfg[TEXT_SETTINGS][] = array('title'=>TEXT_WIDHT, 
                   'name'=>'width',
                   'type'=>'dropdown',
                   'choices'=>array('input-small'=>TEXT_INPTUT_SMALL,'input-medium'=>TEXT_INPUT_MEDIUM,'input-large'=>TEXT_INPUT_LARGE,'input-xlarge'=>TEXT_INPUT_XLARGE),
                   'tooltip_icon'=>TEXT_ENTER_WIDTH,
                   'params'=>array('class'=>'form-control input-medium'));
    
    $cfg[TEXT_SETTINGS][] = array('title'=>TEXT_HIDE_FIELD_IF_EMPTY, 'name'=>'hide_field_if_empty','type'=>'checkbox','tooltip_icon'=>TEXT_HIDE_FIELD_IF_EMPTY_TIP);
    
    $cfg[TEXT_SETTINGS][] = array('title'=>TEXT_USE_SEARCH, 
                   'name'=>'use_search',
                   'type'=>'dropdown',
                   'choices'=>array('0'=>TEXT_NO,'1'=>TEXT_YES),
                   'tooltip'=>TEXT_USE_SEARCH_INFO,
                   'params'=>array('class'=>'form-control input-medium'));       
                
    //cfg global list if exist
    if(count($choices = global_lists::get_lists_choices())>0)
    {              
      $cfg[TEXT_SETTINGS][] = array('title'=>TEXT_USE_GLOBAL_LIST, 
                     'name'=>'use_global_list',
                     'type'=>'dropdown',
                     'choices'=>$choices,
                     'tooltip'=>TEXT_USE_GLOBAL_LIST_TOOLTIP,
                     'params'=>array('class'=>'form-control input-medium'));
    }  
    
    
    $cfg[TEXT_VALUE][] = array('title'=>TEXT_DISPLAY_CHOICES_VALUES, 'name'=>'display_choices_values','type'=>'checkbox','tooltip_icon'=>TEXT_DISPLAY_CHOICES_VALUES_TIP);
    
    $cfg[TEXT_VALUE][] = array(
        'title'=>TEXT_DISPLAY_PARENT_NAME, 
        'name'=>'display_parent_name',
        'type'=>'dropdown',
        'choices'=>[0=>TEXT_NO,1=>TEXT_YES],
        'params'=>array('class'=>'form-control input-small'));
    
    $cfg[TEXT_VALUE][] = array(
        'title' => TEXT_SEPARATOR,
        'name' => 'parent_name_separator',
        'type' => 'input',
        'default' =>':',            
        'params' => array('class' => 'form-control input-medium'),
        'form_group'=>['form_display_rules'=>'fields_configuration_display_parent_name:1']
        );

        return $cfg;
  }  
  
  function render($field,$obj,$params = array())
  {
    $cfg = new fields_types_cfg($field['configuration']);
            
    $attributes = array('class'=>'form-control ' . $cfg->get('width') . ' field_' . $field['id'] . ($field['is_required']==1 ? ' required':'') . ($cfg->get('use_search')==1 ? ' chosen-select2':''),
                        'data-placeholder'=>TEXT_SELECT_SOME_VALUES);
    
//use global lists if exsit    
    if($cfg->get('use_global_list')>0)
    {
      $choices = global_lists::get_choices_with_icons($cfg->get('use_global_list'),(($field['is_required']==0 or strlen($cfg->get('default_text'))>0) ? true:false), $cfg->get('default_text'),$obj['field_' . $field['id']],true,$cfg->get('display_choices_values'));
      $default_id = global_lists::get_choices_default_id($cfg->get('use_global_list'));
    }
    else
    {                    
      $choices = fields_choices::get_choices_with_icons($field['id'],(($field['is_required']==0 or strlen($cfg->get('default_text'))>0) ? true:false), $cfg->get('default_text'), $cfg->get('display_choices_values'),$obj['field_' . $field['id']],true);
      $default_id = fields_choices::get_default_id($field['id']); 
    }
    
    //set allowed choices if isset
    $choices = fields_choices::set_allowed_choices($choices,$params['allowed_choices']??'');
    
    
    $value = ($obj['field_' . $field['id']]>0 ? $obj['field_' . $field['id']] : (($params['form']??'')=='comment' ? '':$default_id)); 
    
    return '<div>' . select_tag('fields[' . $field['id'] . ']',$choices,$value,$attributes) . '</div>' . fields_types::custom_error_handler($field['id']);
  }
  
  function process($options)
  {
    global $app_changed_fields, $app_choices_cache, $app_global_choices_cache;
    
    if(!$options['is_new_item'])
    {
      $cfg = new fields_types_cfg($options['field']['configuration']);
      
      if($options['value']>0 and $options['value']!=$options['current_field_value'] and $cfg->get('notify_when_changed')==1)
      {      	      		
      	$app_changed_fields[] = array(
      			'name'=>$options['field']['name'],
      			'value'=>($cfg->get('use_global_list')>0 ? $app_global_choices_cache[$options['value']]['name'] : $app_choices_cache[$options['value']]['name']),
      			'fields_id'=>$options['field']['id'],
      			'fields_value'=>$options['value'],
      	);      	
      }
    }
  
    return $options['value'];
  }
  
  function output($options)
  {    
    $cfg = new fields_types_cfg($options['field']['configuration']);
    
    if($cfg->get('display_parent_name')==1)
    {
        //render global list value
        if($cfg->get('use_global_list')>0)
        {
          return global_lists::render_value_with_parents($options['value'],false,$cfg->get('parent_name_separator'));
        }
        else
        {
          return fields_choices::render_value_with_parents($options['value'],false,$cfg->get('parent_name_separator'));
        }
    }
    else
    {
        //render global list value
        if($cfg->get('use_global_list')>0)
        {
          return global_lists::render_value($options['value']);
        }
        else
        {
          return fields_choices::render_value($options['value']);
        }
    }
  }  
  
  function reports_query($options)
  {
    $filters = $options['filters'];
    $sql_query = $options['sql_query'];
    
    $prefix = (strlen($options['prefix']) ? $options['prefix'] : 'e');
  
    $sql_query[] = $prefix . '.field_' . $filters['fields_id'] .  ($filters['filters_condition']=='include' ? ' in ': ' not in ') .'(' . $filters['filters_values'] . ') ';
    
    return $sql_query;
  }
}