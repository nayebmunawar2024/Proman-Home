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

<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="<?php echo APP_LANGUAGE_SHORT_CODE ?>" dir="<?php echo APP_LANGUAGE_TEXT_DIRECTION ?>" class="no-js">
<!--<![endif]-->
<!-- BEGIN HEAD -->
<head>    
<meta charset="utf-8"/>
<meta name = "robots" content = "noindex,nofollow">
<title><?php echo $app_title ?></title>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta content="width=device-width, initial-scale=1, user-scalable=no" name="viewport"/>
<meta content="" name="description"/>
<?php echo app_author_text() ?>
<meta name="MobileOptimized" content="320">


<!-- BEGIN GLOBAL MANDATORY STYLES -->
<link href="template/plugins/font-awesome/css/font-awesome.min.css?v=4.7.0" rel="stylesheet" type="text/css"/>
<link href="template/plugins/line-awesome/css/line-awesome.min.css?v=1.3.0" rel="stylesheet" type="text/css"/>
<link href="template/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
<link href="template/plugins/uniform/css/uniform.default.css" rel="stylesheet" type="text/css"/>
<!-- END GLOBAL MANDATORY STYLES -->
<!-- BEGIN PAGE LEVEL STYLES -->
<link href="template/plugins/jquery-ui/jquery-ui-1.10.3.custom.min.css" rel="stylesheet"/>
<link href="template/plugins/bootstrap-modal/css/bootstrap-modal-bs3patch.css" rel="stylesheet" type="text/css"/>
<link href="template/plugins/bootstrap-modal/css/bootstrap-modal.css" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" type="text/css" href="template/plugins/bootstrap-datepicker/css/datepicker.css"/>
<link rel="stylesheet" type="text/css" href="template/plugins/bootstrap-datetimepicker-master/css/bootstrap-datetimepicker.css"/>
<!-- END PAGE LEVEL SCRIPTS -->
<!-- BEGIN THEME STYLES -->
<link href="template/css/style-conquer.css" rel="stylesheet" type="text/css"/>
<link href="template/css/style.css?v=2" rel="stylesheet" type="text/css"/>
<link href="template/css/style-responsive.css?v=2" rel="stylesheet" type="text/css"/>
<link href="template/css/plugins.css" rel="stylesheet" type="text/css"/>
<link href="js/uploadifive/uploadifive.css" rel="stylesheet" media="screen">
<link href="js/chosen/1.8.7/chosen.css" rel="stylesheet" media="screen">
<link rel="stylesheet" type="text/css" href="template/plugins/jquery-nestable/jquery.nestable.css"/>
<link rel="stylesheet" type="text/css" href="js/select2/4.1.0/css/select2.min.css" />
<link rel="stylesheet" type="text/css" href="js/xdsoft_datetimepicker/2.5.22/jquery.datetimepicker.css">
<link rel="stylesheet" type="text/css" href="template/plugins/ion.rangeSlider-master/2.3.1/css/ion.rangeSlider.min.css"/>

<?php require('js/mapbbcode-master/includes.css.php'); ?>

<link href="css/skins/<?php echo $app_skin ?>" rel="stylesheet" type="text/css" />

<script src="<?= CFG_PATH_TO_JQUERY ?>" type="text/javascript"></script>   

<script src="js/jquery.ui.touch-punch/jquery.ui.touch-punch.min.js" type="text/javascript"></script>

<script type="text/javascript" src="<?= CFG_PATH_TO_JQUERY_VALIDATION ?>"></script>
<script type="text/javascript" src="<?= CFG_PATH_TO_JQUERY_VALIDATION_METHODS ?>"></script>
<?php require('js/validation/validator_messages.php'); ?> 

<!--izoAutocomplete-->
<link rel="stylesheet" type="text/css" href="js/izoAutocomplete/1.0/izoAutocomplete.css"/>
<script type="text/javascript" src="js/izoAutocomplete/1.0/izoAutocomplete.js"></script>

<!-- Add fancyBox -->
<link rel="stylesheet" href="js/fancybox/2.1.7/jquery.fancybox.css" type="text/css" media="screen" />
<script type="text/javascript" src="js/fancybox/2.1.7/jquery.fancybox.pack.js"></script>

<script type="text/javascript" src="js/main.js?v=<?php echo PROJECT_VERSION ?>"></script>

<script type="text/javascript">      
  var CKEDITOR = false;
  var CKEDITOR_holders = new Array();
      
  var app_cfg_first_day_of_week = <?php echo CFG_APP_FIRST_DAY_OF_WEEK ?>;
  var app_language_short_code = '<?php echo APP_LANGUAGE_SHORT_CODE ?>';
  var app_cfg_ckeditor_images = '<?php echo url_for("dashboard/ckeditor_image")?>';
  var app_language_text_direction = '<?php echo APP_LANGUAGE_TEXT_DIRECTION ?>'
  var app_cfg_drop_down_menu_on_hover = <?php echo CFG_DROP_DOWN_MENU_ON_HOVER ?>;
  var app_ckeditor_contents_css = <?= (is_file('css/skins/' . $app_skin_dir . '/ckeditor.css') ? "'css/skins/{$app_skin_dir}/ckeditor.css'":"'template/plugins/ckeditor/4.21.0/contents.css'") ?>;

  function keep_session()
	{
	  $.ajax({url: '<?php echo url_for("dashboard/","action=keep_session") ?>'});
	}
	
	$(function(){
	   setInterval("keep_session()",600000);                                                                   
	}); 
      
</script>

<link rel="stylesheet" type="text/css" href="css/default.css?v=<?php echo PROJECT_VERSION ?>"/>
<?php echo app_include_custom_css() ?>

<?php echo render_login_page_background() ?>
<?php echo app_recaptcha::render_js() ?>

<script>
  if(isIframe())
  {    
    document.write('<link href="css/iframe.css" rel="stylesheet" type="text/css" />');   
  }
</script>

<!-- END THEME STYLES -->
<?php echo app_favicon() ?>
</head>
<!-- BEGIN BODY -->
<body class="login public-layout page-scale-reduced">

<div class="login-fade-in"></div>

<!-- BEGIN LOGO -->
<div class="login-page-logo">

<?php
  if(is_file(DIR_FS_UPLOADS  . '/' . CFG_APP_LOGO))
  {
    if(is_image(DIR_FS_UPLOADS  . '/' . CFG_APP_LOGO))
    {
      $html = '<img src="uploads/' . CFG_APP_LOGO .  '" border="0" title="' . CFG_APP_NAME . '">';
      
      if(strlen(CFG_APP_LOGO_URL)>0)
      {
        $html = '<a href="' . CFG_APP_LOGO_URL . '" target="_new">' . $html . '</a>';
      }
      
      echo $html;
    }
  }
  else
  {
    echo CFG_APP_NAME;
  }
?>
	
</div>
<!-- END LOGO -->
<!-- BEGIN LOGIN -->
<div class="content <?php echo 'content-' . $app_action ?>" >

<?php 
  //output alerts if they exists.
  echo $alerts->output(); 
        
//include module views  
  if(is_file($path = $app_plugin_path . 'modules/' . $app_module . '/views/' . $app_action . '.php'))
  {  	
    require($path);
  }   
?>


</div>
<!-- END LOGIN -->


<!-- BEGIN COPYRIGHT -->
<div class="copyright">
    <?= app_copyright_text() ?>
    <?php echo app_powered_by_text() ?>
</div>
<!-- END COPYRIGHT -->

<!-- BEGIN JAVASCRIPTS(Load javascripts at bottom, this will reduce page load time) -->
<!-- BEGIN CORE PLUGINS -->
<!--[if lt IE 9]>
<script src="template/plugins/respond.min.js"></script>
<script src="template/plugins/excanvas.min.js"></script> 
<![endif]-->
<script src="template/plugins/jquery-migrate-1.2.1.min.js" type="text/javascript"></script>
<!-- IMPORTANT! Load jquery-ui-1.10.3.custom.min.js before bootstrap.min.js to fix bootstrap tooltip conflict with jquery ui tooltip -->
<script src="template/plugins/jquery-ui/jquery-ui-1.10.3.custom.min.js" type="text/javascript"></script>
<script src="template/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<script src="template/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.js?v=2.2.2" type="text/javascript"></script>
<script src="template/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
<script src="template/plugins/jquery.blockui.min.js" type="text/javascript"></script>
<script src="template/plugins/jquery.cokie.min.js" type="text/javascript"></script>
<script src="template/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
<!-- END CORE PLUGINS -->
<!-- BEGIN PAGE LEVEL PLUGINS -->
<script type="text/javascript" src="<?= CFG_PATH_TO_CKEDITOR ?>ckeditor.js"></script>
<script type="text/javascript" src="<?= CFG_PATH_TO_CKEDITOR ?>plugins/codesnippet/lib/highlight/highlight.pack.js"></script>
<script type="text/javascript" src="template/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js"></script>
<script type="text/javascript" src="template/plugins/bootstrap-datetimepicker-master/js/bootstrap-datetimepicker.js"></script>
<script type="text/javascript" src="template/plugins/bootstrap-modal/js/bootstrap-modalmanager.js" type="text/javascript"></script>
<script type="text/javascript" src="template/plugins/bootstrap-modal/js/bootstrap-modal.js" type="text/javascript"></script>
<script type="text/javascript" src="template/plugins/jquery-nestable/jquery.nestable.js"></script>
<script type="text/javascript" src="template/plugins/bootstrap-wizard/jquery.bootstrap.wizard.js"></script>
<script type="text/javascript" src="js/uploadifive/jquery.uploadifive.js?v=1.2.3"></script>
<script type="text/javascript" src="js/chosen/1.8.7/chosen.jquery.js"></script>
<script type="text/javascript" src="js/chosen/1.8.7/jquery-chosen-sortable.min.js"></script>
<script type="text/javascript" src="js/chosen/1.8.7/chosen-order/chosen.order.jquery.min.js"></script>
<script type="text/javascript" src="js/maskedinput/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/totop/jquery.ui.totop.js" ></script>
<script type="text/javascript" src="js/jquery-number-master/jquery.number.min.js" ></script>
<script type="text/javascript" src="js/select2/4.1.0/js/select2.full.js" ></script>
<script type="text/javascript" src="js/jquery.taboverride-master/build/taboverride.min.js" ></script>
<script type="text/javascript" src="js/jquery.taboverride-master/build/jquery.taboverride.min.js" ></script>
<script type="text/javascript" src="js/scannerdetection/1.1.2/jquery.scannerdetection.js" ></script>
<script type="text/javascript" src="js/inputmask/5.0.5/jquery.inputmask.min.js" ></script>
<script type="text/javascript" src="js/izoColorPicker/1.0/izoColorPicker.js"></script>
<script type="text/javascript" src="js/treetable-master/jquery-treetable.js" ></script>
<script type="text/javascript" src="js/xdsoft_datetimepicker/2.5.22/jquery.datetimepicker.full.js" ></script>
<script type="text/javascript" src="js/jquery.zeninput/jquery.zeninput.js" ></script>
<script type="text/javascript" src="template/plugins/ion.rangeSlider-master/2.3.1/js/ion.rangeSlider.min.js"></script>
<script type="text/javascript" src="js/audiorecorder/1.0/audiorecorder_helper.js"></script>
<!-- END PAGE LEVEL PLUGINS -->

<?php require('js/mapbbcode-master/includes.js.php'); ?>

<?php 
	if(is_ext_installed())
	{
		echo smart_input::render_js_includes();	
	}
?>

<!-- BEGIN PAGE LEVEL SCRIPTS -->
<script src="template/scripts/app.js?v=<?php echo PROJECT_VERSION ?>" type="text/javascript"></script>
<!-- END PAGE LEVEL SCRIPTS -->

<script>
    
    var xdatetimepicker_i18n ={
            months: [<?php echo TEXT_DATEPICKER_MONTHS ?>],
            dayOfWeekShort: [<?php echo TEXT_DATEPICKER_DAYSMIN ?>],
            dayOfWeek: [<?php echo TEXT_DATEPICKER_DAYS ?>]
        }    
    
jQuery(document).ready(function() {     

	$.fn.datepicker.dates['en'] = {
	    days: [<?php echo TEXT_DATEPICKER_DAYS ?>],
	    daysShort: [<?php echo TEXT_DATEPICKER_DAYSSHORT ?>],
	    daysMin: [<?php echo TEXT_DATEPICKER_DAYSMIN ?>],
	    months: [<?php echo TEXT_DATEPICKER_MONTHS ?>],
	    monthsShort: [<?php echo TEXT_DATEPICKER_MONTHSSHORT ?>],
	    today: "<?php echo TEXT_DATEPICKER_TODAY ?>"    
	};
	
	$.fn.datetimepicker.dates['en'] = {
	    days: [<?php echo TEXT_DATEPICKER_DAYS ?>],
	    daysShort: [<?php echo TEXT_DATEPICKER_DAYSSHORT ?>],
	    daysMin: [<?php echo TEXT_DATEPICKER_DAYSMIN ?>],
	    months: [<?php echo TEXT_DATEPICKER_MONTHS ?>],
	    monthsShort: [<?php echo TEXT_DATEPICKER_MONTHSSHORT ?>],
	    meridiem: ["am", "pm"],
			suffix: ["st", "nd", "rd", "th"],
	    today: "<?php echo TEXT_DATEPICKER_TODAY ?>"    
	};

  App.init();
  
  rukovoditel_app_init();

	appHandleUniform()
     
});
</script>

<?php echo i18n_js() ?>

</body>
<!-- END BODY -->
</html>