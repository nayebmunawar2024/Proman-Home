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

//checking access
if(isset($_GET['id']) and !users::has_access('update'))
{            
  echo ajax_modal_template_header(TEXT_WARNING) . '<div class="modal-body">' . TEXT_NO_ACCESS . '</div>' . ajax_modal_template_footer_simple();
  exit();
}
elseif(!isset($_GET['id']) and (!users::has_access('create') or !access_rules::has_add_buttons_access($current_entity_id,$parent_entity_item_id)))
{    
  echo ajax_modal_template_header(TEXT_WARNING) . '<div class="modal-body">' . TEXT_NO_ACCESS . '</div>' . ajax_modal_template_footer_simple();
  exit();
}

$obj = array();

if(isset($_GET['id']))
{
    $obj = db_find('app_entity_' . $current_entity_id,(int)$_GET['id']);  
  
    if(users::has_access('update_creator') and $obj['created_by'] != $app_user['id'])
    {
        echo ajax_modal_template_header(TEXT_WARNING) . '<div class="modal-body">' . TEXT_NO_ACCESS . '</div>' . ajax_modal_template_footer_simple();
        exit();
    }
}
else
{
  $obj = db_show_columns('app_entity_' . $current_entity_id);

//prepare start/end dates if add item from calendar report
  if(strstr($app_redirect_to,'calendarreport'))
  {
    require(component_path('items/items_form_calendar_report_prepare'));
  }
  
//prepare start/end dates if add item from pivot calendar report
  if(strstr($app_redirect_to,'pivot_calendars'))
  {
  	require(component_path('items/items_form_pivot_calendar_report_prepare'));
  }  
  
  //prepare start/end dates if add item from resource timeline report
  if(strstr($app_redirect_to,'resource_timeline'))
  {
  	require(component_path('ext/resource_timeline/items_form_prepare'));
  } 
  
//prepare start/end dates if add item from gantt report
  if(strstr($app_redirect_to,'ganttreport'))
  {
  	require(component_path('items/items_form_gantt_report_prepare'));
  }  
  
//auto fill related fields to mail
  if(isset($_GET['mail_groups_id']))
  {
  	require(component_path('ext/mail/auto_fill_fields'));
  }
  
  //prepare subentity form
    if(strstr($app_redirect_to,'subentity_form'))
    {
        require(component_path('items/subentity_form_prepare'));
    }
}

$entity_cfg = new entities_cfg($current_entity_id);

//check if form blocked
if(isset($_GET['id']))
{
    blocked_forms::validate($current_entity_id, _GET('id'));
}