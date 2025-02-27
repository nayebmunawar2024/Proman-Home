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

//check security settings if they are enabled
app_restricted_countries::verify();
app_restricted_ip::verify();

if(app_session_is_registered('app_logged_users_id'))
{
    redirect_to('users/login', 'action=logoff');
}

$app_layout = 'login_layout.php';

if(CFG_LDAP_USE != 1)
{
    $alerts->add(TEXT_LDAP_IS_NOT_ENABLED, 'warning');
    redirect_to('users/login');
}

switch($app_module_action)
{
    case 'login':
        //chck form token
        app_check_form_token('users/ldap_login');

        if(!$ldap_default_group_id = access_groups::get_ldap_default_group_id())
        {
            redirect_to('users/ldap_login');
        }

        if(app_recaptcha::is_enabled())
        {
            if(!app_recaptcha::verify())
            {
                $alerts->add(TEXT_RECAPTCHA_VERIFY_ROBOT, 'error');
                redirect_to('users/ldap_login');
            }
        }
        
        //login attempt
        if(!login_attempt::verify())
        {
            $alerts->add(TEXT_LOGIN_ATTEMPT_VERIFY_ERROR, 'error');
            redirect_to('users/ldap_login');
        }

        $username = db_prepare_input($_POST['username']);
        $password = db_prepare_input($_POST['password']);

        $ldap = new ldap_login();

        $user_attr = $ldap->do_ldap_login($username, $password);

        if($user_attr['status'] == true)
        {
            $user_email = $username . '@localhost.com';

            if(strlen($user_attr['email']) > 0)
            {
                $user_email = $user_attr['email'];
            }

            if(strlen($user_attr['name']) > 0)
            {
                $first_name = $user_attr['name'];
            }

            $first_name = (strlen($user_attr['firstname']) ? $user_attr['firstname'] : $first_name);
            $last_name = (strlen($user_attr['lastname']) ? $user_attr['lastname'] : '');
            $group = $user_attr['group'];
            
            $group_id = ($group > 0 ? $group : $ldap_default_group_id);

            $check_query = db_query("select id, field_6, multiple_access_groups from app_entity_1 where field_12='" . db_input($username) . "' ");
            if(!$check = db_fetch_array($check_query))
            {
                $hasher = new PasswordHash(11, false);               

                $sql_data = array('password' => $hasher->HashPassword($password),
                    'field_12' => $username,
                    'field_5' => 1,
                    'field_6' => $group_id,
                    'field_7' => $first_name,
                    'field_8' => $last_name,
                    'field_9' => $user_email,
                    'date_added' => time());

                db_perform('app_entity_1', $sql_data);
                $users_id = db_insert_id();

                if(is_ext_installed())
                {
                    $app_user['id'] = $users_id;
                    $app_user['email'] = $user_email;
                    $app_user['group_id'] = $group_id;

                    //email rules
                    $email_rules = new email_rules(1, $users_id);
                    $email_rules->send_insert_msg();

                    //log changeds            
                    $log = new track_changes(1, $users_id);
                    $log->log_insert();
                }

                if(!strstr($user_email, 'localhost.com'))
                {
                    $options = array('to' => $user_email,
                        'to_name' => $first_name,
                        'subject' => (strlen(CFG_REGISTRATION_EMAIL_SUBJECT) > 0 ? CFG_REGISTRATION_EMAIL_SUBJECT : TEXT_NEW_USER_DEFAULT_EMAIL_SUBJECT),
                        'body' => CFG_REGISTRATION_EMAIL_BODY . '<p><b>' . TEXT_LOGIN_DETAILS . '</b></p><p>' . TEXT_USERNAME . ': ' . $username . '<br></p><p><a href="' . url_for('users/login', '', true) . '">' . url_for('users/login', '', true) . '</a></p>',
                        'from' => CFG_EMAIL_ADDRESS_FROM,
                        'from_name' => CFG_EMAIL_NAME_FROM);

                    users::send_email($options);
                }

                app_session_register('app_logged_users_id', $users_id);

                //login log
                users_login_log::success($username, $users_id);

                redirect_to('users/account');
            }
            else
            {
                if($group>0 and $check['field_6']>0 and $check['field_6']!=$group)
                {                                        
                    $sql_data = [
                        'field_6'=>$group_id
                        ];
                    
                    
                    if(strlen($check['multiple_access_groups']) and !in_array($group_id,explode(',',$check['multiple_access_groups'])))
                    {
                        $multiple_access_groups = explode(',',$check['multiple_access_groups']);
                        /*if (($key = array_search($check['field_6'], $multiple_access_groups)) !== false) 
                        {
                            unset($multiple_access_groups[$key]);
                        }*/
                        
                        $multiple_access_groups[] = $group_id;
                        
                        $sql_data['multiple_access_groups'] = implode(',',$multiple_access_groups);
                    }
                    
                    db_perform('app_entity_1', $sql_data,'update',"id={$check['id']}");
                }

                app_session_register('app_logged_users_id', $check['id']);

                //login log
                users_login_log::success($username, $check['id']);

                if(isset($_COOKIE['app_login_redirect_to']))
                {
                    setcookie('app_login_redirect_to', '', time() - 3600, '/');
                    redirect_to(str_replace('module=', '', $_COOKIE['app_login_redirect_to']));
                }
                else
                {
                    redirect_to('dashboard/');
                }
            }
        }
        else
        {
            //login log
            users_login_log::fail($username);

            $alerts->add($user_attr['msg'], 'warning');
            redirect_to('users/ldap_login');
        }
        break;
}