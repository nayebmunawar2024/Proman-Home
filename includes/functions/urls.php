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

function redirect_to($module, $prams = '')
{
    if(IS_AJAX)
    {
        if($module == 'ext/license/key')
            exit();

        echo '<script>window.top.location.href="' . url_for($module, $prams) . '"</script>';
        exit();
    }

    header('Location: ' . url_for($module, $prams));

    exit();
}

function redirect_to_404()
{
    redirect_to('dashboard/page_not_found');
}

function redirect_to_forbidden()
{
    redirect_to('dashboard/access_forbidden');
}

function is_ssl()
{
    return ((ENABLE_SSL or IS_HTTPS == 'on') ? true : false);
}

function url_for($module, $prams = '', $hide_session = false)
{
    global $session_started;

    $scheme = (is_ssl() ? 'https://' : 'http://');
    $host = $_SERVER['HTTP_HOST'];

    $self = pathinfo($_SERVER['PHP_SELF']);
    $self['dirname'] = str_replace("\\", "/", $self['dirname']??'');
    $path = $self['dirname'] . (substr($self['dirname'], -1) != '/' ? '/' : '');

    $prams = (strlen($prams) > 0 ? '&' . $prams : '');

    if($session_started and!SESSION_FORCE_COOKIE_USE and!$hide_session)
    {
        $prams .= '&' . session_name() . '=' . session_id();
    }

    if(defined('IS_CRON') and IS_CRON == true)
    {
        $url = CRON_HTTP_SERVER_HOST . 'index.php?module=' . $module . $prams;
    }
    else
    {
        $url = $scheme . $host . $path . 'index.php?module=' . $module . $prams;
    }

    $url .= csrf_protect::add_token_to_url($url);

    return $url;
}

function url_for_file($file)
{
    $scheme = (is_ssl() ? 'https://' : 'http://');
    $host = $_SERVER['HTTP_HOST'];

    $self = pathinfo($_SERVER['PHP_SELF']);
    $self['dirname'] = str_replace("\\", "/", $self['dirname']);
    $path = $self['dirname'] . (substr($self['dirname'], -1) != '/' ? '/' : '');

    return $scheme . $host . $path . $file;
}

function link_to($name, $url, $attributes = array())
{
    return '<a href="' . $url . '" ' . tag_attributes_to_html($attributes) . ' ' . (isset($attributes['confirm']) ? 'onclick="return confirm(\'' . addslashes($attributes['confirm']) . '\')"':''). '>' . $name . '</a>';
}

function link_to_modalbox($name, $url, $attributes = array())
{
    if(!isset($attributes['class']))
        $attributes['class'] = '';

    $attributes['class'] .= ' link-to-modalbox';

    return '<a onClick="open_dialog(\'' . $url . '\')" ' . tag_attributes_to_html($attributes) . '>' . $name . '</a>';
}

function component_path($path)
{
    $module_array = explode('/', $path);

    if(count($module_array) == 2)
    {
        $module = $module_array[0];
        $component = (strlen($module_array[1]) > 0 ? $module_array[1] : $module_array[0]);

        return 'modules/' . $module . '/components/' . $component . '.php';
    }
    elseif(count($module_array) == 3)
    {

        $plugin = $module_array[0];
        $module = $module_array[1];
        $component = (strlen($module_array[2]) > 0 ? $module_array[2] : $module_array[1]);

        return 'plugins/' . $plugin . '/modules/' . $module . '/components/' . $component . '.php';
    }
}

function get_all_get_url_params($exclude_array = '')
{
    global $_GET;

    if(!is_array($exclude_array))
        $exclude_array = array();

    $params = array();
    if(is_array($_GET) && (sizeof($_GET) > 0))
    {
        reset($_GET);
        foreach($_GET as $key => $value)
        {
            if(is_string($value) && (strlen($value) > 0) && ($key != session_name()) && ($key != 'error') && ($key != 'module') && (!in_array($key, $exclude_array)) && ($key != 'x') && ($key != 'y'))
            {
                $params[] = $key . '=' . rawurlencode(stripslashes($value));
            }
        }
    }

    return implode('&', $params);
}

function auto_link_text($text)
{
    $pattern = '/(?s)<pre[^<]*>.*?<\\/pre>(*SKIP)(*F)|([^"]|^)(((http[s]?:\/\/(.+(:.+)?$)?))[a-z0-9](([-a-z0-9]+\.)*\.[a-z]{2,})?\/?[a-z0-9()\[\]$.,_\/~#&=:;%+!?-]+)/i';

    $result = preg_replace_callback($pattern, 'callback_prepare_link_in_text', $text??'');

    if($result != null)
    {
        return $result;
    }
    else
    {
        return $text;
    }
}

function callback_prepare_link_in_text($matches)
{
    $scheme = (is_ssl() ? 'https://' : 'http://');
    $host = $_SERVER['HTTP_HOST'];

    $self = pathinfo($_SERVER['PHP_SELF']);
    $path = $self['dirname'];

    $current_path = $scheme . $host . $path;

    $href = $matches[2];

    if(strstr($href, $current_path))
    {
        $url = str_replace($current_path, '', $href);
    }
    else
    {
        $url = $href;
    }

    if(strlen($url) > 60)
    {
        $url = substr($url, 0, 25) . '...' . substr($url, -25);
    }

    return $matches[1] . '<a target="_blank" href="' . $href . '" title="' . $href . '">' . $url . '</a>';
}

