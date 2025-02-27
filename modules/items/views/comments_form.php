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
  $header_menu_button = ''; 
  
  //add templates menu in header
  if(is_ext_installed())
  {
    $header_menu_button = comments_templates::render_modal_header_menu($current_entity_id);
  }
  
  $text_comment = strlen($entity_cfg->get('comments_window_heading')) ? $entity_cfg->get('comments_window_heading') : TEXT_COMMENT;   
  
  echo ajax_modal_template_header($header_menu_button . $text_comment); 
          
  $app_items_form_name = 'comments_form';         
?>

<?php echo form_tag('comments_form', url_for('items/comments','action=save' . (isset($_GET['id']) ? '&id=' . $_GET['id']:'') ),array('class'=>'form-horizontal')) ?>
<div class="modal-body">
  <div class="form-body">
  
<?php echo input_hidden_tag('path',$_GET['path']) ?>

<?php $obj = (isset($_GET['id']) ?  db_find('app_comments',$_GET['id']): db_show_columns('app_comments')) ?>

<?php 
//reply to comment
	if(isset($_GET['reply_to']))
	{
		$reply_to_obj = db_find('app_comments',$_GET['reply_to']);
		if($entity_cfg->get('use_editor_in_comments')==1 or $entity_cfg->get('use_editor_in_comments')==2)
		{
			$obj['description'] = '<blockquote>' . $reply_to_obj['description'] . '</blockquote><p></p>' . "\n";
		}
		else 
		{
			$obj['description'] = $reply_to_obj['description'] . "\n";
		}
	}
	
	if(isset($_GET['description']))
	{
		$obj['description'] = db_prepare_input($_GET['description']);
	}
?>
      
<?php

$html_tab = array();
$html_tab_content = array();

if(!isset($_GET['id']))
{	
  $fields_access_schema = users::get_fields_access_schema($current_entity_id,$app_user['group_id']);
  
  //check fields access rules for item
  $item_info = db_find('app_entity_' . $current_entity_id,$current_item_id);  
  $access_rules = new access_rules($current_entity_id, $item_info);
  $fields_access_schema += $access_rules->get_fields_view_only_access();
   
//build default tab   
  $html_default_tab = '';
  $fields_query = db_query("select f.* from app_fields f where f.type not in (" . fields_types::get_reserverd_types_list() . ',' . fields_types::get_users_types_list() . ") and  f.entities_id='" . db_input($current_entity_id) . "' and f.comments_status=1 and f.comments_forms_tabs_id=0 order by f.comments_sort_order, f.name");
  while($v = db_fetch_array($fields_query))
  {       
    //check field access
    if(isset($fields_access_schema[$v['id']])) continue;
    
    //set off required option for comment form
    $v['is_required'] = 0;
    
     $html_default_tab .='
          <div class="form-group form-group-' . $v['id'] . '">
          	<label class="col-md-3 control-label" for="fields_' . $v['id']  . '">' . ($v['tooltip_display_as']=='icon' ? tooltip_icon($v['tooltip']) :'') . fields_types::get_option($v['type'],'name',$v['name']) . '</label>
            <div class="col-md-9">	
          	  ' . fields_types::render($v['type'],$v,array('field_' . $v['id']=>''),array('parent_entity_item_id'=>$parent_entity_item_id,'form'=>'comment')) . '
              ' . ($v['tooltip_display_as']!='icon' ? tooltip_text($v['tooltip']):'') . '
            </div>			
          </div>        
        ';   
  }
     
//build tabs heading 
  $html_tab[0] = '<li class="form_tab_0 active"><a data-toggle="tab" href="#form_tab_0">' . TEXT_GENERAL_INFO . '</a></li>';
  $tabs_query = db_fetch_all('app_comments_forms_tabs',"entities_id='" . db_input($current_entity_id) . "' order by  sort_order, name");
  while($tabs = db_fetch_array($tabs_query))
  {
  	$html_tab[$tabs['id']] = '<li class="form_tab_' . $tabs['id'] . '"><a data-toggle="tab" href="#form_tab_' . $tabs['id'] . '">' . $tabs['name'] . '</a></li>';  	
  }
    
//build tabls content
  $tabs_query = db_fetch_all('app_comments_forms_tabs',"entities_id='" . db_input($current_entity_id) . "' order by  sort_order, name");
  while($tabs = db_fetch_array($tabs_query))
  {
  	$html = '';
  	$fields_query = db_query("select f.* from app_fields f where f.type not in (" . fields_types::get_reserverd_types_list() . ',' . fields_types::get_users_types_list() . ") and  f.entities_id='" . db_input($current_entity_id) . "' and f.comments_status=1 and f.comments_forms_tabs_id='" . $tabs['id'] . "' order by f.comments_sort_order, f.name");
  	while($v = db_fetch_array($fields_query))
  	{
  		//check field access
  		if(isset($fields_access_schema[$v['id']])) continue;
  	
  		//set off required option for comment form
  		$v['is_required'] = 0;
  	
  		$html .='
          <div class="form-group form-group-' . $v['id'] . '">
          	<label class="col-md-3 control-label" for="fields_' . $v['id']  . '">' . ($v['tooltip_display_as']=='icon' ? tooltip_icon($v['tooltip']) :'') . fields_types::get_option($v['type'],'name',$v['name']) . '</label>
            <div class="col-md-9">
          	  ' . fields_types::render($v['type'],$v,array('field_' . $v['id']=>''),array('parent_entity_item_id'=>$parent_entity_item_id,'form'=>'comment')) . '
              ' . ($v['tooltip_display_as']!='icon' ? tooltip_text($v['tooltip']):'') . '
            </div>
          </div>
        ';
  	}
  	
  	if(strlen($html))
  	{
  		$html_tab_content[$tabs['id']] = '<div class="tab-pane fade" id="form_tab_' . $tabs['id'] . '">' . $html . '</div>';
  	}  	  	
  }
  
  //print_r($html_tab_content);
 
//render tabs heading if tabs exists  
  if(count($html_tab_content))
  {
  	$html = '<ul class="nav nav-tabs" id="form_tabs">';
  	
  	$html .= $html_tab[0];
  	
  	//build tabs heading and skip tabs with no fields
  	foreach($html_tab_content as $tab_id=>$content)
  	{
  		$html .= $html_tab[$tab_id];
  	}
  	
  	$html .= '</ul>';
  	
  	$html .= '
  		<div class="tab-content">
  				<div class="tab-pane fade active in" id="form_tab_0">';
  	echo $html;
  	
  }
  
//output fields for default tab  
  echo $html_default_tab;
}  
?>    
    <div class="form-group">
    	<label class="col-md-3 control-label" for="name"><?php echo $text_comment ?></label>
      <div class="col-md-9">	
    	  <?php 
          $attr = ['class'=>'form-control autofocus ' . ($entity_cfg->get('use_editor_in_comments')!=0 ? 'editor-auto-focus':'')];          
          if($entity_cfg->get('use_editor_in_comments')==2)
          {
             $attr['toolbar'] = 'small';
          }
          echo textarea_tag('description',$obj['description'],$attr) ?>        
      </div>			
    </div>

<?php if($entity_cfg->get('disable_attachments_in_comments')!=1): ?>    
    <div class="form-group">
    	<label class="col-md-3 control-label" for="name"><?php echo TEXT_ATTACHMENTS ?></label>
      <div class="col-md-9">	
    	  <?php 
            $attachments_field = [
                'id'=>'attachments',
                'configuration'=>json_encode([
                    'allow_audio_recording'=>$entity_cfg->get('comments_allow_audio_recording',0),
                    'audio_recording_length'=>$entity_cfg->get('comments_audio_recording_length',1)
                    ])
            ];
            echo fields_types::render('fieldtype_attachments',$attachments_field,array('field_attachments'=>$obj['attachments'])) ?>
        <?php echo input_hidden_tag('comments_attachments','',array('class'=>'form-control required_group')) ?>        
      </div>			
    </div>
<?php endif ?>    
    
<?php

//render tabs content
	if(count($html_tab_content))
	{
		$html = '</div>';
		
		//build tabs content
		foreach($html_tab_content as $tab_id=>$content)
		{
			$html .= $content;
		}
		
		$html .= '</div>';
		
		echo $html;
	}	


  //render templates fields values
  if(is_ext_installed())
  {
    echo comments_templates::render_fields_values($current_entity_id);
  }
?>    
    
 </div>
</div>
 
<?php echo ajax_modal_template_footer('hide-save-button','<button type="button" onClick="submit_comments_form()" class="btn btn-primary btn-primary-modal-action">' . addslashes(TEXT_BUTTON_SAVE). '</button>') ?>    
                  
</form> 


<?php
  //include comments form validation 
  require(component_path('items/comments_form_validation.js')) 
?> 

<?php 	
	
    $forms_fields_rules = new forms_fields_rules($current_entity_id,$app_items_form_name);                    
    echo $forms_fields_rules->apply();
?>

   