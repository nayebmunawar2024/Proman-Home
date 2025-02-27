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


<div class="row">
	<div class="col-md-12">

<?= count($app_breadcrumb)>1 ? '<ul class="page-breadcrumb breadcrumb noprint">' . items::render_breadcrumb($app_breadcrumb) . '</ul>':'' ?>              
  
<?php if(count($navbar = items::build_menu())>1): ?>
  <div class="navbar navbar-default navbar-items" role="navigation">
  	<!-- Brand and toggle get grouped for better mobile display -->
  	<div class="navbar-header">
  		<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
    		<span class="sr-only"></span>
    		<span class="fa fa-bar "></span>
    		<span class="fa fa-bar fa-align-justify"></span>
    		<span class="fa fa-bar"></span>
  		</button>
  		<?php
  		  if(isset($navbar[0]['url']))
  		  {
  		  	echo '<a class="navbar-brand ' .  ($navbar[0]['selected_id'] == $current_entity_id ? 'selected':'') . '" href="' . $navbar[0]['url'] . '">' . $navbar[0]['title'] . '</a>';
  		  }
  		  else
  		  {
  		  	echo '<a class="navbar-brand ' .  ($navbar[0]['selected_id'] == $current_entity_id ? 'selected':'') . '" href="#" onClick="return false">' . $navbar[0]['title'] . '</a>';
  		  }
  		?>
  	</div>
  	<!-- Collect the nav links, forms, and other content for toggling -->
  	<div class="collapse navbar-collapse navbar-ex1-collapse">
    
       <?php
        unset($navbar[0]);
         
        echo renderNavbarMenu($navbar,'',0,$current_entity_id); 
       ?>
       
  	</div>
  	<!-- /.navbar-collapse -->
  </div>
<?php endif ?>  
  
 
  <?php 
  	if($current_item_id==0)
  	{
  		$help_pages = new help_pages($current_entity_id);
  		
  		
  		$title = $app_breadcrumb[count($app_breadcrumb)-1]['title'] . $help_pages->render_icon('listing');
  		
  		if(is_ext_installed())
  		{  			
  			$common_filters = new common_filters($current_entity_id,$reports_info['id']);
  			$common_filters->parent_item_id = $parent_entity_item_id;
  			echo $common_filters->render($title);
  		}
  		else
  		{	  		
                    echo '<h3 class="page-title">' . $title . '</h3>';
  		}
  		
    	echo $help_pages->render_announcements();
  		 	
    } 
   ?>    

  </div>
</div>




