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

class fieldtype_phone
{
	public $options;

	function __construct()
	{
		$this->options = array('title' => TEXT_FIELDTYPE_PHONE);
	}

	function get_configuration()
	{
		self::prepare_procedure();
		
		$cfg = array();

		$cfg[TEXT_SETTINGS][] = array('title'=>TEXT_ALLOW_SEARCH, 'name'=>'allow_search','type'=>'checkbox','tooltip_icon'=>TEXT_ALLOW_SEARCH_TIP);

		$cfg[TEXT_SETTINGS][] = array('title'=>TEXT_WIDHT,
				'name'=>'width',
				'type'=>'dropdown',
				'choices'=>array('input-small'=>TEXT_INPTUT_SMALL,'input-medium'=>TEXT_INPUT_MEDIUM,'input-large'=>TEXT_INPUT_LARGE,'input-xlarge'=>TEXT_INPUT_XLARGE),
				'tooltip_icon'=>TEXT_ENTER_WIDTH,
				'params'=>array('class'=>'form-control input-medium'));
		 
		$cfg[TEXT_SETTINGS][] = array('title'=>TEXT_INPUT_FIELD_MASK, 'name'=>'mask','type'=>'input','tooltip_icon'=>TEXT_INPUT_FIELD_PHONE_MASK_TIP,'params'=>array('class'=>'form-control'));
		
		$cfg[TEXT_SETTINGS][] = array('title' => TEXT_IS_UNIQUE_FIELD_VALUE, 'name' => 'is_unique', 'type' => 'dropdown', 'choices' => fields_types::get_is_unique_choices(_POST('entities_id')), 'tooltip_icon' => TEXT_IS_UNIQUE_FIELD_VALUE_TIP, 'params' => array('class' => 'form-control input-large'));
		$cfg[TEXT_SETTINGS][] = array('title'=>TEXT_ERROR_MESSAGE, 'name'=>'unique_error_msg','type'=>'input','tooltip_icon'=>TEXT_UNIQUE_FIELD_VALUE_ERROR_MSG_TIP,'tooltip'=>TEXT_DEFAULT . ': ' . TEXT_UNIQUE_FIELD_VALUE_ERROR,'params'=>array('class'=>'form-control input-xlarge'));
                
                $cfg[TEXT_DYNAMIC_MASK][] = array(
                    'title' => TEXT_INPUT_FIELD_MASK, 
                    'name' => 'dynamic_mask', 
                    'type' => 'input', 
                    'tooltip' => TEXT_INPUT_FIELD_MASK_TIP . '<br>' . 
                    TEXT_FIELDTYPE_INPUT_DYNAMIC_MASK_INFO . '<br>' . TEXT_EXAMPLE . ': aa-9{1,4} <br><br>' . 
                    TEXT_FIELDTYPE_INPUT_DYNAMIC_MASK_OPTIONAL_INFO . '<br>' . TEXT_EXAMPLE . ': 999[-999]', 
                    'params' => array('class' => 'form-control'));
		
		if(is_ext_installed())
		{
			$modules = new modules('telephony');
			$choices = $modules->get_active_modules();
				
			$cfg[TEXT_EXT_TELEPHONY_MODULE][] = array('title'=>TEXT_EXT_TELEPHONY_MODULE,'name'=>'telephony_module','type'=>'dropdown','choices'=>[''=>'']+$choices,'tooltip_icon'=>TEXT_EXT_FIELDTYPE_PHONE_TELEPHONY_MODULE_INFO,'params'=>array('class'=>'form-control input-large'));
			
			$modules = new modules('sms');
			$choices = $modules->get_active_modules();
			
			$cfg[TEXT_EXT_TELEPHONY_MODULE][] = array('title'=>TEXT_EXT_SMS_MODULE,'name'=>'sms_module','type'=>'dropdown','choices'=>[''=>'']+$choices,'tooltip_icon'=>TEXT_EXT_FIELDTYPE_PHONE_SMS_MODULE_INFO,'params'=>array('class'=>'form-control input-large'));
			
			$cfg[TEXT_EXT_TELEPHONY_MODULE][] = array('title'=>TEXT_EXT_SHOW_CALL_SMS_HISTORY, 'name'=>'show_history','type'=>'checkbox');
		}

		return $cfg;
	}
	
	static function prepare_procedure()
	{
	
		$sql = "
CREATE FUNCTION  `rukovoditel_regex_replace`(pattern VARCHAR(1000),replacement VARCHAR(1000),original VARCHAR(1000))
RETURNS VARCHAR(1000)
DETERMINISTIC
READS SQL DATA
BEGIN 
 DECLARE temp VARCHAR(1000); 
 DECLARE ch VARCHAR(1); 
 DECLARE i INT;
 SET i = 1;
 SET temp = '';
 IF original REGEXP pattern THEN 
  loop_label: LOOP 
   IF i>CHAR_LENGTH(original) THEN
    LEAVE loop_label;  
   END IF;
   SET ch = SUBSTRING(original,i,1);
   IF NOT ch REGEXP pattern THEN
    SET temp = CONCAT(temp,ch);
   ELSE
    SET temp = CONCAT(temp,replacement);
   END IF;
   SET i=i+1;
  END LOOP;
 ELSE
  SET temp = original;
 END IF;
 RETURN temp;
END";
	
		$is_function = false;
		$check_query = db_query("SHOW FUNCTION STATUS WHERE Db = '" . DB_DATABASE . "'");
		while($check = db_fetch_array($check_query))
		{
			if($check['Name']=='rukovoditel_regex_replace')
			{
				$is_function = true;
			}
		}
	
		if(!$is_function)
		{
			db_query($sql);
		}
	
	}	

	function render($field,$obj,$params = array())
	{
		$cfg =  new fields_types_cfg($field['configuration']);

		$attributes = array('class'=>'form-control ' . $cfg->get('width') .
				' fieldtype_input field_' . $field['id'] .
				($field['is_required']==1 ? ' required':'') .
				($cfg->get('is_unique')>0 ? ' is-unique':''),
		);

		$attributes = fields_types::prepare_uniquer_error_msg_param($attributes,$cfg);

		$script = '';

                if (strlen($cfg->get('dynamic_mask')))
                {            
                    $script = '
                        <script>
                          jQuery(function($){                                    
                             $(".field_' . $field['id'] . '").inputmask({
                                mask: "' . $cfg->get('dynamic_mask') . '",
                                greedy: false,
                                clearIncomplete:true,
                                definitions: {
                                    "я": {
                                      validator: "[А-ЯЁа-яё]"                              
                                    }
                                  }
                            });               
                          });
                        </script>';
                }
                elseif(strlen($cfg->get('mask'))>0)
		{
                    $script = '
                        <script>
                          jQuery(function($){
                             $(".field_' . $field['id'] . '").mask("' . $cfg->get('mask') . '");
                          });
                        </script>
                      ';
		}

		return input_tag('fields[' . $field['id'] . ']',$obj['field_' . $field['id']],$attributes) . $script;
	}

	function process($options)
	{
		return db_prepare_input($options['value']);
	}

	function output($options)
	{		
		//return non-formated value if export
		if(isset($options['is_export']))
		{
			return $options['value'];
		}
		
		$cfg = new fields_types_cfg($options['field']['configuration']);
		
		$phone_number = $options['value'];
		
		if(!strlen($phone_number)) return '';
		
		if(strlen($cfg->get('telephony_module')))
		{
			$module_info_query = db_query("select * from app_ext_modules where id='" . $cfg->get('telephony_module') . "' and type='telephony' and is_active=1");
			if($module_info = db_fetch_array($module_info_query))
			{								
				modules::include_module($module_info,'telephony');
				
				$module = new $module_info['module'];
				$phone_number = $module->prepare_url($module_info['id'],$phone_number, $options);
			}			
		}
		
		if(strlen($cfg->get('sms_module')))
		{
			$module_info_query = db_query("select * from app_ext_modules where id='" . $cfg->get('sms_module') . "' and type='sms'  and is_active=1");
			if($module_info = db_fetch_array($module_info_query))
			{				
				$phone_number .= '&nbsp;&nbsp;<a title="' . TEXT_EXT_SMS . '" href="javascript: open_dialog(\'' . url_for('items/send_sms','path=' . $options['path'] . '&module_id=' . $module_info['id'] . '&field_id=' . $options['field']['id'] . '&item_id=' . $options['item']['id']) . '\')"><i class="fa fa-commenting-o" aria-hidden="true"></i></a>';
			}
		}
		
		if($cfg->get('show_history'))
		{
                    if(has_access_to_call_history())
                    {
                        $phone_number .= '&nbsp;&nbsp;<a title="' . TEXT_EXT_HISTORY . '" href="' . url_for('ext/call_history/view','search=' . preg_replace('/\D/', '',$options['value']) ) . '"><i class="fa fa-history" aria-hidden="true"></i></a>';
                    }
                    else
                    {
                        $phone_number .= '&nbsp;&nbsp;<a title="' . TEXT_EXT_HISTORY . '" href="javascript: open_dialog(\'' . url_for('items/call_history','path=' . $options['path'] . '&phone=' . preg_replace('/\D/', '',$options['value']) ) . '\')"><i class="fa fa-history" aria-hidden="true"></i></a>';
                    }
		}
		
		return $phone_number;
	}
}