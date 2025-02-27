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
class users_login_log
{

    static function get_type_choiсes()
    {
        return ['' => '', '0' => TEXT_LOGIN_ATTEMPT, '1' => TEXT_SUCCESSFUL_LOGIN];
    }

    static function success($username, $users_id)
    {
        self::log($username, 1, $users_id);
        
        login_attempt::reset();
    }

    static function fail($username)
    {
        self::log($username);
        
        login_attempt::set();
    }

    static function log($username, $is_success = 0, $users_id = 0)
    {
        $sql_data = [
            'users_id' => $users_id,
            'username' => db_prepare_input($username),
            'identifier' => $_SERVER['REMOTE_ADDR'],
            'is_success' => $is_success,
            'date_added' => time(),
        ];

        db_perform('app_users_login_log', $sql_data);

        if($is_success and $users_id > 0)
        {
            self::set_user_last_login_date($users_id);
        }
    }

    static function set_user_last_login_date($users_id)
    {
        //prepare fieldtype_user_last_login_date
        $fields_query = db_query("select id, entities_id from app_fields where type in ('fieldtype_user_last_login_date') and  entities_id=1");
        if(!$fields = db_fetch_array($fields_query))
        {
            $sql_data = [
                'type' => 'fieldtype_user_last_login_date',
                'entities_id' => 1,
                'forms_tabs_id' => 1,
            ];

            db_perform('app_fields', $sql_data);

            $field_id = db_insert_id();

            db_query("ALTER TABLE app_entity_1 ADD field_{$field_id} INT NOT NULL;");
        }
        else
        {
            $field_id = $fields['id'];
        }

        //update
        db_query("update app_entity_1 set field_{$field_id}=" . time() . " where id={$users_id}");
    }

    static function delete_by_user_id($users_id)
    {
        db_query("delete from app_users_login_log where users_id='" . $users_id . "'");
    }
}
