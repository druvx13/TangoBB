<?php

define('BASEPATH', 'Staff');
require_once('../applications/wrapper.php');

if (!$TANGO->perm->check('access_administration')) {
    redirect(SITE_URL);
}//Checks if user has permission to create a thread.
//require_once('template/top.php');
echo $ADMIN->template('top');
$notice = '';

function languagePackages()
{
    global $TANGO;
    $return = '';
    if ($handle = opendir('../applications/languages/')) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != ".." && $entry != "index.html") {
                $explode = explode('.php', $entry);
                $checked = ($TANGO->data['site_language'] == $explode['0']) ? ' selected' : '';
                $return .= '<option value="' . $explode['0'] . '"' . $checked . '>' . ucfirst($explode['0']) . '</option>';
            }
        }
        closedir($handle);
    }
    return $return;
}

if (isset($_POST['update'])) {
    try {

        foreach ($_POST as $parent => $child) {
            $_POST[$parent] = clean($child);
        }

        NoCSRF::check('csrf_token', $_POST);

        $site_name = $_POST['site_name'];
        $board_email = $_POST['board_email'];
        $site_lang = $_POST['default_language'];
        $site_rules = $_POST['board_rules'];
        $enable_reg = (isset($_POST['register_enable'])) ? '1' : '0';
        $post_merge = (isset($_POST['post_merge'])) ? '1' : '0';
        $flatui_ena = (isset($_POST['flatui_enable'])) ? '1' : '0';
        $number_subs = $_POST['number_subs'];

        $fb_app_id = $_POST['fb_app_id'];
        $fb_app_sec = $_POST['fb_app_secret'];
        $enable_fb = (isset($_POST['enable_facebook'])) ? '1' : '0';

        $rcap_public = $_POST['rcap_public'];
        $rcap_private = $_POST['rcap_private'];
        $enable_rcap = (isset($_POST['enable_recaptcha'])) ? '2' : '1';

        $smtp_port = $_POST['smtp_port'];
        $smtp_user = $_POST['smtp_user'];
        $smtp_pass = $_POST['smtp_pass'];
        $smtp_add = $_POST['smtp_add'];
        $enable_smtp = (isset($_POST['enable_smtp'])) ? '2' : '1';
        if (!$site_name or !$board_email or !$site_lang) {
            throw new Exception ('All fields are required!');
        } else {

            /*$data = array(
                'site_name' => $site_name,
                'site_email' => $board_email,
                'site_rules' => $site_rules,
                'site_language' => $site_lang,
                'register_enable' => $enable_reg,
                'post_merge' => $post_merge,
                'facebook_app_id' => $fb_app_id,
                'facebook_app_secret' => $fb_app_sec,
                'facebook_authenticate' => $enable_fb,
                'recaptcha_public_key' => $rcap_public,
                'recaptcha_private_key' => $rcap_private,
                'captcha_type' => $enable_rcap,
                'mail_type' => $enable_smtp,
                'smtp_address' => $smtp_add,
                'smtp_port' => $smtp_port,
                'smtp_username' => $smtp_user,
                'smtp_password' => $smtp_pass
            );
            $MYSQL->where('id', 1);*/
            $MYSQL->bindMore(array(
                'site_name' => $site_name,
                'site_email' => $board_email,
                'site_rules' => $site_rules,
                'site_language' => $site_lang,
                'register_enable' => $enable_reg,
                'post_merge' => $post_merge,
                'number_subs' => $number_subs,
                'facebook_app_id' => $fb_app_id,
                'facebook_app_secret' => $fb_app_sec,
                'facebook_authenticate' => $enable_fb,
                'recaptcha_public_key' => $rcap_public,
                'recaptcha_private_key' => $rcap_private,
                'captcha_type' => $enable_rcap,
                'mail_type' => $enable_smtp,
                'smtp_address' => $smtp_add,
                'smtp_port' => $smtp_port,
                'smtp_username' => $smtp_user,
                'smtp_password' => $smtp_pass,
                'flat_ui_admin' => $flatui_ena
            ));

            try {
                //$MYSQL->update('{prefix}generic', $data);
                $MYSQL->query('UPDATE {prefix}generic SET site_name = :site_name,
                                                            site_email = :site_email,
                                                            site_rules = :site_rules,
                                                            site_language = :site_language,
                                                            register_enable = :register_enable,
                                                            post_merge = :post_merge,
                                                            number_subs = :number_subs,
                                                            facebook_app_id = :facebook_app_id,
                                                            facebook_app_secret = :facebook_app_secret,
                                                            facebook_authenticate = :facebook_authenticate,
                                                            recaptcha_public_key = :recaptcha_public_key,
                                                            recaptcha_private_key = :recaptcha_private_key,
                                                            captcha_type = :captcha_type,
                                                            mail_type = :mail_type,
                                                            smtp_address = :smtp_address,
                                                            smtp_port = :smtp_port,
                                                            smtp_username = :smtp_username,
                                                            smtp_password = :smtp_password,
                                                            flat_ui_admin = :flat_ui_admin
                                                            WHERE id = 1');
                $notice .= $ADMIN->alert(
                    $LANG['admin']['save_success'],
                    'success'
                );
            } catch (mysqli_sql_exception $e) {
                throw new Exception ($LANG['admin']['save_error']);
            }

        }

    } catch (Exception $e) {
        $notice .= $ADMIN->alert(
            $e->getMessage(),
            'danger'
        );
    }
}

$token = NoCSRF::generate('csrf_token');

echo '<form action="" method="POST">';

$reg_check    = ($TANGO->data['register_enable'] == 1) ? ' CHECKED' : '';
$merge_check  = ($TANGO->data['post_merge'] == 1) ? ' CHECKED' : '';
$flatui_check = ($TANGO->data['flat_ui_admin'])? 'CHECKED' : '';
echo $ADMIN->box(
    $LANG['admin']['general_settings'],
    $notice .
    '<input type="hidden" name="csrf_token" value="' . $token . '">
       <label for="site_name">' . $LANG['admin']['board_name'] . '</label>
       <input type="text" class="form-control" name="site_name" id="site_name" value="' . $TANGO->data['site_name'] . '" />
       <label for="board_email">' . $LANG['admin']['board_email'] . '</label>
       <input type="text" class="form-control" name="board_email" id="board_email" value="' . $TANGO->data['site_email'] . '" />
       <label for="number_subs">' . $LANG['admin']['shown_subforums'] . '</label>
       <input type="text" class="form-control" name="number_subs" id="number_subs" value="' . $TANGO->data['number_subs'] . '" />
       <input type="checkbox" name="register_enable" value="1" id="reg_enable" ' . $reg_check . ' /> <label for="reg_enable">' . $LANG['admin']['enable_register'] . '</label><br />
       <input type="checkbox" name="post_merge" value="1" id="post_merge" ' . $merge_check . ' /> <label for="post_merge">' . $LANG['admin']['merge_posts'] . ' (<a href="#" title="' . $LANG['admin']['merge_posts_tooltip'] . '" id="tooltip">?</a>)</label><br />
       <input type="checkbox" name="flatui_enable" value="1" id="flatui_enable" ' . $flatui_check . ' /> <label for="flatui_enable">' . $LANG['admin']['enable_flat_ui'] . ' (<a href="#" title="' . $LANG['admin']['enable_flat_ui_tooltip'] . '" id="tooltip">?</a>)</label><br />
       <br />
       <label for="default_language">' . $LANG['admin']['default_language'] . '</label><br />
       <select name="default_language" id="Default_language">
       ' . languagePackages() . '
       </select>'
);
echo $ADMIN->box(
    $LANG['admin']['forum_rules'],
    $LANG['admin']['forum_rules_message'] . '
     <textarea name="board_rules" class="form-control" style="min-height:250px;">' . $TANGO->data['site_rules'] . '</textarea>'
);

$recaptcha_check = ($TANGO->data['captcha_type'] == "2") ? ' CHECKED' : '';
echo $ADMIN->box(
    $LANG['admin']['captcha_settings'],
    $LANG['admin']['captcha_message'] . '<br />
       <label for="rcap_public">' . $LANG['admin']['recaptcha_public_key'] . '</label>
       <input type="text" name="rcap_public" id="rcap_public" class="form-control" value="' . $TANGO->data['recaptcha_public_key'] . '" />
       <label for="rcap_private">' . $LANG['admin']['recaptcha_private_key'] . '</label>
       <input type="text" name="rcap_private" id="rcap_private" class="form-control" value="' . $TANGO->data['recaptcha_private_key'] . '" />
       <input type="checkbox" name="enable_recaptcha" value="1"' . $recaptcha_check . ' /> ' . $LANG['admin']['use_recaptcha']
);

$smtp_check = ($TANGO->data['mail_type'] == 2) ? ' CHECKED' : '';
echo $ADMIN->box(
    $LANG['admin']['smtp_settings'],
    '<label for="smtp_add">' . $LANG['admin']['smtp_address'] . '</label>
       <input type="text" name="smtp_add" id="smtp_add" class="form-control" value="' . $TANGO->data['smtp_address'] . '" />
       <label for="smtp_user">' . $LANG['admin']['smtp_username'] . '</label>
       <input type="text" name="smtp_user" id="smtp_user" class="form-control" value="' . $TANGO->data['smtp_username'] . '" />
       <label for="smtp_pass">' . $LANG['admin']['smtp_password'] . '</label>
       <input type="text" name="smtp_pass" id="smtp_pass" class="form-control" value="' . $TANGO->data['smtp_password'] . '" />
       <label for="smtp_port">' . $LANG['admin']['smtp_port'] . '</label>
       <input type="text" name="smtp_port" id="smtp_port" class="form-control" value="' . $TANGO->data['smtp_port'] . '" />
       <input type="checkbox" name="enable_smtp" value="1"' . $smtp_check . ' /> ' . $LANG['admin']['send_email_smtp']
);
$fb_check = ($TANGO->data['facebook_authenticate'] == 1) ? ' CHECKED' : '';
echo $ADMIN->box(
    $LANG['admin']['facebook_settings'],
    $LANG['admin']['facebook_message'] . '<br />
       <label for="fb_app_id">' . $LANG['admin']['facebook_app_id'] . '</label>
       <input type="text" name="fb_app_id" id="fb_app_id" class="form-control" value="' . $TANGO->data['facebook_app_id'] . '" />
       <label for="fb_app_secret">' . $LANG['admin']['facebook_app_secret'] . '</label>
       <input type="text" name="fb_app_secret" id="fb_app_secret" class="form-control" value="' . $TANGO->data['facebook_app_secret'] . '" />
       <input type="checkbox" name="enable_facebook" value="1"' . $fb_check . ' /> ' . $LANG['admin']['enable_facebook_auth']
);

echo $ADMIN->box(
    null,
    '<input type="submit" name="update" class="btn btn-default" value="' . $LANG['admin']['save_settings'] . '" />'
);

echo '</form>';

//require_once('template/bot.php');
echo $ADMIN->template('bot');

?>
