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

<h3 class="page-title"><?php echo TEXT_HEADING_APPLICATION ?></h3>

<?php echo form_tag('cfg_form', url_for('configuration/save', 'redirect_to=configuration/users_settings'), array('enctype' => 'multipart/form-data', 'class' => 'form-horizontal')) ?>
<div class="form-body">


    <div class="tabbable tabbable-custom">

        <ul class="nav nav-tabs">            
            <li class="active"><a href="#users_configuration"  data-toggle="tab"><?php echo TEXT_USERS_CONFIGURATION ?></a></li>
            <li ><a href="#users_extra_configuration"  data-toggle="tab"><?php echo TEXT_EXTRA ?></a></li>   
        </ul>


        <div class="tab-content">            
            <div class="tab-pane fade active in" id="users_configuration">

                <div class="form-group">
                    <label class="col-md-3 control-label" for="CFG_APP_DISPLAY_USER_NAME_ORDER"><?php echo TEXT_DISPLAY_USER_NAME_ORDER ?></label>
                    <div class="col-md-9">	
                        <?php echo select_tag('CFG[APP_DISPLAY_USER_NAME_ORDER]', array('firstname_lastname' => TEXT_FIRSTNAME_LASTNAME, 'lastname_firstname' => TEXT_LASTNAME_FIRSTNAME), CFG_APP_DISPLAY_USER_NAME_ORDER, array('class' => 'form-control input-medium')); ?>
                    </div>			
                </div>

                <div class="form-group">
                    <label class="col-md-3 control-label" for="CFG_PASSWORD_MIN_LENGTH"><?php echo TEXT_MIN_PASSWORD_LENGTH ?></label>
                    <div class="col-md-9">	
                        <?php echo input_tag('CFG[PASSWORD_MIN_LENGTH]', CFG_PASSWORD_MIN_LENGTH, array('class' => 'form-control input-small required')); ?>
                    </div>			
                </div>  
                
                <div class="form-group">
                    <label class="col-md-3 control-label" for="CFG_PASSWORD_MIN_LENGTH"><?php echo TEXT_STRONG_PASSWORD ?></label>
                    <div class="col-md-9">	
                        <?php echo select_tag('CFG[IS_STRONG_PASSWORD]',$default_selector ,CFG_IS_STRONG_PASSWORD, array('class' => 'form-control input-small required')); ?>
                        <?php echo tooltip_text(TEXT_STRONG_PASSWORD_TIP) ?>
                    </div>			
                </div> 

                <div class="form-group">
                    <label class="col-md-3 control-label" for="CFG_ALLOW_CHANGE_USERNAME"><?php echo TEXT_ALLOW_CHANGE_USERNAME ?></label>
                    <div class="col-md-9">	
                        <?php echo select_tag('CFG[ALLOW_CHANGE_USERNAME]', $default_selector, CFG_ALLOW_CHANGE_USERNAME, array('class' => 'form-control input-small')); ?>
                    </div>			
                </div>

                <div class="form-group">
                    <label class="col-md-3 control-label" for="CFG_ALLOW_REGISTRATION_WITH_THE_SAME_EMAIL"><?php echo TEXT_ALLOW_REGISTRATION_WITH_THE_SAME_EMAIL ?></label>
                    <div class="col-md-9">	
                        <?php echo select_tag('CFG[ALLOW_REGISTRATION_WITH_THE_SAME_EMAIL]', $default_selector, CFG_ALLOW_REGISTRATION_WITH_THE_SAME_EMAIL, array('class' => 'form-control input-small')); ?>
                    </div>			
                </div>   


                <div class="form-group">
                    <label class="col-md-3 control-label" for="CFG_APP_DISABLE_CHANGE_PWD"><?php echo tooltip_icon(TEXT_SELECT_USERS_GROUPS) . TEXT_DISABLE_CHANGE_PWD ?></label>
                    <div class="col-md-9">	
                        <?php echo select_tag('CFG[APP_DISABLE_CHANGE_PWD][]', array('' => TEXT_NONE) + access_groups::get_choices(false), CFG_APP_DISABLE_CHANGE_PWD, array('class' => 'form-control input-xlarge chosen-select', 'multiple' => 'multiple')); ?>
                    </div>			
                </div>  
                
                <h3 class="form-section"><?php echo TEXT_USERS_GROUPS ?></h3>
                
                <div class="form-group">
                    <label class="col-md-3 control-label" for="CFG_ENABLE_MULTIPLE_ACCESS_GROUPS"><?php echo TEXT_ASSIGN_USER_TO_MULTIPLE_GROUPS ?></label>
                    <div class="col-md-9">	
                        <?php echo select_tag('CFG[ENABLE_MULTIPLE_ACCESS_GROUPS]', $default_selector, CFG_ENABLE_MULTIPLE_ACCESS_GROUPS, array('class' => 'form-control input-small')); ?>
                        <?php echo tooltip_text(TEXT_ASSIGN_USER_TO_MULTIPLE_GROUPS_INFO) ?>
                    </div>			
                </div>  
                
                <div class="form-group">
                    <label class="col-md-3 control-label" for="CFG_DISPLAY_USER_GROUP_IN_MENU"><?php echo tooltip_icon(TEXT_SELECT_USERS_GROUPS) . TEXT_DISPLAY_USER_GROUP_IN_MENU ?></label>
                    <div class="col-md-2" style="width: 150px">	
                        <?php echo select_tag('CFG[DISPLAY_USER_GROUP_IN_MENU]', $default_selector, CFG_DISPLAY_USER_GROUP_IN_MENU, array('class' => 'form-control input-small')); ?>
                    </div>
                    <div class="col-md-6">	
                        <?php echo select_tag('CFG[DISPLAY_USER_GROUP_ID_IN_MENU][]', array('' => TEXT_NONE) + access_groups::get_choices(), CFG_DISPLAY_USER_GROUP_ID_IN_MENU, array('class' => 'form-control input-xlarge chosen-select', 'multiple' => 'multiple')); ?>
                    </div>
                </div>

            </div>
            
            <div class="tab-pane fade" id="users_extra_configuration">
                                                
                <div class="form-group">
                    <label class="col-md-3 control-label" ><?php echo TEXT_AUTO_LOGOUT_INACTION_USERS ?></label>
                    <div class="col-md-9">	
                        <?php echo select_tag('CFG[AUTO_LOGOUT_INACTION_USERS]', $default_selector, CFG_AUTO_LOGOUT_INACTION_USERS, array('class' => 'form-control input-small')); ?>
                    </div>			
                </div>
                
                 <div class="form-group" form_display_rules="CFG_AUTO_LOGOUT_INACTION_USERS:1">
                    <label class="col-md-3 control-label" ><?php echo TEXT_TIME_WITHOUT_ACTION ?></label>
                    <div class="col-md-9">	
                        <?php echo input_tag('CFG[AUTO_LOGOUT_INACTION_TIME]', CFG_AUTO_LOGOUT_INACTION_TIME, array('class' => 'form-control input-small','type'=>'number','min'=>3)); ?>
                        <?= tooltip_text(TEXT_IN_MINUTES) ?>
                    </div>			
                </div> 
                
                <div class="form-group" form_display_rules="CFG_AUTO_LOGOUT_INACTION_USERS:1">
                    <label class="col-md-3 control-label" ><?php echo TEXT_BLOCK_RECORD_FORM_WHEN_EDITING ?></label>
                    <div class="col-md-9">	
                        <?php echo select_tag('CFG[BLOCK_RECORD_FORM_WHEN_EDITING]', $default_selector, CFG_BLOCK_RECORD_FORM_WHEN_EDITING, array('class' => 'form-control input-small')); ?>
                        <?= tooltip_text(TEXT_BLOCK_RECORD_FORM_WHEN_EDITING_INFO) ?>
                    </div>			
                </div>
                
                <div class="form-group" form_display_rules="CFG_AUTO_LOGOUT_INACTION_USERS:1">
                    <label class="col-md-3 control-label" ><?php echo TEXT_MESSAGE_TEXT ?></label>
                    <div class="col-md-9">	
                        <?php echo textarea_tag('CFG[BLOCK_RECORD_FORM_WARNIGN_TEXT]', CFG_BLOCK_RECORD_FORM_WARNIGN_TEXT, array('class' => 'form-control input-xlarge')); ?>
                        <?= tooltip_text(TEXT_DEFAULT . ': ' . TEXT_FORM_BLOCKED_WARNING) ?>
                    </div>			
                </div>

            </div>

        </div>
    </div>

    <?php echo submit_tag(TEXT_BUTTON_SAVE) ?>

</div>
</form>

<script>
    $(function () {
        $('#cfg_form').validate({
            rules: {
                APP_LOGO: {
                    required: false,
                    extension: "gif|jpeg|jpg|png"
                }
            }
        });


        $(".input-masked").each(function () {
            $.mask.definitions["~"] = "[,. *]";
            $(this).mask($(this).attr("data-mask"));
        })

    });

</script> 