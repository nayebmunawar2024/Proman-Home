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

<?php echo ajax_modal_template_header(TEXT_HEADING_VALUE_IFNO) ?>

<?php echo form_tag('fields_form', url_for('entities/fields_choices', 'action=save' . (isset($_GET['id']) ? '&id=' . $_GET['id'] : '')), array('class' => 'form-horizontal', 'enctype' => 'multipart/form-data')) ?>
<div class="modal-body">
    <div class="form-body">

        <?php echo input_hidden_tag('entities_id', $_GET['entities_id']) . input_hidden_tag('fields_id', $_GET['fields_id']) ?>


        <div class="form-group">
            <label class="col-md-4 control-label" for="is_active"><?php echo TEXT_IS_ACTIVE ?></label>
            <div class="col-md-8">	
                <div class="checkbox-list"><label class="checkbox-inline"><?php echo input_checkbox_tag('is_active', '1', array('checked' => $obj['is_active'])) ?></label></div>      
            </div>			
        </div>

        <div class="form-group">
            <label class="col-md-4 control-label" for="parent_id"><?php echo tooltip_icon(TEXT_CHOICES_PARENT_INFO) . TEXT_PARENT ?></label>
            <div class="col-md-8">	
                <?php echo select_tag('parent_id', fields_choices::get_choices($_GET['fields_id']), (isset($_GET['parent_id']) ? $_GET['parent_id'] : $obj['parent_id']), array('class' => 'form-control input-medium')) ?>      
            </div>			
        </div>

        <div class="form-group">
            <label class="col-md-4 control-label" for="name"><?php echo tooltip_icon(TEXT_CHOICES_NAME_INFO) . TEXT_NAME ?></label>
            <div class="col-md-8">	
                <?php echo input_tag('name', $obj['name'], array('class' => 'form-control input-large required autofocus')) ?>      
            </div>			
        </div>
        
        <div class="form-group">
            <label class="col-md-4 control-label" for="cfg_menu_title"><?php echo TEXT_ICON; ?></label>
            <div class="col-md-8">	
                <?php echo input_icon_tag('icon', $obj['icon'], array('class' => 'form-control input-large')); ?>                    
            </div>			
        </div>

        <?php if($fields_info['type'] != 'fieldtype_autostatus'): ?>  
            <div class="form-group">
                <label class="col-md-4 control-label" for="is_default"><?php echo tooltip_icon(TEXT_CHOICES_IS_DEFAULT_INFO) . TEXT_IS_DEFAULT ?></label>
                <div class="col-md-8">	
                    <div class="checkbox-list"><label class="checkbox-inline"><?php echo input_checkbox_tag('is_default', '1', array('checked' => $obj['is_default'])) ?></label></div>      
                </div>			
            </div>
        <?php endif ?>  

        <div class="form-group">
            <label class="col-md-4 control-label" for="bg_color"><?php echo tooltip_icon(TEXT_CHOICES_BACKGROUND_COLOR_INFO) . TEXT_BACKGROUND_COLOR ?></label>
            <div class="col-md-8">
                <div class="input-group input-small color colorpicker-default" data-color="<?php echo (strlen($obj['bg_color']) > 0 ? $obj['bg_color'] : '#ff0000') ?>" >
                    <?php echo input_tag('bg_color', $obj['bg_color'], array('class' => 'form-control input-small')) ?>
                    <span class="input-group-btn">
                        <button class="btn btn-default" type="button">&nbsp;</button>
                    </span>
                </div>      
            </div>			
        </div>

        <div class="form-group">
            <label class="col-md-4 control-label" for="sort_order"><?php echo tooltip_icon(TEXT_CHOICES_SORT_ORDER_INFO) . TEXT_SORT_ORDER ?></label>
            <div class="col-md-8">	
                <?php echo input_tag('sort_order', $obj['sort_order'], array('class' => 'form-control input-small')) ?>      
            </div>			
        </div>

        <?php
        if($fields_info['type'] == 'fieldtype_image_map')
        {
            $cfg = new settings($fields_info['configuration']);

            if(!strlen($cfg->get('use_image_field')))
            {
                require(component_path('entities/choices_map_form'));
            }
        }
        else
        {
            ?>
            <div class="form-group">
                <label class="col-md-4 control-label" for="sort_order"><?php echo TEXT_VALUE ?></label>
                <div class="col-md-8">	
    <?php echo input_tag('value', $obj['value'], array('class' => 'form-control input-small number')) ?>
            <?php echo tooltip_text(TEXT_CHOICES_VALUE_INFO) ?>      
                </div>			
            </div>
        <?php } ?>

<?php if($fields_info['type'] == 'fieldtype_grouped_users'): ?>
            <div class="form-group">
                <label class="col-md-4 control-label" for="users"><?php echo tooltip_icon(TEXT_CHOICES_USERS_INFO) . TEXT_USERS_LIST ?></label>
                <div class="col-md-8">	
                    <?php
                    $attributes = array('class' => 'form-control chosen-select required',
                        'multiple' => 'multiple',
                        'data-placeholder' => TEXT_SELECT_SOME_VALUES);

                    echo select_tag('users[]', users::get_choices(), explode(',', $obj['users']), $attributes);
                    ?>      
                </div>			
            </div>
<?php endif ?>

    </div>
</div> 

<?php echo ajax_modal_template_footer() ?>

</form> 

<script>
    $(function ()
    {
        $('#fields_form').validate({
            submitHandler: function (form)
            {
                app_prepare_modal_action_loading(form)
                form.submit();
            }
        });
    });
</script>   