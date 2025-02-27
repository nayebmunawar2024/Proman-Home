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
class reports_sections
{

    public $reports_groups_id, $is_common;

    function __construct($reports_groups_id, $is_common)
    {
        $this->reports_groups_id = $reports_groups_id;
        $this->is_common = $is_common;
    }

    function render()
    {
        global $app_user;

        $html = '<ul id="section_panel" class="sortable-simple">';
        $sections_query = db_query("select * from app_reports_sections where reports_groups_id='" . db_input($this->reports_groups_id) . "' and created_by='" . db_input($app_user['id']) . "' order by sort_order");
        while($sections = db_fetch_array($sections_query))
        {
            $html .= '
				<li id="section_panel_' . $sections['id'] . '">
					<div class="panel panel-default" >						
						<div class="panel-body">
							 <table width="100%">
								 <tr>
									 <td width="' . ($sections['count_columns'] == 2 ? '45%' : '90%') . '" style="border-right: 1px solid #ddd; text-align: center; padding-right: 15px;">' . $this->get_reports_choices($sections, 'report_left') . '</td>
									 ' . ($sections['count_columns'] == 2 ? '<td width="45%" style="text-align: center; padding-left: 15px;">' . $this->get_reports_choices($sections, 'report_right') . '</td>' : '') . '
									 <td align="right"><a title="' . addslashes(TEXT_DELETE) . '" class="btn btn-default btn-xs purple" onClick="reports_section_delete(' . $sections['id'] . ')" href="#"><i class="fa fa-trash-o"></i></a></td>
					       </tr>
							 </table>
						</div>
					</div>
				</li>
					';
        }

        $html .= '
				</ul>
				
				
				<script>
				  $(function() {      
				       
				    	$( "ul.sortable-simple" ).sortable({
				    		connectWith: "ul",
				    		update: function(event,ui){  
				          data = "";  
				          $( "ul.sortable-simple" ).each(function() {data = data +"&"+$(this).attr("id")+"="+$(this).sortable("toArray") });                            
				          data = data.slice(1)                      
				          $.ajax({type: "POST",url: "' . url_for("dashboard/reports", "action=sort_sections&id=" . $this->reports_groups_id) . '",data: data});
				        }
				    	});
					});
   			</script>
				';

        return $html;
    }

    function get_reports_choices($sections, $type)
    {
        global $app_user;

        $html = '';
        $choices = array('' => '');

        if($this->is_common)
        {
            $reports_query = db_query("select id, name from app_reports where  reports_type in ('common') order by name");
            while($v = db_fetch_array($reports_query))
            {
                $choices[TEXT_EXT_COMMON_REPORTS]['common' . $v['id']] = $v['name'];
            }
        }
        else
        {
            $reports_query = db_query("select id, name from app_reports where created_by='" . db_input($app_user['id']) . "' and reports_type in ('standard') order by name");
            while($v = db_fetch_array($reports_query))
            {
                $choices[TEXT_STANDARD_REPORTS]['standard' . $v['id']] = $v['name'];
            }
            
            //include common reports
            if(is_ext_installed())
            {
                $reports_query = db_query("select id, name from app_reports where  reports_type in ('common') and (find_in_set('" . $app_user['group_id'] . "',users_groups) or find_in_set('" . $app_user['id'] . "',assigned_to)) order by name");
                while($v = db_fetch_array($reports_query))
                {
                    $choices[TEXT_EXT_COMMON_REPORTS]['common' . $v['id']] = $v['name'];
                }
            }
        }

        if(is_ext_installed())
        {

            if(calendar::user_has_personal_access())
            {
                $choices[TEXT_EXT_СALENDAR]['calendar_personal'] = TEXT_EXT_СALENDAR_PERSONAL;
            }

            if(calendar::user_has_public_access())
            {
                $choices[TEXT_EXT_СALENDAR]['calendar_public'] = TEXT_EXT_СALENDAR_PUBLIC;
            }


            //calendar preport
            if($app_user['group_id'] > 0)
            {
                $reports_query = db_query("select c.* from app_ext_calendar c, app_entities e, app_ext_calendar_access ca where e.id=c.entities_id and c.id=ca.calendar_id and ca.access_groups_id='" . db_input($app_user['group_id']) . "' order by c.name");
            }
            else
            {
                $reports_query = db_query("select c.* from app_ext_calendar c, app_entities e where e.id=c.entities_id order by c.name");
            }
            while($v = db_fetch_array($reports_query))
            {
                $choices[TEXT_EXT_СALENDAR]['calendarreport' . $v['id']] = $v['name'];
            }

            //pivot calendar preport			
            $reports_query = db_query("select id, name, users_groups from app_ext_pivot_calendars order by name");
            while($reports = db_fetch_array($reports_query))
            {
                if(pivot_calendars::has_access($reports['users_groups']))
                {
                    $choices[TEXT_EXT_PIVOT_СALENDAR]['pivot_calendars' . $reports['id']] = $reports['name'];
                }
            }

            //graphic			
            $reports_query = db_query("select id, name, allowed_groups from app_ext_graphicreport order by name");
            while($v = db_fetch_array($reports_query))
            {
                if(in_array($app_user['group_id'], explode(',', $v['allowed_groups'])) or $app_user['group_id'] == 0)
                {
                    $choices[TEXT_EXT_GRAPHIC_REPORT]['graphicreport' . $v['id']] = $v['name'];
                }
            }

            //funnel
            $reports_query = db_query("select id, name, users_groups from app_ext_funnelchart order by name");
            while($v = db_fetch_array($reports_query))
            {
                if(in_array($app_user['group_id'], explode(',', $v['users_groups'])) or $app_user['group_id'] == 0)
                {
                    $choices[TEXT_EXT_FUNNELCHART]['funnelchart' . $v['id']] = $v['name'];
                }
            }
            
            //kanban
            if($app_user['group_id']>0)
            {
                    $reports_query = db_query("select c.* from app_ext_kanban c, app_entities e where c.is_active=1 and e.id=c.entities_id and (find_in_set(" . $app_user['group_id'] . ",c.users_groups) or find_in_set(" . $app_user['id'] . ",c.assigned_to)) order by c.name");
            }
            else
            {
                    $reports_query = db_query("select c.* from app_ext_kanban c, app_entities e where c.is_active=1 and e.id=c.entities_id order by c.name");
            }

            while($v = db_fetch_array($reports_query))
            {
                $choices[TEXT_EXT_KANBAN]['kanban' . $v['id']] = $v['name'];
            }

            //pivot tables
            $reports_query = db_query("select * from app_ext_pivot_tables order by name");
            while($v = db_fetch_array($reports_query))
            {
                $pivot_table = new pivot_tables($v);

                if($pivot_table->has_access())
                {
                    $choices[TEXT_EXT_PIVOT_TABLES]['pivot_tables' . $v['id']] = $v['name'];
                }
            }

            //pivot
            $reports_query = db_query("select id, name, allowed_groups from app_ext_pivotreports order by name");
            while($v = db_fetch_array($reports_query))
            {
                if(in_array($app_user['group_id'], explode(',', $v['allowed_groups'])) or $app_user['group_id'] == 0)
                {
                    $choices[TEXT_EXT_PIVOTREPORTS]['pivotreports' . $v['id']] = $v['name'];
                }
            }

            //pivot tables
            $reports_query = db_query("select * from app_ext_resource_timeline order by name");
            while($v = db_fetch_array($reports_query))
            {
                if(resource_timeline::has_access($v['users_groups']))
                {
                    $choices[TEXT_EXT_RESOURCE_TIMELINE]['resource_timeline' . $v['id']] = $v['name'];
                }
            }

            //report designer
            $reports_query = db_query("select id, name from app_ext_report_page where entities_id=0  and is_active=1 and (find_in_set('" . $app_user['group_id'] . "',users_groups) or find_in_set('" . $app_user['id'] . "',assigned_to))");
            while($v = db_fetch_array($reports_query))
            {
                $choices[TEXT_EXT_REPORT_DESIGNER]['report_page' . $v['id']] = $v['name'];
            }
        }

        $html = select_tag($type . '_section' . $sections['id'], $choices, $sections[$type], array('class' => 'form-control', 'onChange' => 'reports_section_edit(' . $sections['id'] . ',\'' . $type . '\',this.value)'));

        return $html;
    }
}
