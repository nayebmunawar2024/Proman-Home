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

$rules_query = db_query("select * from app_ext_sms_rules where id='" . str_replace('sms_rules','',$app_redirect_to) . "'");
$rules = db_fetch_array($rules_query);

$breadcrumb = array();

$breadcrumb[] = '<li>' . link_to(TEXT_EXT_SMS_SENDIGN_RULES,url_for('ext/modules/sms_rules', 'entities_id=' . $rules['entities_id'])) . '<i class="fa fa-angle-right"></i></li>';

$breadcrumb[] = '<li>' . $entity_info['name'] . '<i class="fa fa-angle-right"></i></li>';

$breadcrumb[] = '<li>' . strip_tags($rules['description']) . '<i class="fa fa-angle-right"></i></li>';

$breadcrumb[] = '<li>' . TEXT_FILTERS . '</li>';

$page_description = TEXT_SET_MSG_FILTERS;

