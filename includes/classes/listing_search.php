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
class listing_search
{
    public $search_settings;

    function __construct($reports_id)
    {
        global $app_user;

        $this->search_settings = array();

        $settings_query = db_query("select * from app_users_search_settings where reports_id='" . $reports_id . "' and users_id = '" . $app_user['id'] . "'");
        while($settings = db_fetch_array($settings_query))
        {
            $this->search_settings[$settings['configuration_name']] = $settings['configuration_value'];
        }

        //print_r($this->search_settings);
    }

    function get($name)
    {
        $value = '';

        if(!isset($this->search_settings[$name]))
        {
            $this->search_settings[$name] = '';
        }

        switch($name)
        {
            case 'search_keywords':
                $value = stripslashes($this->search_settings['search_keywords']);
                break;
            case 'use_search_fields':
                $value = explode(',', $this->search_settings['use_search_fields']);
                break;
            case 'search_in_comments':
            case 'search_in_all':
            case 'search_type_and':
            case 'search_type_match':
                $value = $this->search_settings[$name];
                break;
        }

        return $value;
    }

    function get_input_attributes($name)
    {
        $attributes = array();

        if($this->get($name) == 'true')
        {
            $attributes = array('checked' => 'checked');
        }

        return $attributes;
    }

    public static function save($reports_id)
    {
        global $app_user;

        $settings = array(
            'search_keywords' => addslashes($_POST['search_keywords']),
            'use_search_fields' => $_POST['use_search_fields'],
            'search_in_comments' => isset($_POST['search_in_comments']) ? $_POST['search_in_comments'] : 0,
            'search_in_all' => $_POST['search_in_all'],
            'search_type_and' => $_POST['search_type_and'],
            'search_type_match' => $_POST['search_type_match'],
        );

        $sql_data = array();

        foreach($settings as $k => $v)
        {
            $sql_data[] = array(
                'configuration_name' => $k,
                'configuration_value' => $v,
                'users_id' => $app_user['id'],
                'reports_id' => $reports_id,
            );
        }

        //reset settings before insert
        db_query("delete from app_users_search_settings where users_id='" . $app_user['id'] . "' and reports_id='" . $reports_id . "'");

        //sert new settings
        db_batch_insert('app_users_search_settings', $sql_data);
    }
    
    static function reset(int $reports_id)
    {
        global $app_user;
                
        db_query("update app_users_search_settings set configuration_value='' where configuration_name='search_keywords' and users_id='" . $app_user['id'] . "' and reports_id='" . $reports_id . "' ");                
    }
}
