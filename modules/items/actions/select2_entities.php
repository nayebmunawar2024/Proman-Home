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

if(!IS_AJAX)
{
    exit();
}

switch($app_module_action)
{
    case 'select_items':

        $entity_info = db_find('app_entities', _get::int('entity_id'));
        
        $parent_item_id = isset($_GET['parent_item_id']) ? _GET('parent_item_id') : false;
        
        $search = isset($_GET['search']) ? $_GET['search']:'';
        $is_tree_view = (isset($_GET['is_tree_view']) and $_GET['is_tree_view']==1 and !strlen($search)) ? true:false;

        $listing_sql_query = 'e.id>0' . ($parent_item_id!==false ? ' and e.parent_item_id=' . $parent_item_id:'') . ($is_tree_view ? " and e.parent_id=0":"");
        $listing_sql_query_order = '';
        $listing_sql_query_join = '';
        $listing_sql_query_having = '';
        $listing_sql_select = '';

        if(isset($_GET['search']))
        {
            $items_search = new items_search($entity_info['id']);
            $items_search->set_search_keywords($_GET['search']);
            
            if(isset($_GET['search_fields']))
            {
                $items_search->set_search_fields(explode(',',$_GET['search_fields']));
            }

            $listing_sql_query .= $items_search->build_search_sql_query();
        }

        //check view assigned only access
        $listing_sql_query = items::add_access_query($entity_info['id'], $listing_sql_query);

        //include access to parent records
        $listing_sql_query .= items::add_access_query_for_parent_entities($entity_info['id']);

        $listing_sql_query_order .= items::add_listing_order_query_by_entity_id($entity_info['id']);


        $results = [];
        $listing_sql = "select  e.* " . $listing_sql_select . " from app_entity_" . $entity_info['id'] . " e " . $listing_sql_query_join . " where " . $listing_sql_query . $listing_sql_query_having . $listing_sql_query_order;

        $listing_split = new split_page($listing_sql, '', 'query_num_rows', 30);
        $items_query = db_query($listing_split->sql_query, false);
        while($item = db_fetch_array($items_query))
        {
            $path_info = items::get_path_info($entity_info['id'], $item['id']);

            //print_r($path_info);

            $parent_name = '';
            if(!$parent_item_id and strlen($path_info['parent_name']) > 0)
            {
                $parent_name = str_replace('<br>', ' / ', $path_info['parent_name']) . ' / ';
            }

            $text = $parent_name . items::get_heading_field($entity_info['id'], $item['id'], $item);

            $results[] = ['id' => $item['id'], 'text' => $text, 'html' => '<div>' . $text . '</div>'];
            
            if($is_tree_view)
            {
                $results = app_select2_nested_items_result($entity_info['id'],$item['id'],$results);
            }
        }

        $response = ['results' => $results];

        if($listing_split->number_of_pages != $_GET['page'] and $listing_split->number_of_pages > 0)
        {
            $response['pagination']['more'] = 'true';
        }

        echo json_encode($response);

        exit();

        break;
}