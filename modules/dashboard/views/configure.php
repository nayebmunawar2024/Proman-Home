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

<?php echo ajax_modal_template_header(TEXT_CONFIGURE_DASHBOARD) ?>

<?php
$common_reports_query = db_query("select count(*) as total from app_reports r, app_entities e, app_entities_access ea  where r.entities_id = e.id and e.id=ea.entities_id and length(ea.access_schema)>0 and ea.access_groups_id='" . db_input($app_user['group_id']) . "' and (find_in_set(" . $app_user['group_id'] . ",r.users_groups) or find_in_set(" . $app_user['id'] . ",r.assigned_to)) and r.reports_type = 'common' order by r.dashboard_sort_order, r.name");
$common_reports = db_fetch_array($common_reports_query);
$common_reports_count = $common_reports['total'];
?>


<?php echo form_tag('dashboard', url_for('dashboard/','action=save')) ?>

<div class="modal-body ajax-modal-width-790">

<ul class="nav nav-tabs" id="form_tabs">
  <li class="active" ><a data-toggle="tab" href="#form_tab_standard_reports"><?php echo TEXT_STANDARD_REPORTS ?></a></li>


  <li><a data-toggle="tab" href="#form_tab_reports_sections"><?php echo TEXT_SECTIONS ?></a></li>
<?php if(users::has_reports_access()): ?>    
  <li><a data-toggle="tab" href="#form_tab_reports_counter"><?php echo TEXT_COUNTERS ?></a></li>
  <li><a data-toggle="tab" href="#form_tab_reports_header"><?php echo TEXT_HEADER_TOP_MENU ?></a></li>
<?php endif ?>
  
<?php if($common_reports_count>0): ?>  
  <li><a data-toggle="tab" href="#form_tab_common_reports"><?php echo TEXT_EXT_COMMON_REPORTS ?></a></li>
<?php endif ?>
  
</ul>

<div class="tab-content">
  <div class="tab-pane active" id="form_tab_standard_reports">
  
<div><?php echo TEXT_CONFIGURE_DASHBOARD_INFO ?></div><br>

<table style="width: 100%; max-width: 960px;">
  <tr>
    <td valign="top" width="50%">
      <fieldset>
        <legend><?php echo TEXT_REPORTS_ON_DASHBOARD ?></legend>
<div class="cfg_listing">        
  <ul id="reports_on_dashboard" class="sortable sortable-reports">
  <?php
  $reports_query = db_query("select r.*,e.name as entities_name from app_reports r, app_entities e where e.id=r.entities_id and r.created_by='" . db_input($app_logged_users_id) . "' and r.reports_type='standard' and r.in_dashboard=1 order by r.dashboard_sort_order, r.name");
  while($v = db_fetch_array($reports_query))
  {
    echo '<li id="report_' . $v['id'] . '"><div>' . $v['name'] . '</div></li>';
  }
  ?> 
  </ul>         
</div>
              
      </fieldset>
    
    </td>
    <td style="padding-left: 25px;" valign="top">
    
      <fieldset>
        <legend><?php echo TEXT_MY_REPORTS ?></legend>
<div class="cfg_listing">        
<ul id="reports_excluded_from_dashboard" class="sortable sortable-reports">
<?php
  $reports_query = db_query("select r.*,e.name as entities_name from app_reports r, app_entities e where e.id=r.entities_id and r.created_by='" . db_input($app_logged_users_id) . "' and r.reports_type='standard' and r.in_dashboard!=1 order by e.name, r.name");
  while($v = db_fetch_array($reports_query))
  {
    echo '<li id="report_' . $v['id'] . '"><div>' . $v['name'] . '</div></li>';
  }
?> 
</ul>
</div>                     
      </fieldset>
      
      
    </td>
  </tr>
</table>

  </div>
  
  <div class="tab-pane" id="form_tab_reports_sections">
  	<div><?php echo TEXT_CONFIGURE_DASHBOARD_SECTION_INFO ?></div><br>
  	<div style="margin-bottom: 15px;">
            <div class="btn-group open">
                <button type="button" class="btn btn-primary btn-add-reports-section" data-columns="2"><?php echo TEXT_ADD_SECTION ?></button>
                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown"><i class="fa fa-angle-down"></i></button>
                <ul class="dropdown-menu" role="menu">
                    <li>
                            <a href="#" class="btn-add-reports-section" data-columns="1"><?php echo TEXT_ONE_COLUMN ?></a>
                    </li>
                    <li>
                            <a href="#" class="btn-add-reports-section" data-columns="2"><?php echo TEXT_TWO_COLUMNS ?></a>
                    </li>                    
                </ul>
            </div>            
        </div>
  	<div id="reports_sections_list"></div>
  </div>
  
  <div class="tab-pane" id="form_tab_reports_counter">
  
<div><?php echo TEXT_CONFIGURE_DASHBOARD_INFO ?></div><br>

<table style="width: 100%; max-width: 960px;">
  <tr>
    <td valign="top" width="50%">
      <fieldset>
        <legend><?php echo TEXT_REPORTS_ON_DASHBOARD ?></legend>
<div class="cfg_listing">        
  <ul id="reports_counter_on_dashboard" class="sortable sortable-reports-counter">
  <?php
  $reports_query = db_query("select r.*,e.name as entities_name from app_reports r, app_entities e where e.id=r.entities_id and r.created_by='" . db_input($app_logged_users_id) . "' and r.reports_type='standard' and r.in_dashboard_counter=1 order by r.dashboard_counter_sort_order, r.name");
  while($v = db_fetch_array($reports_query))
  {
    echo '<li id="report_' . $v['id'] . '"><div>' . $v['name'] . '</div></li>';
  }
  ?> 
  </ul>         
</div>
              
      </fieldset>
    
    </td>
    <td style="padding-left: 25px;" valign="top">
    
      <fieldset>
        <legend><?php echo TEXT_MY_REPORTS ?></legend>
<div class="cfg_listing">        
<ul id="reports_counter_excluded_from_dashboard" class="sortable sortable-reports-counter">
<?php
  $reports_query = db_query("select r.*,e.name as entities_name from app_reports r, app_entities e where e.id=r.entities_id and r.created_by='" . db_input($app_logged_users_id) . "' and r.reports_type='standard' and r.in_dashboard_counter!=1 order by e.name, r.name");
  while($v = db_fetch_array($reports_query))
  {
    echo '<li id="report_' . $v['id'] . '"><div>' . $v['name'] . '</div></li>';
  }
?> 
</ul>
</div>                     
      </fieldset>
      
      
    </td>
  </tr>
</table>

  </div>
  
  
  
<div class="tab-pane" id="form_tab_reports_header">
  
	<div><?php echo TEXT_CONFIGURE_HOT_REPORTS_INFO ?></div><br>

	<table style="width: 100%; max-width: 960px;">
	  <tr>
	    <td valign="top" width="50%">
	      <fieldset>
	        <legend><?php echo TEXT_DISPLAY_IN_HEADER ?></legend>
	<div class="cfg_listing">        
	  <ul id="reports_in_header" class="sortable sortable-reports-header">
	  <?php
	  $reports_query = db_query("select r.*,e.name as entities_name from app_reports r, app_entities e where e.id=r.entities_id and r.created_by='" . db_input($app_logged_users_id) . "' and r.reports_type='standard' and r.in_header=1 order by r.header_sort_order, r.name");
	  while($v = db_fetch_array($reports_query))
	  {
	    echo '<li id="report_' . $v['id'] . '"><div>' . $v['name'] . '</div></li>';
	  }
	  ?> 
	  </ul>         
	</div>
	              
	      </fieldset>
	    
	    </td>
	    <td style="padding-left: 25px;" valign="top">
	    
	      <fieldset>
	        <legend><?php echo TEXT_MY_REPORTS ?></legend>
	<div class="cfg_listing">        
	<ul id="reports_excluded_in_header" class="sortable sortable-reports-header">
	<?php
	  $reports_query = db_query("select r.*,e.name as entities_name from app_reports r, app_entities e where e.id=r.entities_id and r.created_by='" . db_input($app_logged_users_id) . "' and r.reports_type='standard' and r.in_header!=1 order by e.name, r.name");
	  while($v = db_fetch_array($reports_query))
	  {
	    echo '<li id="report_' . $v['id'] . '"><div>' . $v['name'] . '</div></li>';
	  }
	?> 
	</ul>
	</div>                     
	      </fieldset>
	      
	      
	    </td>
	  </tr>
	</table>

</div>     
  
  
  
<?php if($common_reports_count>0): ?>  
  <div class="tab-pane" id="form_tab_common_reports">
    <p><?php echo TEXT_EXT_COMMON_REPORTS_DASHBOARD_DESCRIPTION ?></p>
<?php
  $common_reports_list = array();
  $reports_query = db_query("select r.* from app_reports r, app_entities e, app_entities_access ea  where r.entities_id = e.id and e.id=ea.entities_id and length(ea.access_schema)>0 and ea.access_groups_id='" . db_input($app_user['group_id']) . "' and (find_in_set(" . $app_user['group_id'] . ",r.users_groups) or find_in_set(" . $app_user['id'] . ",r.assigned_to)) and r.reports_type = 'common' order by r.dashboard_sort_order, r.name");
  while($reports = db_fetch_array($reports_query))
  {
    $common_reports_list[$reports['id']] = $reports['name']; 
  }
  
  echo select_checkboxes_tag('hidden_common_reports',$common_reports_list,$app_users_cfg->get('hidden_common_reports'));
?>  
  
  </div>
<?php endif ?>  

  </div>
</div>

<?php echo ajax_modal_template_footer() ?>

</form> 

<script>
  $(function() {      
       
    	$( "ul.sortable-reports" ).sortable({
    		connectWith: "ul",
    		update: function(event,ui){  
          data = '';  
          $( "ul.sortable" ).each(function() {data = data +'&'+$(this).attr('id')+'='+$(this).sortable("toArray") });                            
          data = data.slice(1)                      
          $.ajax({type: "POST",url: '<?php echo url_for("dashboard/","action=sort_reports")?>',data: data});
        }
    	});
             
	  	$( "ul.sortable-reports-counter" ).sortable({
	  		connectWith: "ul",
	  		update: function(event,ui){  
	        data = '';  
	        $( "ul.sortable" ).each(function() {data = data +'&'+$(this).attr('id')+'='+$(this).sortable("toArray") });                            
	        data = data.slice(1)                      
	        $.ajax({type: "POST",url: '<?php echo url_for("dashboard/","action=sort_reports_countr")?>',data: data});
	      }
	  	});  

	  	$( "ul.sortable-reports-header" ).sortable({
	  		connectWith: "ul",
	  		update: function(event,ui){  
	        data = '';  
	        $( "ul.sortable" ).each(function() {data = data +'&'+$(this).attr('id')+'='+$(this).sortable("toArray") });                            
	        data = data.slice(1)                      
	        $.ajax({type: "POST",url: '<?php echo url_for("dashboard/","action=sort_reports_header")?>',data: data});
	      }
	  	});  

	  	//handle sections
	  	$('#reports_sections_list').load("<?php echo url_for('dashboard/reports','action=get_sections') ?>")
	  	
	  	$('.btn-add-reports-section').click(function(){
			$('#reports_sections_list').load("<?php echo url_for('dashboard/reports','action=add_section') ?>",{columns: $(this).attr('data-columns')})
		})		  
	  	 
	});  

  function reports_section_delete(section_id)
  {
  	$('#section_panel_'+section_id).fadeOut();
  	$.ajax({type: "POST",url: '<?php echo url_for("dashboard/reports","action=delete_section")?>',data: {section_id:section_id}});
  }

  function reports_section_edit(section_id,type,value)
  {
  	$.ajax({type: "POST",url: '<?php echo url_for("dashboard/reports","action=edit_section")?>',data: {section_id:section_id,type:type,value:value}}).done(function(msg){
			if(msg.length>0)
			{
				alert(msg)
				$('#'+type+'_section'+section_id).val('');
			}
  	});;
  }
	  
</script>