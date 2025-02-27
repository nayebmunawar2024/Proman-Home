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

<h3 class="page-title"><?php echo TEXT_ENTITIES_HEADING ?></h3>
<p><?php echo TEXT_ENTITIES_PAGE_INFO ?></p>

<div class="row">
    <div class="col-md-9" style="padding-bottom: 5px;">
        <?php
        echo button_tag(TEXT_ADD_NEW_ENTITY, url_for('entities/entities_form')) . ' ' .
        button_tag('<i class="fa fa-sort-amount-asc"></i> ' . TEXT_SORT, url_for('entities/entities_sort'), true, array('class' => 'btn btn-default')) . ' ' .
        button_tag('<i class="fa fa-list"></i> ' . TEXT_ENTITIES_GROUPS, url_for('entities/entities_groups'), false, array('class' => 'btn btn-default')) . ' ' .
        button_tag('<i class="fa fa-sitemap"></i> ' . TEXT_FLOWCHART, url_for('entities/entities_flowchart'), false, array('class' => 'btn btn-default')) . ' ' .
        button_tag('<i class="fa fa-exchange"></i> ' . TEXT_CHANGE_STRUCTURE, url_for('entities/entities_change_structure'), true, array('class' => 'btn btn-default'))
        ?>

    </div>
    <div class="col-md-3">
        <?php
        $choices = entities_groups::get_choices();

        if(count($choices))
        {
            $html = form_tag('entities_filter_form', url_for('entities/entities', 'action=set_entities_filter')) .
                    select_tag('entities_filter', entities_groups::get_choices(), $entities_filter, array('class' => 'form-control  ', 'onChange' => 'this.form.submit()')) .
                    '</form>';
            echo $html;
        }
        ?>
    </div>
</div> 

<div class="table-scrollable" style="overflow-x:visible;overflow-y:visible; ">
    <table class="tree-table table table-striped table-bordered table-hover">
        <thead>
            <tr>
                <th><?php echo TEXT_ACTION ?></th>
                <th><?php echo TEXT_ID ?></th>
                <th><?php echo TEXT_GROUP ?></th>
                <th width="100%"><?php echo TEXT_NAME ?></th>
                <th><?php echo TEXT_NOTE ?></th>
                <th><?php echo TEXT_SORT_ORDER ?></th>    
            </tr>
        </thead>
        <tbody>
<?php if(count($entities_list) == 0) echo '<tr><td colspan="4">' . TEXT_NO_RECORDS_FOUND . '</td></tr>'; ?>
                    <?php foreach($entities_list as $v): ?>
                <tr>
                    <td style="white-space: nowrap;"><?php
                        echo button_icon_delete(url_for('entities/entities_delete', 'id=' . $v['id'])) . ' ' .
                        button_icon_edit(url_for('entities/entities_form', 'id=' . $v['id'])) . ' ' .
                        button_icon(TEXT_CREATE_SUB_ENTITY, 'fa fa-plus', url_for('entities/entities_form', 'parent_id=' . $v['id'])) . ' ' .
                        (entities::has_subentities($v['id']) > 1 ? button_icon(TEXT_SORT, 'fa fa-sort-amount-asc', url_for('entities/entities_sort', 'parent_id=' . $v['id'])) : '')
                        ?></td>
                    <td><?php echo $v['id'] ?></td>
                    <td><?php echo entities_groups::get_name_by_id($v['group_id']) ?></td>
                    <td style="white-space: nowrap">

                        <?php echo '<div class="tt" data-tt-id="entity_' . $v['id'] . '" ' . ($v['parent_id'] > 0 ? 'data-tt-parent="entity_' . $v['parent_id'] . '"' : '') . '></div>' ?>

    <?php //echo  str_repeat('&nbsp;<i class="fa fa-minus" aria-hidden="true"></i>&nbsp;', $v['level'])  ?>

                        <div class="btn-group">
                            <button type="button" type="button" class="btn btn-default" onClick="location.href = '<?php echo url_for('entities/entities_configuration', 'entities_id=' . $v['id']) ?>'"><?php echo $v['name'] ?></button>
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" data-hover="dropdown"><i class="fa fa-angle-down"></i></button>						
                            <ul class="dropdown-menu" role="menu">
                                <li><?php echo link_to(TEXT_NAV_GENERAL_CONFIG, url_for('entities/entities_configuration&entities_id=' . $v['id'])) ?></li>
                                <li><?php echo link_to(TEXT_NAV_FIELDS_CONFIG, url_for('entities/fields&entities_id=' . $v['id'])) ?></li>
                                <li class="dropdown-submenu">
                                    <a href="#"><?php echo TEXT_NAV_VIEW_CONFIG ?></a>
                                    <ul class="dropdown-menu">
                                        <li><?php echo link_to(TEXT_NAV_FORM_CONFIG, url_for('entities/forms', 'entities_id=' . $v['id'])) ?></li>
                                        <li><?php echo link_to(TEXT_NAV_FORMS_FIELDS_DISPLAY_RULES, url_for('forms_fields_rules/rules', 'entities_id=' . $v['id'])) ?></li>
                                        <li><?php echo link_to(TEXT_COMPOSITE_UNIQUE_FIELDS, url_for('composite_unique_fields/rules', 'entities_id=' . $v['id'])) ?></li>
                                        <li><?php echo link_to(TEXT_NAV_LISTING_CONFIG, url_for('entities/listing_types', 'entities_id=' . $v['id'])) ?></li>
                                        <li><?php echo link_to(TEXT_FILTERS_PANELS, url_for('filters_panels/panels', 'entities_id=' . $v['id'])) ?></li>
                                        <li><?php echo link_to(TEXT_NAV_ITEM_PAGE_CONFIG, url_for('entities/item_page_configuration', 'entities_id=' . $v['id'])) ?></li>	

                                        <?php
                                            if(entities::has_subentities($v['id']))
                                            {
                                                echo '
                                                    <li>' . link_to(TEXT_NESTED_ENTITIES_MENU, url_for('nested_entities_menu/menu', 'entities_id=' . $v['id'])). '</li>    
                                                    ';
                                            }
                                        ?>  
                                        
                                        <?php if($v['id'] == 1): ?>
                                            <li><?php echo link_to(TEXT_NAV_USER_PUBLIC_PROFILE_CONFIG, url_for('entities/user_public_profile', 'entities_id=' . $v['id'])) ?></li>
                                        <?php endif ?>
                                    </ul>
                                </li>
                                <li class="dropdown-submenu">
                                    <a href="#"><?php echo TEXT_NAV_ACCESS_CONFIG ?></a>
                                    <ul class="dropdown-menu">
                                        <li><?php echo link_to(TEXT_NAV_ENTITY_ACCESS, url_for('entities/access', 'entities_id=' . $v['id'])) ?></li>
                                        <li><?php echo link_to(TEXT_NAV_FIELDS_ACCESS, url_for('entities/fields_access', 'entities_id=' . $v['id'])) ?></li>
                                        <li><?php echo link_to(TEXT_NAV_ACCESS_RULES, url_for('access_rules/fields', 'entities_id=' . $v['id'])) ?></li>
                                        <li><?php echo link_to(TEXT_RECORDS_VISIBILITY, url_for('records_visibility/rules', 'entities_id=' . $v['id'])) ?></li>
                                    </ul>
                                </li>
                                <li class="dropdown-submenu">
                                    <a href="#"><?php echo TEXT_NAV_COMMENTS_CONFIG ?></a>
                                    <ul class="dropdown-menu">
                                        <li><?php echo link_to(TEXT_NAV_COMMENTS_ACCESS, url_for('entities/comments_access', 'entities_id=' . $v['id'])) ?></li>
                                        <li><?php echo link_to(TEXT_NAV_COMMENTS_FIELDS, url_for('entities/comments_form', 'entities_id=' . $v['id'])) ?></li>
                                    </ul>
                                </li>

                                <?php
                                $choices = [];

                                $choices[] = ['title' => TEXT_HELP_SYSTEM, 'url' => url_for('help_pages/pages', 'entities_id=' . $v['id'])];

                                if(is_ext_installed())
                                {
                                    $choices[] = ['title' => TEXT_EXT_EMAIL_SENDING_RULES, 'url' => url_for('ext/email_sending/rules', 'entities_id=' . $v['id'])];
                                    $choices[] = ['title' => TEXT_EXT_EMAIL_NTOFICATION, 'url' => url_for('ext/email_notification/rules', 'entities_id=' . $v['id'])];
                                    $choices[] = ['title' => TEXT_EXT_PROCESSES, 'url' => url_for('ext/processes/processes', 'action=set_entity_filter&entities_id=' . $v['id'])];
                                }

                                $html = '';
                                if(count($choices))
                                {
                                    $html .= '
  		<li class="dropdown-submenu">
				<a href="#">' . TEXT_EXTRA . '</a>
					<ul class="dropdown-menu">';

                                    foreach($choices as $menu)
                                    {
                                        $html .= '<li>' . link_to($menu['title'], $menu['url']) . '</li>';
                                    }

                                    $html .= '
  		</ul>
		</li>';
                                }

                                echo $html;
                                ?>				
                            </ul>						
                        </div>

                    </td>
                    <td><?php echo tooltip_icon($v['notes'], 'left') ?></td>
                    <td><?php echo $v['sort_order'] ?></td>
                </tr>  
<?php endforeach ?>
        </tbody>
    </table>
</div>




