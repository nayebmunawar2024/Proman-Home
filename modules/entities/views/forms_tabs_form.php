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

<?php echo ajax_modal_template_header((isset($_GET['id']) ? TEXT_HEADING_EDIT_FORM_TAB : TEXT_HEADING_NEW_FORM_TAB)) ?>

<?php echo form_tag('forms_form', url_for('entities/forms', 'action=save_tab' . (isset($_GET['id']) ? '&id=' . $_GET['id'] : '')), array('class' => 'form-horizontal')) ?>
<div class="modal-body">
    <div class="form-body">

        <?php echo input_hidden_tag('entities_id', $_GET['entities_id']) ?>

        <div class="form-group">
            <label class="col-md-3 control-label" for="name"><?php echo TEXT_NAME ?></label>
            <div class="col-md-9">	
                <?php echo input_tag('name', $obj['name'], array('class' => 'form-control input-large required autofocus')) ?>      
            </div>			
        </div>

        <div class="form-group">
            <label class="col-md-3 control-label" for="cfg_menu_title"><?php echo TEXT_ICON; ?></label>
            <div class="col-md-9">	
                <?php echo input_icon_tag('icon', $obj['icon'], array('class' => 'form-control input-large')); ?>                    
            </div>			
        </div>
        
        <div class="form-group">
            <label class="col-md-3 control-label"><?php echo TEXT_ICON_COLOR ?></label>
            <div class="col-md-9">
                <?php echo input_color('icon_color',$obj['icon_color']) ?>   
            </div> 
        </div>

        <div class="form-group">
            <label class="col-md-3 control-label" for="name"><?php echo TEXT_DESCRIPTION ?></label>
            <div class="col-md-9">	
                <?php echo textarea_tag('description', $obj['description'], array('class' => 'editor')) ?>      
            </div>			
        </div>

    </div>
</div> 

<?php echo ajax_modal_template_footer() ?>

</form> 

<script>
    $(function ()
    {
        $('#forms_form').validate({
            submitHandler: function (form)
            {
                app_prepare_modal_action_loading(form)
                return true;
            }
        });
    });

</script>   


