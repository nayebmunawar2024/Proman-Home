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
$entities_info = db_find('app_entities', $_GET['entities_id']);
$entities_cfg = entities::get_cfg($_GET['entities_id']);

$breadcrumb = array();

$breadcrumb[] = '<li>' . link_to(TEXT_MENU_ENTITIES_LIST, url_for('entities/entities')) . '<i class="fa fa-angle-right"></i></li>';

//get paretns
if(count($parents = entities::get_parents($_GET['entities_id'])) > 0)
{
    krsort($parents);

    foreach($parents as $id)
    {
        $parent_entity_info = db_find('app_entities', $id);
        $breadcrumb[] = '<li>' . link_to($parent_entity_info['name'], url_for('entities/entities_configuration', 'entities_id=' . $id)) . '<i class="fa fa-angle-right"></i></li>';
    }
}

$breadcrumb[] = '<li>' . link_to($entities_info['name'], url_for('entities/entities_configuration', 'entities_id=' . $_GET['entities_id'])) . '</li>';


?>

<ul class="page-breadcrumb breadcrumb">
<?php echo implode('', $breadcrumb) ?>  
</ul>

<div class="navbar navbar-default" role="navigation">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
            <span class="sr-only"></span>
            <span class="fa fa-bar "></span>
            <span class="fa fa-bar fa-align-justify"></span>
            <span class="fa fa-bar"></span>
        </button>
        
            
        
        <a class="navbar-brand " href="<?php echo url_for('entities/entities_configuration&entities_id=' . $_GET['entities_id']) ?>"><?php echo $entities_info['name'] ?></a>
    </div>
    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse navbar-ex1-collapse">
        <ul class="nav navbar-nav">
            
            <?php echo entities::render_goto_menu($_GET['entities_id']) ?>
            
            <li class="nav_entities_configuration">
                <?php echo link_to(TEXT_NAV_GENERAL_CONFIG, url_for('entities/entities_configuration&entities_id=' . $_GET['entities_id'])) ?>
            </li>
            <li class="nav_fields nav_fields_choices">
                <?php echo link_to(TEXT_NAV_FIELDS_CONFIG, url_for('entities/fields&entities_id=' . $_GET['entities_id'])) ?>
            </li>
            <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-hover="dropdown" data-toggle="dropdown"><?php echo TEXT_NAV_VIEW_CONFIG ?> <i class="fa fa-angle-down"></i></a>
                <ul class="dropdown-menu">
                    <li>
                        <?php echo link_to(TEXT_NAV_FORM_CONFIG, url_for('entities/forms', 'entities_id=' . $_GET['entities_id'])) ?>
                    </li>
                    <li>
                        <?php echo link_to(TEXT_NAV_FORMS_FIELDS_DISPLAY_RULES, url_for('forms_fields_rules/rules', 'entities_id=' . $_GET['entities_id'])) ?>
                    </li>
                    <li>
                        <?php echo link_to(TEXT_COMPOSITE_UNIQUE_FIELDS, url_for('composite_unique_fields/rules', 'entities_id=' . $_GET['entities_id'])) ?>
                    </li>
                    <li>
                        <?php echo link_to(TEXT_NAV_LISTING_CONFIG, url_for('entities/listing_types', 'entities_id=' . $_GET['entities_id'])) ?>
                    </li>
                    <li>
                        <?php echo link_to(TEXT_FILTERS_PANELS, url_for('filters_panels/panels', 'entities_id=' . $_GET['entities_id'])) ?>
                    </li>					
                    <li>
                        <?php echo link_to(TEXT_NAV_ITEM_PAGE_CONFIG, url_for('entities/item_page_configuration', 'entities_id=' . $_GET['entities_id'])) ?>
                    </li>
                    
                    <?php
                        if(entities::has_subentities($_GET['entities_id']))
                        {
                            echo '
                                <li>' . link_to(TEXT_NESTED_ENTITIES_MENU, url_for('nested_entities_menu/menu', 'entities_id=' . $_GET['entities_id'])). '</li>    
                                ';
                        }
                    ?>

                        <?php if($_GET['entities_id'] == 1): ?>
                        <li>
                        <?php echo link_to(TEXT_NAV_USER_PUBLIC_PROFILE_CONFIG, url_for('entities/user_public_profile', 'entities_id=' . $_GET['entities_id'])) ?>
                        </li>
                        <?php endif ?>
                </ul>
            </li>
            <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-hover="dropdown" data-toggle="dropdown"><?php echo TEXT_NAV_ACCESS_CONFIG ?> <i class="fa fa-angle-down"></i></a>
                <ul class="dropdown-menu">
                    <li>
                        <?php echo link_to(TEXT_NAV_ENTITY_ACCESS, url_for('entities/access', 'entities_id=' . $_GET['entities_id'])) ?>
                    </li>
                    <li>
                        <?php echo link_to(TEXT_NAV_FIELDS_ACCESS, url_for('entities/fields_access', 'entities_id=' . $_GET['entities_id'])) ?>
                    </li>
                    <li>
                        <?php echo link_to(TEXT_NAV_ACCESS_RULES, url_for('access_rules/fields', 'entities_id=' . $_GET['entities_id'])) ?>
                    </li>
                    <li>
                        <?php echo link_to(TEXT_RECORDS_VISIBILITY, url_for('records_visibility/rules', 'entities_id=' . $_GET['entities_id'])) ?>
                    </li>
                </ul>
            </li>
            <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-hover="dropdown" data-toggle="dropdown"><?php echo TEXT_NAV_COMMENTS_CONFIG ?> <i class="fa fa-angle-down"></i></a>
                <ul class="dropdown-menu">
                    <li>
                        <?php echo link_to(TEXT_NAV_COMMENTS_ACCESS, url_for('entities/comments_access', 'entities_id=' . $_GET['entities_id'])) ?>
                    </li>
                    <li>
                        <?php echo link_to(TEXT_NAV_COMMENTS_FIELDS, url_for('entities/comments_form', 'entities_id=' . $_GET['entities_id'])) ?>
                    </li>
                </ul>
            </li>

<?php
$choices = [];

$choices[] = ['title' => TEXT_HELP_SYSTEM, 'url' => url_for('help_pages/pages', 'entities_id=' . _get::int('entities_id'))];

if(is_ext_installed())
{
    $choices[] = ['title' => TEXT_EXT_EMAIL_SENDING_RULES, 'url' => url_for('ext/email_sending/rules', 'entities_id=' . _get::int('entities_id'))];
    $choices[] = ['title' => TEXT_EXT_EMAIL_NTOFICATION, 'url' => url_for('ext/email_notification/rules', 'entities_id=' . _get::int('entities_id'))];
    $choices[] = ['title' => TEXT_EXT_SMS_SENDIGN_RULES, 'url' => url_for('ext/modules/sms_rules', 'action=set_entity_filter&entities_id=' . _get::int('entities_id'))];
    $choices[] = ['title' => TEXT_EXT_PROCESSES, 'url' => url_for('ext/processes/processes', 'action=set_entity_filter&entities_id=' . _get::int('entities_id'))];
}

$html = '';
if(count($choices))
{
    $html .= '
  		<li class="dropdown">
				<a href="#" class="dropdown-toggle" data-hover="dropdown" data-toggle="dropdown">' . TEXT_EXTRA . ' <i class="fa fa-angle-down"></i></a>
					<ul class="dropdown-menu">';

    foreach($choices as $v)
    {
        $html .= '<li>' . link_to($v['title'], $v['url']) . '</li>';
    }

    $html .= '
  		</ul>
		</li>';
}

echo $html;
?>			
        </ul>

    </div>
    <!-- /.navbar-collapse -->
</div>

<script>
    $(function ()
    {
        $('.nav_<?php echo $app_action ?>').addClass('active');
        
        $('.nav_entities_goto').click(function(){
            if(!$(this).hasClass('tree-table-menu-active'))
            {
                $(this).addClass('tree-table-menu-active')
                
                if(app_language_text_direction=='rtl')
                {
                    $('.nav_entities_goto .dropdown-menu li').css('text-align','right')
                }
                
                setTimeout(function(){
                   $('.tree-table-menu').treetable()
                },100)
            }
            
        })
    });

</script>   