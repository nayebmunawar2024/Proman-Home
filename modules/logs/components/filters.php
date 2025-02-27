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
<form class="form-inline" role="form" id="log_filters">
    
    <div class="form-group">
        <a href="#" title="<?php echo TEXT_REFRESH ?>" class="btn btn-default btn-refresh"><i class="fa fa-refresh"></i></a>
    </div>

    <?php if(in_array($log_type,['mysql','email']) ): ?>    
    <div class="form-group">
        <div class="input-group">
            <span class="input-group-addon"><i class="fa fa-database"></i></span>
            <?php echo select_tag('sql_errors', [''=>TEXT_ALL,'1'=>TEXT_ERRORS],'', array('class' => 'form-control input-small','type'=>'search')) ?>			
        </div>
    </div>    
    <?php endif ?>
    
    <div class="form-group">

        <div class="input-group input-xlarge datepicker1 input-daterange1 daterange-filter">					
            <span class="input-group-addon">
                <i class="fa fa-calendar"></i>
            </span>
            <?php echo input_tag('from', '', array('class' => 'form-control xdatetimepicker', 'data-settings'=>'{"format":"Y-m-d H:i"}','placeholder' => TEXT_DATE_FROM,'autocomplete'=>'off')) ?>
            <span class="input-group-addon">
                <i style="cursor:pointer" class="fa fa-refresh" aria-hidden="true" title="<?php echo TEXT_RESET ?>" onClick="reset_date_rane_filter('daterange-filter')"></i>
            </span>
            <?php echo input_tag('to', '', array('class' => 'form-control xdatetimepicker', 'data-settings'=>'{"format":"Y-m-d H:i"}','placeholder' => TEXT_DATE_TO,'autocomplete'=>'off')) ?>			
        </div>		
    </div>
    
    <div class="form-group">
        <div class="input-group">
            <span class="input-group-addon"><i class="fa fa-search"></i></span>
            <?php echo input_tag('search', '', array('class' => 'form-control', 'placeholder' => TEXT_SEARCH,'type'=>'search')) ?>			
        </div>
    </div>
    
    <div class="form-group">
        <?php echo submit_tag(TEXT_APPLY) ?>
    </div>
    
    <div class="form-group" style="float: right">
        <?php echo link_to('<i class="fa fa-trash-o"></i> ' . TEXT_CLEAR,url_for('logs/view','type=' . $log_type . '&action=reset'),['class'=>'btn btn-default btn-reset-log','confirm'=>TEXT_ARE_YOU_SURE]) ?>
    </div>

</form>

