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

$access_rules = new access_rules($current_entity_id, $current_item_id);

if(!users::has_comments_access('view', $access_rules->get_comments_access_schema())) exit() 

?>

<?php

$entity_cfg = new entities_cfg($current_entity_id);

$listing_sql_query = '';

if(strlen($_POST['search_keywords'])>0)
{
  echo '<div class="alert alert-info">' . sprintf(TEXT_SEARCH_RESULT_FOR,htmlspecialchars($_POST['search_keywords'])) . ' <span onClick="reset_search()" class="reset_search">' . TEXT_RESET_SEARCH . '</span></div>';
  
  require(component_path('items/add_search_comments_query'));
}


$user_has_comments_access = (users::has_comments_access('update', $access_rules->get_comments_access_schema()) or users::has_comments_access('delete', $access_rules->get_comments_access_schema()) or users::has_comments_access('create', $access_rules->get_comments_access_schema()));
	
?>

<div class="table-scrollable">
<table class="table table-striped table-bordered table-hover">
<thead>  
  <tr>
    <?php if($user_has_comments_access) echo '<th>' . '</th>' ?>    
    <?php if($entity_cfg->get('display_comments_id')==1) echo '<th>' . TEXT_ID . '</th>' ?>
    <th width="100%"><?php echo strlen($entity_cfg->get('comments_listing_heading')) ? $entity_cfg->get('comments_listing_heading') : TEXT_COMMENTS ?></th>
    <th><?php echo TEXT_DATE_ADDED ?></th>
  </tr>
</thead>
<tbody>  
<?php 

$fields_access_schema = users::get_fields_access_schema($current_entity_id,$app_user['group_id']);
$choices_cache = fields_choices::get_cache();

$html = '';
$listing_sql = "select * from app_comments where entities_id='" . db_input($current_entity_id) . "' and items_id='" . db_input($current_item_id) . "' " . $listing_sql_query . " order by id desc";
$listing_split = new split_page($listing_sql,'items_comments_listing');
$listing_split->listing_funciton = 'load_comments_listing';
$items_query = db_query($listing_split->sql_query);
while($item = db_fetch_array($items_query))
{
  $html_action_column = '';
  if($user_has_comments_access)
  {
    $html_action_column = '
      <td class="nowrap">
      ' . ((users::has_comments_access('delete', $access_rules->get_comments_access_schema()) and ($item['created_by']==$app_user['id'] or $app_user['group_id']==0 or users::has_comments_access('full', $access_rules->get_comments_access_schema()))) ? button_icon_delete(url_for('items/comments_delete','id=' .$item['id'] . '&path=' . $_POST['path'])) . '<br>':'') . '
      ' . ((users::has_comments_access('update', $access_rules->get_comments_access_schema()) and ($item['created_by']==$app_user['id'] or $app_user['group_id']==0 or users::has_comments_access('full', $access_rules->get_comments_access_schema()))) ? button_icon_edit(url_for('items/comments_form','id=' .$item['id'] . '&path=' . $_POST['path'])) . '<br>':'') . '
			' . (users::has_comments_access('create', $access_rules->get_comments_access_schema()) ?  button_icon(TEXT_REPLY,'fa fa-reply',url_for('items/comments_form','reply_to=' .$item['id'] . '&path=' . $_POST['path'])):'') . '
      </td>
    ';
  }
  
  $html_fields = '';
  $comments_fields_query = db_query("select f.*,ch.fields_value from app_comments_history ch, app_fields f where comments_id='" . db_input($item['id']) . "' and f.id=ch.fields_id order by ch.id");
  while($field = db_fetch_array($comments_fields_query))
  {
    //check field access
    if(isset($fields_access_schema[$field['id']]))
    {
      if($fields_access_schema[$field['id']]=='hide') continue;
    }
        
    $output_options = array('class'=>$field['type'],
                            'value'=>$field['fields_value'],
                            'field'=>$field, 
                            'path'=>$_POST['path'],
                            'item'=>array('id'=>$current_item_id),
                            'is_listing'=>true,
                            'is_comments_listing' =>true,
    );
        
    $html_fields .='                      
        <tr><th>&bull;&nbsp;' . fields_types::get_option($field['type'],'name',$field['name']) . ':&nbsp;</th><td>' . fields_types::output($output_options). '</td></tr>           
    ';
  }
  
  if(strlen($html_fields)>0)
  {
    $html_fields = '<table class="comments-history">' . $html_fields . '</table>';
  }
  
  
  $output_options = array('class'=>'fieldtype_attachments',
                          'value'=>$item['attachments'],
                          'path'=>$_POST['path'],
                          'field'=>array('entities_id'=>$current_entity_id,'configuration'=>json_encode(['use_image_preview'=>$entity_cfg->get('image_preview_in_comments',0)])),
                          'item'=>array('id'=>$current_item_id)); 
                          
  $attachments = fields_types::output($output_options);

  if((int)$entity_cfg->get('use_editor_in_comments')==0)
  {
    $item['description'] = nl2br($item['description']);
  }
  
  $description = auto_link_text($item['description']);
  
  if((int)$entity_cfg->get('number_characters_in_comments_list')>0 and strlen(strip_tags($description))>$entity_cfg->get('number_characters_in_comments_list'))
  {
      $description = '
                    <div class="truncated-text-block">
                        <div class="truncated-text">' . nl2br(mb_substr(strip_tags($description),0,$entity_cfg->get('number_characters_in_comments_list'))). '... <a href="#" class="truncated-text-expand">' . TEXT_READ_MORE. ' <i class="fa fa-angle-right"></i></a></div>
                        <div class="full-text hidden">' . $description  . ' <a href="#" class="truncated-text-collapse"><i class="fa fa-angle-left"></i> ' . TEXT_HIDE. '</a></div>
                    </div>
                ';
  }
  
  $photo = '';
  if($entity_cfg->get('disable_avatar_in_comments',0)!=1 and $item['created_by'] and isset($app_users_cache[$item['created_by']]))
  {
      $photo = render_user_photo($app_users_cache[$item['created_by']]['photo']);
  }

   $html .= '
    <tr>
      ' . $html_action_column . ' 
      ' . ($entity_cfg->get('display_comments_id')==1 ? '<td>' . $item['id'] . '</td>':''). '   
      <td style="white-space: normal;">
        			<div class="ckeditor-images-content-prepare"><div class="fieldtype_textarea_wysiwyg">' . $description  . '</div></div>' . 
        			$attachments . 
        			$html_fields .        			
      '</td>
      <td class="nowrap">' . 
           format_date_time($item['date_added']) . 
           (($item['created_by']>0 and isset($app_users_cache[$item['created_by']])) ? '<br><span ' . users::render_publi_profile($app_users_cache[$item['created_by']],true). '>' . $app_users_cache[$item['created_by']]['name']. '</span><br>' . $photo : '') . '</td>
    </tr>
  '; 
}

if($listing_split->number_of_rows==0)
{
  $html .= '
    <tr>
      <td colspan="4">' . TEXT_NO_RECORDS_FOUND . '</td>
    </tr>
  '; 
}

$html .= '
  </tbody>
</table>
</div>
';

//add pager
$html .= '
  <table width="100%">
    <tr>
      <td>' . $listing_split->display_count() . '</td>
      <td align="right">' . $listing_split->display_links(). '</td>
    </tr>
  </table>
';

echo $html;  

exit();
