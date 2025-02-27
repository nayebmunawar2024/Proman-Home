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

switch($app_module_action)
{
    case 'sort':
        if(isset($_POST['sort_items']))
        {
            $sort_order = 0;
            foreach(explode(',', $_POST['sort_items']) as $v)
            {
                db_query("update app_access_groups set sort_order='" . $sort_order . "' where id='" . str_replace('item_', '', $v) . "'");

                $sort_order++;
            }
        }
        exit();
        break;
    case 'save':
        $sql_data = array(
            'name' => $_POST['name'],
            'sort_order' => $_POST['sort_order'],
            'is_default' => (isset($_POST['is_default']) ? $_POST['is_default'] : 0),
            'is_ldap_default' => (isset($_POST['is_ldap_default']) ? $_POST['is_ldap_default'] : 0),
            'ldap_filter' => $_POST['ldap_filter'],
            'notes' => $_POST['notes'],
        );

        if(isset($_POST['is_default']))
        {
            db_query("update app_access_groups set is_default = 0");
        }

        if(isset($_POST['is_ldap_default']))
        {
            db_query("update app_access_groups set is_ldap_default = 0");
        }

        if(isset($_GET['id']))
        {
            db_perform('app_access_groups', $sql_data, 'update', "id='" . db_input($_GET['id']) . "'");
        }
        else
        {
            db_perform('app_access_groups', $sql_data);
        }

        redirect_to('users_groups/users_groups');
        break;
    case 'delete':
        if(isset($_GET['id']))
        {
            $msg = access_groups::check_before_delete($_GET['id']);

            if(strlen($msg) > 0)
            {
                $alerts->add($msg, 'error');
            }
            else
            {
                $name = access_groups::get_name_by_id($_GET['id']);

                db_delete_row('app_access_groups', $_GET['id']);
                db_delete_row('app_entities_access', $_GET['id'], 'access_groups_id');
                db_delete_row('app_fields_access', $_GET['id'], 'access_groups_id');
                db_delete_row('app_comments_access', $_GET['id'], 'access_groups_id');
                               
                $alerts->add(sprintf(TEXT_WARN_DELETE_SUCCESS, $name), 'success');
            }

            redirect_to('users_groups/users_groups');
        }
        break;
}