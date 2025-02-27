<?php

/* 
 *  Этот файл является частью программы "CRM Руководитель" - конструктор CRM систем для бизнеса
 *  https://www.rukovoditel.net.ru/
 *  
 *  CRM Руководитель - это свободное программное обеспечение, 
 *  распространяемое на условиях GNU GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 *  
 *  Автор и правообладатель программы: Харчишина Ольга Александровна (RU), Харчишин Сергей Васильевич (RU).
 *  Государственная регистрация программы для ЭВМ: 2023664624
 *  https://fips.ru/EGD/3b18c104-1db7-4f2d-83fb-2d38e1474ca3
 */

$field_id = _GET('field_id');

if($_GET['field_id']=='attachments')
{
    $entity_cfg = new entities_cfg($current_entity_id);
    if($entity_cfg->get('comments_allow_audio_recording',0)==0)
    {
        redirect_to_404();
    }
    
    $field_id = 'attachments';
}
elseif(!isset_field($current_entity_id, $field_id))
{
    redirect_to_404();
}

switch($app_module_action)
{
    case 'upload':
        
        $verifyToken = md5($app_user['id'] . _GET('timestamp'));
        
        audiorecorder::upload($field_id, $verifyToken);
        
        exit();
        break;
}

