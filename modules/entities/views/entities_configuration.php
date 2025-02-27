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

<?php require(component_path('entities/navigation')) ?>

<?php $default_selector = array('1' => TEXT_YES, '0' => TEXT_NO); ?>

<?php echo form_tag('cfg', url_for('entities/entities_configuration', 'action=save&entities_id=' . $_GET['entities_id']), array('class' => 'form-horizontal')) ?>

<div class="tabbable tabbable-custom">

    <ul class="nav nav-tabs">
        <li class="active"><a href="#general_info"  data-toggle="tab"><?php echo TEXT_TITLES ?></a></li>
        <li><a href="#comments_configuration"  data-toggle="tab"><?php echo TEXT_COMMENTS_TITLE ?></a></li>   
        <li><a href="#redirects_configuration"  data-toggle="tab"><?php echo TEXT_REDIRRECTS ?></a></li> 
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade active in" id="general_info">

            <div class="form-group">
                <label class="col-md-3 control-label" for="cfg_menu_title"><?php echo tooltip_icon(TEXT_MENU_TITLE_TOOLTIP) . TEXT_MENU_TITLE; ?></label>
                <div class="col-md-9">	
                    <?php echo input_tag('cfg[menu_title]', $cfg->get('menu_title'), array('class' => 'form-control input-large')); ?>       
                </div>			
            </div>

            <div class="form-group">
                <label class="col-md-3 control-label" for="cfg_menu_title"><?php echo TEXT_MENU_ICON_TITLE; ?></label>
                <div class="col-md-9">	
                    <?php echo input_icon_tag('cfg[menu_icon]', $cfg->get('menu_icon'), array('class' => 'form-control input-large')); ?>                    
                </div>			
            </div>
            
            <div class="form-group">
	  	<label class="col-md-3 control-label"><?php echo TEXT_COLOR ?></label>
                <div class="col-md-9">
                    <table><tr><td>     
                        <?php echo input_color('cfg[menu_icon_color]',$cfg->get('menu_icon_color')) ?>	    			  	  
                        <?php echo tooltip_text(TEXT_ICON) ?>
                    </td><td style="padding-left: 10px;">            
                        <?php echo input_color('cfg[menu_bg_color]',$cfg->get('menu_bg_color'))?>
                        <?php echo tooltip_text(TEXT_BACKGROUND) ?>
                    </td></tr></table>    
                </div> 
            </div>
                        
            <h3 class="form-section "><?php echo TEXT_WINDOW ?></h3>

            <div class="form-group">
                <label class="col-md-3 control-label" for="cfg_window_heading"><?php echo tooltip_icon(TEXT_WINDOW_HEADING_TOOLTIP) . TEXT_WINDOW_HEADING; ?></label>
                <div class="col-md-9">	
                    <?php echo input_tag('cfg[window_heading]', $cfg->get('window_heading'), array('class' => 'form-control input-large')); ?>       
                </div>			
            </div>
            
            <?php
                $choices = [
                    '' => TEXT_AUTOMATIC, 
                    'ajax-modal-width-790' => TEXT_WIDE . " (790 px)", 
                    'ajax-modal-width-1100' => TEXT_XWIDE . " (1100 px)"
                ];
                
                for($i=1200;$i<1800;$i+=100)
                {
                    $choices['ajax-modal-width-' . $i] = TEXT_XWIDE . " ({$i} px)"; 
                }
            ?>

            <div class="form-group">
                <label class="col-md-3 control-label" for="cfg_window_width"><?php echo TEXT_WINDOW_WIDTH; ?></label>
                <div class="col-md-9">	
                    <?php echo select_tag('cfg[window_width]', $choices, $cfg->get('window_width'), array('class' => 'form-control input-medium')); ?>       
                </div>			
            </div>
            
            <h3 class="form-section "><?php echo TEXT_NAV_LISTING_CONFIG ?></h3>
            
            <div class="form-group">
                <label class="col-md-3 control-label" for="cfg_listing_heading"><?php echo tooltip_icon(TEXT_LISTING_HEADING_TOOLTIP) . TEXT_LISTING_HEADING; ?></label>
                <div class="col-md-9">	
                    <?php echo input_tag('cfg[listing_heading]', $cfg->get('listing_heading'), array('class' => 'form-control input-large')); ?>       
                </div>			
            </div>

            <div class="form-group">
                <label class="col-md-3 control-label" for="cfg_insert_button"><?php echo tooltip_icon(TEXT_INSERT_BUTTON_TITLE_TOOLTIP) . TEXT_INSERT_BUTTON_TITLE; ?></label>
                <div class="col-md-9">	
                    <?php echo input_tag('cfg[insert_button]', $cfg->get('insert_button'), array('class' => 'form-control input-large')); ?>       
                </div>			
            </div>  

            <div class="form-group">
                <label class="col-md-3 control-label" for="cfg_reports_hide_insert_button"><?php echo TEXT_HIDE_INSERT_BUTTON_IN_REPORTS; ?></label>
                <div class="col-md-9">	
                    <?php echo select_tag('cfg[reports_hide_insert_button]', $default_selector, $cfg->get('reports_hide_insert_button',0), array('class' => 'form-control input-small')); ?>       
                </div>			
            </div>
            
            <div class="form-group">
                <label class="col-md-3 control-label" for="cfg_reports_hide_insert_button"><?php echo TEXT_DEBUG_MODE . ' (' . TEXT_MYSQL_QUERY . ')'; ?></label>
                <div class="col-md-9">	
                    <?php echo select_tag('cfg[listing_debug_mode]', $default_selector, $cfg->get('listing_debug_mode',0), array('class' => 'form-control input-small')); ?>       
                </div>			
            </div>

            <h3 class="form-section "><?php echo TEXT_DEFAULT_NOTIFICATIONS ?></h3>
            <p class="form-section-description"><?php echo TEXT_DEFAULT_NOTIFICATIONS_INFO ?></p>

            <div class="form-group">
                <label class="col-md-3 control-label" for="cfg_insert_button"><?php echo tooltip_icon(TEXT_EMAIL_SUBJECT_NEW_ITEM_TOOLTIP) . TEXT_EMAIL_SUBJECT_NEW_ITEM; ?></label>
                <div class="col-md-9">	
                    <?php echo input_tag('cfg[email_subject_new_item]', $cfg->get('email_subject_new_item'), array('class' => 'form-control input-large')); ?>       
                </div>			
            </div> 

            <div class="form-group">
                <label class="col-md-3 control-label" for="cfg_insert_button"><?php echo tooltip_icon(TEXT_EMAIL_SUBJECT_UPDATED_ITEM_TOOLTIP) . TEXT_EMAIL_SUBJECT_UPDATED_ITEM; ?></label>
                <div class="col-md-9">	
                    <?php echo input_tag('cfg[email_subject_updated_item]', $cfg->get('email_subject_updated_item'), array('class' => 'form-control input-large')); ?>       
                </div>			
            </div>

            <div class="form-group">
                <label class="col-md-3 control-label" for="cfg_disable_notification"><?php echo tooltip_icon(TEXT_DISABLE_EMAIL_NOTIFICATIONS_TIP) . TEXT_DISABLE_EMAIL_NOTIFICATIONS ?></label>
                <div class="col-md-9">	
                    <?php echo select_tag('cfg[disable_notification]', $default_selector, $cfg->get('disable_notification', 0), array('class' => 'form-control input-small')) ?>							 
                </div>			
            </div>

            <div class="form-group">
                <label class="col-md-3 control-label" for="cfg_disable_internal_notification"><?php echo tooltip_icon(TEXT_DISABLE_INTERNAL_NOTIFICATIONS_INFO) . TEXT_DISABLE_INTERNAL_NOTIFICATIONS ?></label>
                <div class="col-md-9">	
                    <?php echo select_tag('cfg[disable_internal_notification]', $default_selector, $cfg->get('disable_internal_notification', 0), array('class' => 'form-control input-small')) ?>							 
                </div>			
            </div>

            <div class="form-group">
                <label class="col-md-3 control-label" for="cfg_disable_highlight_unread"><?php echo tooltip_icon(TEXT_DISABLE_HIGHLIGH_UNREAD_INFO) . TEXT_DISABLE_HIGHLIGH_UNREAD ?></label>
                <div class="col-md-9">	
                    <?php echo select_tag('cfg[disable_highlight_unread]', $default_selector, $cfg->get('disable_highlight_unread', 0), array('class' => 'form-control input-small')) ?>							 
                </div>			
            </div>	 			


        </div>
        <div class="tab-pane fade" id="comments_configuration">

            <div class="form-group">
                <label class="col-md-3 control-label" for="cfg_use_comments"><?php echo TEXT_USE_COMMENTS; ?></label>
                <div class="col-md-9">	
                    <?php echo select_tag('cfg[use_comments]', $default_selector, $cfg->get('use_comments', 0), array('class' => 'form-control input-small')); ?> 
                    <?php echo tooltip_text(TEXT_USE_COMMENTS_TOOLTIP) ?>
                </div>			
            </div>
            
            <div form_display_rules="cfg_use_comments:1">                                
                
                <div class="form-group">
                    <label class="col-md-3 control-label" for="cfg_comments_listing_type"><?php echo TEXT_LISTING_TYPE; ?></label>
                    <div class="col-md-9">	
                        <?php echo select_tag('cfg[comments_listing_type]', ['table'=>TEXT_TABLE,'list'=>TEXT_LIST], $cfg->get('comments_listing_type'), array('class' => 'form-control input-small')); ?>                     
                    </div>			
                </div>
                
                  

                <p class="form-section"><?= TEXT_HEADING ?></p>
                
                <div class="form-group" form_display_rules="cfg_comments_listing_type:table">
                    <label class="col-md-3 control-label" for="cfg_comments_listing_heading"><?php echo TEXT_LISTING_HEADING; ?></label>
                    <div class="col-md-9">	
                        <?php echo input_tag('cfg[comments_listing_heading]', $cfg->get('comments_listing_heading'), array('class' => 'form-control input-large','placeholder'=>TEXT_DEFAULT . ': ' . TEXT_COMMENTS)); ?>       
                    </div>			
                </div>

                <div class="form-group">
                    <label class="col-md-3 control-label" for="cfg_comments_insert_button"><?php echo  TEXT_INSERT_BUTTON_TITLE; ?></label>
                    <div class="col-md-9">	
                        <?php echo input_tag('cfg[comments_insert_button]', $cfg->get('comments_insert_button'), array('class' => 'form-control input-large','placeholder'=>TEXT_DEFAULT . ': ' . TEXT_BUTTON_ADD_COMMENT)); ?>       
                    </div>			
                </div>
                
                <div class="form-group">
                    <label class="col-md-3 control-label" for="cfg_comments_window_heading"><?php echo  TEXT_WINDOW_HEADING; ?></label>
                    <div class="col-md-9">	
                        <?php echo input_tag('cfg[comments_window_heading]', $cfg->get('comments_window_heading'), array('class' => 'form-control input-large','placeholder'=>TEXT_DEFAULT . ': ' . TEXT_COMMENT)); ?>       
                    </div>			
                </div>

                <p class="form-section"><?= TEXT_DISPLAY ?></p>

                <div class="form-group">
                    <label class="col-md-3 control-label" for="cfg_use_comments"><?php echo tooltip_icon(TEXT_DISPLAY_COMMENTS_TOOLTIP) . TEXT_DISPLAY_COMMENTS_ID; ?></label>
                    <div class="col-md-9">	
                        <?php echo select_tag('cfg[display_comments_id]', $default_selector, $cfg->get('display_comments_id'), array('class' => 'form-control input-small')); ?>                     
                    </div>			
                </div>

                <div class="form-group">
                    <label class="col-md-3 control-label" for="cfg_use_comments"><?php echo tooltip_icon(TEXT_DISPLAY_LAST_COMMENT_IN_LISTING_INFO) . TEXT_DISPLAY_LAST_COMMENT_IN_LISTING; ?></label>
                    <div class="col-md-9">	
                        <?php echo select_tag('cfg[display_last_comment_in_listing]', $default_selector, $cfg->get('display_last_comment_in_listing', 1), array('class' => 'form-control input-small')); ?>                     
                    </div>			
                </div>


                <div class="form-group">
                    <label class="col-md-3 control-label" for="cfg_number_characters_in_list"><?php echo tooltip_icon(TEXT_NUMBER_DISPLAYED_CHARACTERS_IN_LIST_INFO) . TEXT_NUMBER_DISPLAYED_CHARACTERS_IN_LIST; ?></label>
                    <div class="col-md-9">	
                        <?php echo input_tag('cfg[number_characters_in_comments_list]', $cfg->get('number_characters_in_comments_list'), array('class' => 'form-control input-small','type'=>'number')); ?>                     
                    </div>			
                </div>



    <?php
        $choices = [
            '0' => TEXT_NO,
            '1' => TEXT_YES,
            '2' => TEXT_YES . '. '. TEXT_TOOLBAR . ': ' . TEXT_IN_ONE_LINE
        ];
    ?>            
                <div class="form-group">
                    <label class="col-md-3 control-label" for="cfg_use_comments"><?php echo tooltip_icon(TEXT_USE_EDITOR_IN_COMMENTS_TOOLTIP) . TEXT_USE_EDITOR_IN_COMMENTS; ?></label>
                    <div class="col-md-9">	
                        <?php echo select_tag('cfg[use_editor_in_comments]', $choices, $cfg->get('use_editor_in_comments'), array('class' => 'form-control input-large')); ?>                     
                    </div>			
                </div>

                

                <div class="form-group">
                    <label class="col-md-3 control-label" for="cfg_use_comments"><?php echo TEXT_DISABLE_USER_AVATAR; ?></label>
                    <div class="col-md-9">	
                        <?php echo select_tag('cfg[disable_avatar_in_comments]', $default_selector, $cfg->get('disable_avatar_in_comments',0), array('class' => 'form-control input-small')); ?>       
                    </div>			
                </div>

                <div class="form-group">
                    <label class="col-md-3 control-label" for="cfg_image_preview_in_comments"><?php echo tooltip_icon(TEXT_USE_IMAGE_PREVIEW_TIP) . TEXT_USE_IMAGE_PREVIEW; ?></label>
                    <div class="col-md-9">	
                        <?php echo select_tag('cfg[image_preview_in_comments]', $default_selector, $cfg->get('image_preview_in_comments', 0), array('class' => 'form-control input-small')); ?>       
                    </div>			
                </div> 
                
                <p class="form-section"><?= TEXT_ATTACHMENTS ?></p>
                
                <div class="form-group">
                    <label class="col-md-3 control-label" for="cfg_use_comments"><?php echo TEXT_DISABLE_ATTACHMENTS; ?></label>
                    <div class="col-md-9">	
                        <?php echo select_tag('cfg[disable_attachments_in_comments]', $default_selector, $cfg->get('disable_attachments_in_comments'), array('class' => 'form-control input-small')); ?>       
                    </div>			
                </div>
                
                <div form_display_rules="cfg_disable_attachments_in_comments:0">
                    <div class="form-group">
                        <label class="col-md-3 control-label" for="cfg_comments_allow_audio_recording"><?php echo tooltip_icon(TEXT_ALLOW_AUDIO_RECORDING_INFO) . TEXT_ALLOW_AUDIO_RECORDING; ?></label>
                        <div class="col-md-9">	
                            <?php echo select_tag('cfg[comments_allow_audio_recording]', $default_selector, $cfg->get('comments_allow_audio_recording'), array('class' => 'form-control input-small')); ?>       
                        </div>			
                    </div>

                    <div class="form-group" form_display_rules="cfg_comments_allow_audio_recording:1">
                        <label class="col-md-3 control-label" for="cfg_comments_audio_recording_length"><?php echo tooltip_icon(TEXT_AUDIO_RECORDING_LENGTH_INFO) . TEXT_AUDIO_RECORDING_LENGTH; ?></label>
                        <div class="col-md-9">	
                            <?php echo select_tag('cfg[comments_audio_recording_length]', [1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9], $cfg->get('comments_audio_recording_length'), array('class' => 'form-control input-small')); ?>       
                        </div>			
                    </div>
                </div>

                <p class="form-section"><?= TEXT_NOTIFICATION ?></p>

                <div class="form-group">
                    <label class="col-md-3 control-label" for="cfg_insert_button"><?php echo tooltip_icon(TEXT_EMAIL_SUBJECT_NEW_COMMENT_TOOLTIP) . TEXT_EMAIL_SUBJECT_NEW_COMMENT; ?></label>
                    <div class="col-md-9">	
                        <?php echo input_tag('cfg[email_subject_new_comment]', $cfg->get('email_subject_new_comment'), array('class' => 'form-control input-large')); ?>                     
                    </div>			
                </div>

                <div class="form-group">
                    <label class="col-md-3 control-label" for="cfg_send_notification_to_assigned"><?php echo tooltip_icon(TEXT_SEND_COMMENTS_NOTIFICATION_TO_ASSIGNED_INFO) . TEXT_SEND_NOTIFICATION_TO_ASSIGNED_ONLY; ?></label>
                    <div class="col-md-9">	
                        <?php echo select_tag('cfg[send_notification_to_assigned]', $default_selector, $cfg->get('send_notification_to_assigned', 0), array('class' => 'form-control input-small')); ?>                     
                    </div>			
                </div>
            
            </div>

        </div>

        <div class="tab-pane fade" id="redirects_configuration">

            <?php
            $after_adding_selector = array(
                'subentity' => TEXT_REDIRECT_TO_SUBENTITY,
                'listing' => TEXT_REDIRECT_TO_LISTING,
                'info' => TEXT_REDIRECT_TO_INFO,
                'form' => TEXT_KEEP_CURRENT_FORM_OPEN,
            );

            $click_heading_selector = array(
                'subentity' => TEXT_REDIRECT_TO_SUBENTITY,
                'info' => TEXT_REDIRECT_TO_INFO,
            );
            
            if(is_ext_installed())
            {
                $click_heading_selector = array_merge($click_heading_selector,items_redirects::get_reports_choices($_GET['entities_id']));
            }
            ?>	

            <div class="form-group">
                <label class="col-md-3 control-label" for="cfg_use_comments"><?php echo TEXT_REDIRECT_AFTER_ADDING; ?></label>
                <div class="col-md-9">	
                    <?php echo select_tag('cfg[redirect_after_adding]', $after_adding_selector, $cfg->get('redirect_after_adding'), array('class' => 'form-control input-xlarge')); ?>       
                </div>			
            </div>

            <div class="form-group">
                <label class="col-md-3 control-label" for="cfg_use_comments"><?php echo TEXT_REDIRECT_AFTER_CLICK_HEADING; ?></label>
                <div class="col-md-9">	
                    <?php echo select_tag('cfg[redirect_after_click_heading]', $click_heading_selector, $cfg->get('redirect_after_click_heading'), array('class' => 'form-control input-xlarge')); ?>       
                </div>			
            </div>

        </div>

    </div>

</div>	  



<?php echo submit_tag(TEXT_BUTTON_SAVE) ?>

</form>


<script>
    $(function ()
    {
        $('.tooltips').tooltip();
    });
</script>    



