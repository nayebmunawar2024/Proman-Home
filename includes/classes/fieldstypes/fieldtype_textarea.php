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

class fieldtype_textarea
{

    public $options;

    function __construct()
    {
        $this->options = array('title' => TEXT_FIELDTYPE_TEXTAREA_TITLE);
    }

    function get_configuration()
    {
        $cfg = array();

        $cfg[] = array('title' => TEXT_WIDHT,
            'name' => 'width',
            'type' => 'dropdown',
            'choices' => array('input-small' => TEXT_INPTUT_SMALL, 'input-medium' => TEXT_INPUT_MEDIUM, 'input-large' => TEXT_INPUT_LARGE, 'input-xlarge' => TEXT_INPUT_XLARGE),
            'tooltip' => TEXT_ENTER_WIDTH,
            'params' => array('class' => 'form-control input-medium'));
        
        
        $cfg[] = array(
             'title' => TEXT_MAXLENGTH,
             'name' => 'maxlength',
             'type' => 'input',
             'params' => array('class' => 'form-control input-small', 'type' => 'number'),
             'tooltip_icon' => TEXT_MAXLENGTH_TIP
         );

        $cfg[] = array('title' => TEXT_ALLOW_SEARCH, 'name' => 'allow_search', 'type' => 'checkbox', 'tooltip_icon' => TEXT_ALLOW_SEARCH_TIP);

        $cfg[] = array('title' => TEXT_HIDE_FIELD_IF_EMPTY, 'name' => 'hide_field_if_empty', 'type' => 'checkbox', 'tooltip_icon' => TEXT_HIDE_FIELD_IF_EMPTY_TIP);
        
        $cfg[] = array(
            'title' => TEXT_NUMBER_DISPLAYED_CHARACTERS_IN_LIST, 
            'name' => 'number_characters_in_list', 
            'type' => 'input', 
            'tooltip_icon' => TEXT_NUMBER_DISPLAYED_CHARACTERS_IN_LIST_INFO,
            'params' => array('class' => 'form-control input-small','type'=>'number')
            );

        return $cfg;
    }

    function render($field, $obj, $params = array())
    {
        $cfg = new settings($field['configuration']);

        $attributes = array('rows' => '3',
            'class' => 'form-control ' . $cfg->get('width') . ($field['is_heading'] == 1 ? ' autofocus' : '') . ' fieldtype_textarea field_' . $field['id'] . ($field['is_required'] == 1 ? ' required noSpace' : ''));
        
        if (strlen($cfg->get('maxlength'))) 
        {
            $attributes['maxlength'] = $cfg->get('maxlength');
        }
        
        return textarea_tag('fields[' . $field['id'] . ']', str_replace(array('&lt;', '&gt;'), array('<', '>'), $obj['field_' . $field['id']]??''), $attributes);
    }

    function process($options)
    {
        return str_replace(array('<', '>'), array('&lt;', '&gt;'), $options['value']);
    }

    function output($options)
    {
        $cfg = new fields_types_cfg($options['field']['configuration']);
        
        if(isset($options['is_export']))
        {
            return (!isset($options['is_print']) ? str_replace(array('&lt;', '&gt;'), array('<', '>'), $options['value']) : nl2br($options['value']??''));
        }
        else
        {
            if(isset($options['is_listing']) and $options['is_listing']==1 and $cfg->get('number_characters_in_list')>0 and strlen(strip_tags($options['value']))>$cfg->get('number_characters_in_list'))
            {
                $html = '
                        <div class="truncated-text-block">
                            <div class="truncated-text">' . mb_substr(strip_tags($options['value']),0,$cfg->get('number_characters_in_list')). '... <a href="#" class="truncated-text-expand">' . TEXT_READ_MORE. ' <i class="fa fa-angle-right"></i></a></div>
                            <div class="full-text hidden">' . auto_link_text(nl2br($options['value'])) . ' <a href="#" class="truncated-text-collapse"><i class="fa fa-angle-left"></i> ' . TEXT_HIDE. '</a></div>
                        </div>
                    ';
                
                return $html;
            }
            else
            {
                return auto_link_text(nl2br($options['value']??''));
            }
        }
    }

}
