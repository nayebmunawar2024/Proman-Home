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
?>

<?php
$cfg = new fields_types_cfg($app_fields_cache[$current_entity_id][_get::int('fields_id')]['configuration']);


$content = (strlen($cfg->get('signature_description')) ? '<p>' . $cfg->get('signature_description') . '</p>' : '');
$content .= fieldtype_signature::render_previous_signature($cfg,_get::int('fields_id'));
$content .= '<iframe width="100%" height="400" scrolling="no" frameborder="no" src="' . url_for('items/signature_field_layout','fields_id=' . _get::int('fields_id') . '&path=' . $app_path . '&redirect_to=' . $app_redirect_to). '"></iframe>';

$heading = (strlen($cfg->get('button_title')) ? $cfg->get('button_title') : TEXT_APPROVE);

$button_title = 'hide-save-button';


echo ajax_modal_template_header($heading) ?>

<?php echo form_tag('approve_form', url_for('items/signature_field','action=approve&fields_id=' . _get::int('fields_id') . '&path=' . $app_path)) ?>
<?php echo input_hidden_tag('redirect_to',$app_redirect_to )?>
<?php if(isset($_GET['gotopage'])) echo input_hidden_tag('gotopage[' . key($_GET['gotopage']). ']',current($_GET['gotopage'])) ?>
    
<div class="modal-body">    
<?php echo $content ?>
</div>
 
<?php echo ajax_modal_template_footer($button_title) ?>

</form>   

<script>
 $('#approve_form').validate({
	 submitHandler: function(form){
			app_prepare_modal_action_loading(form)
			return true;
		}
	});
 
 
</script> 