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

class global_lists
{

    static function check_before_delete($id)
    {
        global $app_fields_cache, $app_entities_cache;
        
        $check_list = [];
        
        foreach($app_fields_cache as $entity_id=>$v)
        {
            foreach($v as $field)
            {
                $cfg = new settings($field['configuration']);
                
                if($cfg->get('use_global_list')==$id)
                {
                    $check_list[] = '<li>' . link_to($app_entities_cache[$entity_id]['name'] . ': ' . $field['name'] . ' (#' . $field['id'] . ')',url_for('entities/fields', 'entities_id=' . $entity_id)) . '</li>';
                }
            }
        }
        
        $msg = '';
        if(count($check_list))
        {
            $msg = '<b>' . TEXT_CANT_DELTE_ITEM . '</b><br>' . TEXT_LIST_USED_WARN . '<ul>' . implode('', $check_list) . '</ul>';
        }
        
        return $msg;
    }

    public static function check_before_delete_choices($id)
    {
        return '';
    }

    static function get_lists_choices($add_empty = true)
    {
        $choices = array();

        if($add_empty)
        {
            $choices[''] = '';
        }

        $groups_query = db_fetch_all('app_global_lists', '', 'name');
        while($v = db_fetch_array($groups_query))
        {
            $choices[$v['id']] = $v['name'];
        }

        return $choices;
    }

    static function get_name_by_id($id)
    {
        $item = db_find('app_global_lists', $id);

        return $item['name'];
    }

    static function get_choices_name_by_id($id)
    {
        $item = db_find('app_global_lists_choices', $id);

        return $item['name'];
    }

    public static function get_choices_default_id($lists_id)
    {
        $obj_query = db_query("select * from app_global_lists_choices where lists_id = '" . db_input($lists_id) . "' and is_default=1 limit 1");

        if($obj = db_fetch_array($obj_query))
        {
            return $obj['id'];
        }
        else
        {
            return 0;
        }
    }

    static function get_choices_tree($lists_id, $parent_id = 0, $tree = array(), $level = 0, $selected_values = '', $check_status = false, $display_choices_values = false)
    {
        $where_sql = '';

        if($check_status)
        {
            $where_sql = " and (is_active=1 " . (strlen($selected_values) ? " or id in (" . implode(',', array_map(function($v)
                            {
                                return (int) $v;
                            }, explode(',', $selected_values))) . ")" : '') . ") ";
        }

        $choices_query = db_query("select * from app_global_lists_choices where lists_id = '" . db_input($lists_id) . "' and parent_id='" . db_input($parent_id) . "' {$where_sql} order by sort_order, name");

        while($v = db_fetch_array($choices_query))
        {
            if($display_choices_values == 1)
            {
                $v['name'] = $v['name'] . (strlen($v['value']) ? ' (' . ($v['value'] >= 0 ? '+' : '') . $v['value'] . ')' : '');
            }
            
            $tree[] = array_merge($v, array('level' => $level));

            $tree = self::get_choices_tree($lists_id, $v['id'], $tree, $level + 1, $selected_values, $check_status);
        }

        return $tree;
    }

    public static function get_js_level_tree($lists_id, $parent_id = 0, $tree = array(), $level = 0, $selected_values = '')
    {
        $choices_query = db_query("select * from app_global_lists_choices where lists_id = '" . db_input($lists_id) . "' and parent_id='" . db_input($parent_id) . "' and (is_active=1 " . (strlen($selected_values) ? " or id in (" . implode(',', array_map(function($v)
                        {
                            return (int) $v;
                        }, explode(',', $selected_values))) . ")" : '') . ") order by sort_order, name");


        while($v = db_fetch_array($choices_query))
        {
            if($parent_id > 0)
            {
                $tree[$parent_id][] = '
  					$(update_field).append($("<option>", {value: ' . $v['id'] . ',text: "' . addslashes(strip_tags($v['name'])) . '"}));';
            }

            $tree = self::get_js_level_tree($lists_id, $v['id'], $tree, $level + 1, $selected_values);
        }

        return $tree;
    }

    static function get_choices_html_tree($lists_id, $parent_id = 0, $tree = '')
    {
        $count_query = db_query("select count(*) as total from app_global_lists_choices where lists_id = '" . db_input($lists_id) . "' and parent_id='" . db_input($parent_id) . "' order by sort_order, name");
        $count = db_fetch_array($count_query);

        if($count['total'] > 0)
        {
            $tree .= '<ol class="dd-list">';

            $choices_query = db_query("select * from app_global_lists_choices where lists_id = '" . db_input($lists_id) . "' and parent_id='" . db_input($parent_id) . "' order by sort_order, name");

            while($v = db_fetch_array($choices_query))
            {
                $tree .= '<li class="dd-item" data-id="' . $v['id'] . '"><div class="dd-handle">' . app_render_icon($v['icon']) . ' ' . $v['name'] . '</div>';

                $tree = self::get_choices_html_tree($lists_id, $v['id'], $tree);

                $tree .= '</li>';
            }

            $tree .= '</ol>';
        }

        return $tree;
    }

    public static function get_choices($lists_id, $add_empty = true, $empty_text = '', $selected_values = '', $check_status = false, $display_choices_values = false)
    {
        $choices = array();

        $tree = self::get_choices_tree($lists_id, 0, [], 0, $selected_values, $check_status, $display_choices_values);

        if(count($tree) > 0)
        {
            if($add_empty)
            {
                $choices[''] = $empty_text;
            }

            foreach($tree as $v)
            {                
                $choices[$v['id']] = str_repeat(' - ', $v['level']) . $v['name'];                                    
            }
        }

        return $choices;
    }
    
    public static function get_choices_with_icons($lists_id, $add_empty = true, $empty_text = '', $selected_values = '', $check_status = false, $display_choices_values = false)
    {
        $choices = array();

        $tree = self::get_choices_tree($lists_id, 0, [], 0, $selected_values, $check_status, $display_choices_values);

        if(count($tree) > 0)
        {
            if($add_empty)
            {
                $choices[''] = $empty_text;
            }

            foreach($tree as $v)
            {
                if(strlen($v['icon']??''))
                {
                    $choices[$v['id']] = [
                        'name' => str_repeat(' - ', $v['level']) . $v['name'],
                        'attr' => [
                            'data-icon'=>trim($v['icon'])
                            ]
                        ];
                }
                else
                {
                    $choices[$v['id']] = str_repeat(' - ', $v['level']) . $v['name'];                    
                }
            }
        }

        return $choices;
    }

    public static function get_choices_with_color($lists_id, $add_empty = true, $empty_text = '', $selected_values = '', $check_status = false)
    {
        $choices = array();

        $tree = self::get_choices_tree($lists_id, 0, [], 0, $selected_values, $check_status);

        if(count($tree) > 0)
        {
            if($add_empty)
            {
                $choices[''] = $empty_text;
            }

            foreach($tree as $v)
            {
                $choices[$v['id']] = ['name' => str_repeat(' - ', $v['level']) . $v['name'], 'color' => $v['bg_color']];
            }
        }

        return $choices;
    }

    static function choices_sort_tree($lists_id, $tree, $parent_id = 0)
    {
        $sort_order = 0;
        foreach($tree as $v)
        {
            db_query("update app_global_lists_choices set parent_id='" . $parent_id . "', sort_order='" . $sort_order . "' where id='" . db_input($v['id']) . "' and lists_id='" . db_input($lists_id) . "'");

            if(isset($v['children']))
            {
                self::choices_sort_tree($lists_id, $v['children'], $v['id']);
            }

            $sort_order++;
        }
    }

    public static function get_cache()
    {
        $list = array();

        $choices_query = db_query("select * from app_global_lists_choices");

        while($v = db_fetch_array($choices_query))
        {
            $list[$v['id']] = $v;
        }

        return $list;
    }

    public static function render_value($values = array(), $is_export = false)
    {
        global $app_global_choices_cache;

        if(!is_array($values))
        {
            $values = explode(',', $values);
        }

        $html = '';
        foreach($values as $id)
        {
            if(isset($app_global_choices_cache[$id]))
            {
                if($is_export)
                {
                    $html .= (strlen($html) == 0 ? $app_global_choices_cache[$id]['name'] : ', ' . $app_global_choices_cache[$id]['name']);
                }
                elseif(strlen($app_global_choices_cache[$id]['bg_color']??'') > 0)
                {
                    $html .= render_bg_color_block($app_global_choices_cache[$id]['bg_color'], self::render_icon($app_global_choices_cache[$id]['icon']) . $app_global_choices_cache[$id]['name']);
                }
                else
                {
                    $html .= '<div>' . self::render_icon($app_global_choices_cache[$id]['icon']) . $app_global_choices_cache[$id]['name'] . '</div>';
                }
            }
        }

        return $html;
    }
    
    static function render_icon($icon)
    {
        return strlen($icon)>0 ? app_render_icon($icon) . ' ' : '';
    }

    public static function render_value_with_parents($values = array(), $is_export = false,$separator='')
    {
        global $app_global_choices_cache;

        if(!is_array($values))
        {
            $values = explode(',', $values);
        }

        $html = '';
        foreach($values as $id)
        {
            if(!isset($app_global_choices_cache[$id])) continue;
            
            $name = self::render_icon($app_global_choices_cache[$id]['icon']) . self::get_parents_names($app_global_choices_cache[$id]['parent_id'], $separator) . $app_global_choices_cache[$id]['name'];

            if(isset($app_global_choices_cache[$id]))
            {
                if($is_export)
                {
                    $html .= (strlen($html) == 0 ? $name : ', ' . $name);
                }
                elseif(strlen($app_global_choices_cache[$id]['bg_color']) > 0)
                {
                    $html .= render_bg_color_block($app_global_choices_cache[$id]['bg_color'], $name);
                }
                else
                {
                    $html .= '<div>' . $name . '</div>';
                }
            }
        }

        return $html;
    }

    public static function get_paretn_ids($id, $parents = array())
    {
        $choices_query = db_query("select * from app_global_lists_choices where id = '" . db_input($id) . "' order by sort_order, name");

        while($v = db_fetch_array($choices_query))
        {
            $parents[] = $v['id'];

            if($v['parent_id'] > 0)
            {
                $parents = self::get_paretn_ids($v['parent_id'], $parents);
            }
        }

        return $parents;
    }
    
    static function get_parents_names($parent_id, $separator = '')
    {
        global $app_global_choices_cache;

        $parents = [];
        foreach(self::get_paretn_ids($parent_id) as $id)
        {
            $parents[] = $app_global_choices_cache[$id]['name'];
        }
        
        return count($parents) ? implode($separator, $parents) . $separator : '';
    }
    
    static function has_nested($id)
    {
        if(!$id) return false;
        
        $check_query = db_query("select id from app_global_lists_choices where parent_id={$id} and is_active=1 limit 1");
        if($check = db_fetch_array($check_query))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

}
