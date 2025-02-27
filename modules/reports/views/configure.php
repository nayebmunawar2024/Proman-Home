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
<?php echo ajax_modal_template_header(TEXT_NAV_LISTING_CONFIG) ?>

<?php
  if($app_redirect_to=='common_reports')
  {
    echo form_tag('sorting_form', url_for('ext/common_reports/reports'));
  }
  elseif(strstr($app_redirect_to,'funnelchart'))
  {
  	$id = str_replace('funnelchart','',$app_redirect_to);
  	echo form_tag('sorting_form', url_for('ext/funnelchart/view','id=' . $id . (strlen($app_path) ? '&path=' . $app_path:'')));
  }
  elseif(isset($_GET['path']))
  {
    echo form_tag('sorting_form', url_for('items/','path=' . $_GET['path']));
  }
  elseif($app_redirect_to == 'parent_infopage_filters')
  {
  	echo form_tag('sorting_form', url_for('entities/parent_infopage_filters','entities_id=' . $reports_info['entities_id']));
  }
  elseif($app_redirect_to == 'infopage_entityfield_filters')
  {
  	echo form_tag('sorting_form', url_for('entities/infopage_entityfield_filters','entities_id=' . $reports_info['entities_id'] . '&related_entities_id=' . $_GET['related_entities_id'] . '&fields_id=' . $_GET['fields_id']));
  }
  elseif($app_redirect_to == 'related_records_field_settings')
  {
  	echo form_tag('sorting_form', url_for('entities/fields_settings','entities_id=' . $_GET['entities_id'] . '&fields_id=' . $_GET['fields_id']));
  }
  else
  { 
    echo form_tag('sorting_form', url_for('reports/view','reports_id=' . $reports_info['id']));
  } 
    
  
  $fields_access_schema = users::get_fields_access_schema($reports_info['entities_id'],$app_user['group_id']);
  
  $listing = new items_listing($_GET['reports_id']);
?>

<div class="modal-body">

<?php if(in_array($listing->get_listing_type(),['table','tree_table']) and $listing->is_listing_fields_configuration()): ?>    
<div><?php echo TEXT_LISTING_CFG_INFO ?></div>

<table width="100%">
  <tr>
    <td valign="top" width="45%">
      <fieldset>
        <legend><?php echo TEXT_FIELDS_IN_LISTING ?></legend>
<div class="cfg_listing">        
  <ul id="fields_for_listing" class="sortable">
  <?php  
  if(count($fields_in_listing)>0)
  {
    $fields_query = db_query("select f.*, t.name as tab_name from app_fields f, app_forms_tabs t where f.id in (" . implode(',',$fields_in_listing). ") and  f.entities_id='" . db_input($reports_info['entities_id']) . "' and f.forms_tabs_id=t.id order by field(f.id," . implode(',',$fields_in_listing) . ")");
    while($v = db_fetch_array($fields_query))
    {
      //check field access
      if(isset($fields_access_schema[$v['id']]))
      {
        if($fields_access_schema[$v['id']]=='hide') continue;
      }
      
      //skip fieldtype_parent_item_id for deafult listing
      if($v['type']=='fieldtype_parent_item_id' and ($app_entities_cache[$reports_info['entities_id']]['parent_id']==0 or strlen($app_path)))
      {
        continue;      
      }
            
      echo '<li id="form_fields_' . $v['id'] . '"><div>' . fields_types::get_option($v['type'],'name',$v['name']) . '</div></li>';
    }
  }
  ?> 
  </ul>         
</div>
              
      </fieldset>
    </td>
    <td style="padding-left: 25px;" valign="top">
      <fieldset>
        <legend><?php echo TEXT_FIELDS_EXCLUDED_FROM_LISTING ?></legend>
<div class="cfg_listing">        
<ul id="fields_excluded_from_listing" class="sortable">
<?php
$exclude_fields_types_sql = " and f.type not in ('fieldtype_section','fieldtype_mapbbcode','fieldtype_mind_map','fieldtype_subentity_form')";
$fields_query = db_query("select f.*, t.name as tab_name from app_fields f, app_forms_tabs t where " . (count($fields_in_listing)>0 ? "f.id not in (" . implode(',',$fields_in_listing). ") and " : "") . "  f.entities_id='" . db_input($reports_info['entities_id']) . "' and f.forms_tabs_id=t.id {$exclude_fields_types_sql} order by t.sort_order, t.name, f.sort_order, f.name");
while($v = db_fetch_array($fields_query))
{
  //check field access
  if(isset($fields_access_schema[$v['id']]))
  {
    if($fields_access_schema[$v['id']]=='hide') continue;
  }
  
  //skip fieldtype_parent_item_id for deafult listing
  if($v['type']=='fieldtype_parent_item_id' and ($app_entities_cache[$reports_info['entities_id']]['parent_id']==0 or strlen($app_path)))
  {
    continue;      
  }
      
  echo '<li id="form_fields_' . $v['id'] . '"><div>' . fields_types::get_option($v['type'],'name',$v['name']). '</div></li>';
}
?> 
</ul>
</div>                     
      </fieldset>
    </td>
  </tr>
</table>

<?php endif ?>

<?php echo TEXT_SHOW . ' ' .  input_tag('rows_per_page',($reports_info['rows_per_page']>0 ? $reports_info['rows_per_page'] : CFG_APP_ROWS_PER_PAGE), array('class'=>'form-control form-control-inline input-xsmall')) . ' <span style="text-transform: lowercase;">' . TEXT_ROWS_PER_PAGE . '.</span>' ?>

</div>

<script>
         
  $(function() {                     
    	$( "ul.sortable" ).sortable({
    		connectWith: "ul",       
    		update: function(event,ui)
        {               
          data = '';  
          $( "ul.sortable" ).each(function() {data = data +'&'+$(this).attr('id')+'='+$(this).sortable("toArray") });                            
          data = data.slice(1)                      
          $.ajax({type: "POST",url: '<?php echo url_for("reports/configure","action=set_listing_fields&reports_id=" . (int)$_GET["reports_id"])?>',data: data});
        }
    	});

    	$('#rows_per_page').keyup(function(){
    		$.ajax({type: "POST",url: '<?php echo url_for("reports/configure","action=set_rows_per_page&reports_id=" . (int)$_GET["reports_id"])?>',data: {rows_per_page: $(this).val()}});
      })
      
      $('#sorting_form').submit(function(){      
         let url = $(this).attr('action')
         location.href = url; 
         return false;
      })
      
  }); 
  
  function reset_report_cfg_to_default()
  {
      $.ajax({type: "POST",url: '<?php echo url_for("reports/configure","action=reset_cfg_to_defatul&reports_id=" . (int)$_GET["reports_id"])?>'}).done(function(){
          let url = $('#sorting_form').attr('action')
          location.href = url;
      });      
  }
</script>
 
<?php 
$html = '<button type="button" class="btn btn-default" onClick="reset_report_cfg_to_default()" title="' . TEXT_RESET_TO_DEFAULT . '">' . TEXT_RESET . '</button>';
echo ajax_modal_template_footer(false, $html) ?>

</form> 