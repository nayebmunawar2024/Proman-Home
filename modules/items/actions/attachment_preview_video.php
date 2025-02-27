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

$file = attachments::parse_filename(base64_decode($_GET['file']));
    		  	
if(!is_file($file['file_path']))
{
    die(TEXT_FILE_NOT_FOUD);
}

switch($app_module_action)
{
    case 'get_video_file':
        header('Content-type: ' . $file['mime_type']);
        echo file_get_contents($file['file_path']);
        exit();
        break;
}


$html = '
    <div class="attachment-previw-window' . (is_mobile() ? '-mobile':''). '">
        <video id="player" playsinline controls autoplay  style="width:100%;">
                <source src="' . url_for('items/attachment_preview_video','action=get_video_file&path=' . $app_path . '&file=' . urlencode(base64_encode($file['file']))) . '" type="' . $file['mime_type'] . '" />
        </video>
    </div>
    <script>
        document.getElementById("player").addEventListener("loadeddata", function() {
            if(this.videoHeight==0)
            {
                this.style.cssText="height: 60px; width: 100%";
            }
        })
    </script>
    ';


echo $html;
