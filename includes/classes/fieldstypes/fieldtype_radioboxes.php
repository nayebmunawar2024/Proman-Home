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

class fieldtype_radioboxes
{
  public $options;
  
  function __construct()
  {
    $this->options = array('title' => TEXT_FIELDTYPE_RADIOBOXES_TITLE,'has_choices'=>true);
  }
  
  function get_configuration()
  {
    $cfg = array();
    
    $cfg[] = array('title'=>TEXT_DISPLAY_AS, 'name'=>'display_as','type'=>'dropdown','choices'=>self::get_display_as_choices(),'default'=>'list-column-1','params'=>['class'=>'form-control input-medium']);
    
    $cfg[] = array('title'=>TEXT_HIDE_FIELD_IF_EMPTY, 'name'=>'hide_field_if_empty','type'=>'checkbox','tooltip_icon'=>TEXT_HIDE_FIELD_IF_EMPTY_TIP);
    
    $cfg[] = array('title'=>TEXT_DISPLAY_CHOICES_VALUES, 'name'=>'display_choices_values','type'=>'checkbox','tooltip_icon'=>TEXT_DISPLAY_CHOICES_VALUES_TIP);
    
//cfg global list if exist
    if(count($choices = global_lists::get_lists_choices())>0)
    {              
      $cfg[] = array('title'=>TEXT_USE_GLOBAL_LIST, 
                     'name'=>'use_global_list',
                     'type'=>'dropdown',
                     'choices'=>$choices,
                     'tooltip'=>TEXT_USE_GLOBAL_LIST_TOOLTIP,
                     'params'=>array('class'=>'form-control input-medium'));
    }    
            
    return $cfg;
  }  
  
  static function get_display_as_choices()
  {
      $choices = [
          'list-inline'=>TEXT_INLINE_LIST,
          'list-column-1'=>TEXT_COLUMN . ' 1',
          'list-column-2'=>TEXT_COLUMN . ' 2',
          'list-column-3'=>TEXT_COLUMN . ' 3',
          'list-column-4'=>TEXT_COLUMN . ' 4',
      ];
      
      return $choices;
  }
  
  function render($field,$obj,$params = array())
  {         
    $cfg = new fields_types_cfg($field['configuration']);
           
    $attributes = array('class'=>'field_' . $field['id'] . ($field['is_required']==1 ? ' required':''),'data-raido-list'=>$field['id']);
               
//use global lists if exsit       
    if($cfg->get('use_global_list')>0)
    {
      $choices = global_lists::get_choices_with_icons($cfg->get('use_global_list'),false,'',$obj['field_' . $field['id']],true);
      $default_id = global_lists::get_choices_default_id($cfg->get('use_global_list'));
    }
    else
    {                         
      $choices = fields_choices::get_choices_with_icons($field['id'],false,'',$cfg->get('display_choices_values'),$obj['field_' . $field['id']],true);
      $default_id = fields_choices::get_default_id($field['id']);
    }
    
    //set allowed choices if isset
    $choices = fields_choices::set_allowed_choices($choices,$params['allowed_choices']??'');
    
    $value = ($obj['field_' . $field['id']]>0 ? $obj['field_' . $field['id']] : $default_id); 
    
    
    if($cfg->get('display_as')=='' or $cfg->get('display_as')=='list-column-1')
    {
        return '<div class="radio-list radio-list-' . $field['id'] . '">' . select_radioboxes_tag('fields[' . $field['id'] . ']',$choices,$value,$attributes) . '</div>';
    }
    else
    {
        $attributes['ul-class'] = $cfg->get('display_as');
        return '<div class="radio-list radio-list-' . $field['id'] . ($attributes['ul-class']=='list-inline' ? ' form-control-static':'') . '">' . select_radioboxes_ul_tag('fields[' . $field['id'] . ']',$choices,$value,$attributes) . '</div>';
    }
  }
  
  function process($options)
  {            
    return $options['value'];
  }
  
  function output($options)
  {
    $cfg = new fields_types_cfg($options['field']['configuration']);
    
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
  
  function reports_query($options)
  {
    $filters = $options['filters'];
    $sql_query = $options['sql_query'];
    
    $prefix = (strlen($options['prefix']) ? $options['prefix'] : 'e');
  
    $sql_query[] = $prefix . '.field_' . $filters['fields_id'] .  ($filters['filters_condition']=='include' ? ' in ': ' not in ') .'(' . $filters['filters_values'] . ') ';
    
    return $sql_query;
  }  
}