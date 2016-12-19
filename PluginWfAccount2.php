<?php
/**
<p>New version 161001 where only MySQL can be used instead of yml user files. It depends on wf/phpmailer to send messages. Therefore settings/phpmailer is needed.</p>
<p>Features</p>
<ul>
 <li>Sign in, registration, change email, change password is what this plugin is about.
 <li>Source must be MySql.
 <li>Settings form wf/phpmailer is needed.
 <li>Email is sent when registration and change email.
 <li>Registration will not create a new account if one exist, it will only do a password recovery in hidden mode.
 <li>Registration and change email will send a key via email for verify purpose.
</ul>
#code-yml#
plugin_modules:
  account:
    plugin: 'wf/account2'
    settings:
      allow:
        signin: true
        registration: true
        change_email: true
        change_password: true
      mysql:
        server: 'localhost'
        database: 'database_name'
        user_name: 'my_username'
        password: 'my_secret_password'
      phpmailer:
        SMTPAuth: 'true'
        SMTPSecure: ssl
        Port: '465'
        SMTPDebug: '0'
        Username: buto.noreply@gmail.com
        Password: 'my_secret_password'
        Host: smtp.gmail.com
        From: buto.noreply@gmail.com
        FromName: 'PluginWfAccount2'
        To: claes@webforms.se
        Subject: 'Action of PluginWfAccount2'
        Body: Body.
        WordWrap: '255'
#code#
<pre>
CREATE TABLE `account` (
  `id` varchar(50) NOT NULL default '',
  `email` varchar(255) default NULL,
  `password` varchar(255) default NULL,
  `activated` int(11) default NULL,
  `activate_key` varchar(50) default NULL,
  `activate_password` varchar(255) default NULL,
  `activate_date` datetime default NULL,
  `change_email_email` varchar(255) default NULL,
  `change_email_key` varchar(50) default NULL,
  `change_email_date` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `account_log` (
  `id` int(16) NOT NULL auto_increment,
  `account_id` varchar(50) default NULL,
  `type` varchar(50) default NULL,
  `date` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `account_log_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `account` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
CREATE TABLE `account_role` (
  `id` int(16) NOT NULL auto_increment,
  `account_id` varchar(50) default NULL,
  `role` varchar(50) default NULL,
  PRIMARY KEY  (`id`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `account_role_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `account` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
</pre>
*/
class PluginWfAccount2{
  /**
  Page with a create form.  
  */
  public function page_create(){
    wfPlugin::includeonce('wf/array');
    wfPlugin::includeonce('wf/yml');
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    
    $this->checkAllow($settings, 'registration');
    
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/wf/account2/layout');
    if(wfUser::isSecure()){
      $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/page/signedin.yml';
      $page = wfFilesystem::loadYml($filename);
      $page = wfArray::set($page, 'content/signin/innerHTML/signout/attribute/href', '/'.wfArray::get($GLOBALS, 'sys/class').'/signout');
      wfDocument::mergeLayout($page);
      return null;
    }else{
      $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/page/create.yml';
      $page = wfFilesystem::loadYml($filename);
      $form = wfFilesystem::loadYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/form/create.yml');
      $form['url'] = '/'.wfArray::get($GLOBALS, 'sys/class').'/action';
      $page = wfArray::set($page, 'content/login_form/innerHTML/frm_login/data/data', $form);
      wfDocument::mergeLayout($page);
      return null;
    }
  }
  
  private function checkAllow($settings, $type){
    if(!$settings->get('allow/'.$type)){
      exit("Param settings/allow/$type must exist and be true!");
    }
  }
  
  public function page_signin(){
    $ajax = false;
    if(wfRequest::get('_time')){
      $ajax = true;
    }
    wfPlugin::includeonce('wf/array');
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    $this->checkAllow($settings, 'signin');
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/wf/account2/layout');
    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/page/signin.yml';
    $page = wfFilesystem::loadYml($filename);
    if(!$ajax){
      unset($page['content']['move_save_button']);
    }
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml('/plugin/wf/account2/form/signin.yml');
    $form->set('url', '/'.wfArray::get($GLOBALS, 'sys/class').'/action');
    if($ajax){
      $form->setUnset('buttons/btn_cancel');
    }
    $page = wfArray::set($page, 'content/login_form/innerHTML/frm_login/data/data', $form->get());
    wfDocument::mergeLayout($page);
  }  
  public function page_email(){
    wfPlugin::includeonce('wf/array');
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    $this->checkAllow($settings, 'change_email');
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/wf/account2/layout');
    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/page/email.yml';
    $page = wfFilesystem::loadYml($filename);
    $form = wfFilesystem::loadYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/form/email.yml');
    $form['url'] = '/'.wfArray::get($GLOBALS, 'sys/class').'/action';
    $page = wfArray::set($page, 'content/login_form/innerHTML/frm_login/data/data', $form);
    wfDocument::mergeLayout($page);
  }
  public function page_action(){
    if(!wfRequest::isPost()){
      exit('');
    }
    wfPlugin::includeonce('wf/yml');
    wfPlugin::includeonce('wf/array');
    wfPlugin::includeonce('wf/form');
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    $action = wfRequest::get('action');
    $script = new PluginWfArray();
    $json = new PluginWfArray();
    $json->set('success', false);
    $users = $this->getUsers($settings);
    $uid = wfCrypt::getUid();
    if(($action=='create' || $action=='activate')){
      $form = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/form/create.yml');
    }elseif($action=='signin'){
      $form = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/form/signin.yml');
    }elseif($action=='email' || $action=='email_verify'){
      $form = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/form/email.yml');
    }elseif($action=='password'){
      $form = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/form/password.yml');
    }
    if($action=='create'){
      $this->checkAllow($settings, 'registration');
      $activate_key = $this->getKey();
      $form->set(null, PluginWfForm::bindAndValidate($form->get()));
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('account_create_save', '".PluginWfForm::getErrors($form->get())."');"));
      }else{
        $user_id = $this->getUserId($users->get(), $form->get('items/email/post_value'));
        if(!$user_id){
          // Create account.
          $user_id = wfCrypt::getUid();
          $this->runSQL($settings, "insert into account (id, email, password) values ('$user_id', '".$form->get('items/email/post_value')."', '".wfCrypt::getHashAndSaltAsString($form->get('items/password/post_value'))."');");
          $this->log(null, 'create', $user_id);
          wfEvent::run('account_create');
        }else{
          $this->runSQL($settings, "update account set activate_password='".wfCrypt::getHashAndSaltAsString($form->get('items/password/post_value'))."' where id='$user_id';");
          $this->runSQL($settings, "update account set activate_date='".date('Y-m-d H:i:s')."' where id='$user_id';");
          $this->log(null, 'recreate', $user_id);
          wfEvent::run('account_recreate');
        }
        $this->runSQL($settings, "update account set activate_key='".$activate_key."' where id='$user_id';");
        // Script
        $script->set(true, 'document.getElementById(\'div_account_create_email\').style.display=\'none\'');
        $script->set(true, 'document.getElementById(\'div_account_create_password\').style.display=\'none\'');
        $script->set(true, 'document.getElementById(\'div_account_create_key\').style.display=\'\'');
        $script->set(true, 'document.getElementById(\'account_create_save\').value=\''.__('Verify').'\'');
        $script->set(true, 'document.getElementById(\'account_create_action\').value=\'activate\'');
        $script->set(true, 'PluginWfAccount2.sendmessage("'.wfArray::get($GLOBALS, 'sys/class').'");');
        $script->set(true, 'PluginWfAccount2.saveForm("account_create_save", "Check your email for the key!", true);');
        $json->set('success', true);
        $json->set('script', $script->get());
        // Set params to send mail via page_sendmessage().
        $_SESSION = wfArray::set($_SESSION, 'plugin/wf/account/send_email/To',   $form->get('items/email/post_value'));
        $_SESSION = wfArray::set($_SESSION, 'plugin/wf/account/send_email/Body', 'Key to activate account is: '.$activate_key);
      }
    }elseif($action=='activate'){
      $this->checkAllow($settings, 'registration');
      $form->set('items/key/mandatory', true);
      $form->set(null, PluginWfForm::bindAndValidate($form->get()));
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('account_create_save', '".PluginWfForm::getErrors($form->get())."');"));
      }else{
        $user_id = $this->getUserId($users->get(), $form->get('items/email/post_value'));
        if(!$user_id){
          $json->set('script', array("PluginWfAccount2.saveForm('account_create_save', '".__('User is missing!')."');"));
        }else{
          $this->log(null, 'activate', $user_id);
          /// Get user data...
          $user = $this->getUser($settings, $user_id);
          if($user->get('activate_key') != $form->get('items/key/post_value')){
            $json->set('script', array("PluginWfAccount2.saveForm('account_create_save', '".__('Key does not match!')."');"));
          }else{
            $this->runSQL($settings, "update account set activated=1 where id='$user_id';");
            if($user->get('activate_password')){
              $this->runSQL($settings, "update account set password=activate_password where id='$user_id';");
            }
            $this->runSQL($settings, "update account set activate_key=null where id='$user_id';");
            $this->runSQL($settings, "update account set activate_password=null where id='$user_id';");
            $this->runSQL($settings, "update account set activate_date=null where id='$user_id';");
            $script->set(true, 'document.getElementById(\'account_create\').innerHTML=\''.__('Account was activated! <a href="/">Home</a>.').'\'');
            $script->set(true, "location.href='/'");
            $json->set('success', true);
            $json->set('script', $script->get());
            wfEvent::run('account_activate');
            $this->sign_in($user_id, $users->get(), $settings);
          }
        }
      }
    }elseif($action=='signin'){
      $this->checkAllow($settings, 'signin');
      $form->set(null, PluginWfForm::bindAndValidate($form->get()));
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('account_create_save', '".PluginWfForm::getErrors($form->get())."');"));
      }else{
        $user_id = $this->getUserId($users->get(), $form->get('items/email/post_value'));
        if(!$user_id){
          $json->set('script', array("PluginWfAccount2.saveForm('account_create_save', '".__('Username or password does not match!')."');"));
        }else{
          if($this->validatePassword($users->get($user_id.'/password'), $form->get('items/password/post_value'))){
            if(!$users->get($user_id.'/activated')){
              $json->set('script', array("PluginWfAccount2.saveForm('account_create_save', '".__('User is not activated!')."');"));
            }else{
              $json->set('success', true);
              $this->sign_in($user_id, $users->get(), $settings);
              $json->set('script', array("location.href='/';"));
            }
          }else{
            $json->set('script', array("PluginWfAccount2.saveForm('account_create_save', '".__('Username or password does not match!')."');"));
          }
        }
      }
    }elseif($action=='email' && wfUser::isSecure()){
      $this->checkAllow($settings, 'change_email');
      $form->set(null, PluginWfForm::bindAndValidate($form->get()));
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('account_email_save', '".PluginWfForm::getErrors($form->get())."');"));
      }else{
        if(!$this->validatePassword($users->get(wfArray::get($_SESSION, 'user_id').'/password'), $form->get('items/password/post_value'))){
          $json->set('script', array("PluginWfAccount2.saveForm('account_email_save', '".__('Password does not match!')."');"));
        }else{
          $get_key = $this->getKey();
          $this->runSQL($settings, "update account set change_email_email='".$form->get('items/new_email/post_value')."' where id='".wfArray::get($_SESSION, 'user_id')."';");
          $this->runSQL($settings, "update account set change_email_key='".$get_key."' where id='".wfArray::get($_SESSION, 'user_id')."';");
          $this->runSQL($settings, "update account set change_email_date='".date('Y-m-d H:i:s')."' where id='".wfArray::get($_SESSION, 'user_id')."';");
          $script->set(true, 'document.getElementById(\'wf_account_current_email\').style.display=\'none\'');
          $script->set(true, 'document.getElementById(\'div_account_email_new_email\').style.display=\'none\'');
          $script->set(true, 'document.getElementById(\'div_account_email_password\').style.display=\'none\'');
          $script->set(true, 'document.getElementById(\'div_account_email_key\').style.display=\'\'');
          $script->set(true, 'document.getElementById(\'account_email_save\').value=\''.__('Verify').'\'');
          $script->set(true, 'document.getElementById(\'account_email_action\').value=\'email_verify\'');
          $script->set(true, 'PluginWfAccount2.sendmessage("'.wfArray::get($GLOBALS, 'sys/class').'");');
          $script->set(true, 'PluginWfAccount2.saveForm("account_email_save", "Check your email for the key!", true);');
          $json->set('success', true);
          $json->set('script', $script->get());
          $_SESSION = wfArray::set($_SESSION, 'plugin/wf/account/send_email/To',   $form->get('items/new_email/post_value'));
          $_SESSION = wfArray::set($_SESSION, 'plugin/wf/account/send_email/Body', 'Key to activate new email is: '.$get_key);
        }
      }
    }elseif($action=='email_verify' && wfUser::isSecure()){
      $this->checkAllow($settings, 'change_email');
      $user = $this->getUser($settings, wfArray::get($_SESSION, 'user_id'));
      $change_email_key = $user->get('change_email_key');
      $change_email_email = $user->get('change_email_email');
      $form->set('items/key/mandatory', true);
      $validator = new PluginWfArray();
      $validator->set('plugin', 'wf/form');
      $validator->set('method', 'validate_equal');
      $validator->set('data/value', $change_email_key);
      $form->set('items/key/validator/', $validator->get());
      $form->set(null, PluginWfForm::bindAndValidate($form->get()));
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('account_email_save', '".PluginWfForm::getErrors($form->get())."');"));
      }else{
        // Validate.
        $this->runSQL($settings, "update account set email='".$change_email_email."' where id='".wfArray::get($_SESSION, 'user_id')."';");
        $_SESSION = wfArray::set($_SESSION, 'email', $change_email_email);
        $this->runSQL($settings, "update account set change_email_email=null where id='".wfArray::get($_SESSION, 'user_id')."';");
        $this->runSQL($settings, "update account set change_email_key=null where id='".wfArray::get($_SESSION, 'user_id')."';");
        $this->runSQL($settings, "update account set change_email_date=null where id='".wfArray::get($_SESSION, 'user_id')."';");
        $script->set(true, "location.href='/'");
        $json->set('script', $script->get());
      }
    }elseif($action=='password' && wfUser::isSecure()){
      $this->checkAllow($settings, 'change_password');
      $form->set(null, PluginWfForm::bindAndValidate($form->get()));
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('account_password_save', '".PluginWfForm::getErrors($form->get())."');"));
      }else{
        $script->set(true, "location.href='/'");
        $json->set('script', $script->get());
        $this->runSQL($settings, "update account set password='".wfCrypt::getHashAndSaltAsString( $form->get('items/new_password/post_value'))."' where id='".wfArray::get($_SESSION, 'user_id')."';");
      }
    }
    exit(json_encode($json->get()));
  }
  private function validatePassword($password, $post_password){
    if(wfCrypt::isValid($post_password, $password) || $post_password==$password){
      return true;
    }else{
      return false;
    }
  }
  private function log($obj = null, $type, $user_id = null){
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    wfPlugin::includeonce('wf/mysql');
    $mysql = new PluginWfMysql();
    $mysql->open($settings->get('mysql'));
    if(!$user_id){
      $user_id = wfArray::get($_SESSION, 'user_id');
    }
    $mysql->runSql("insert into account_log (account_id, type, date) values ('".$user_id."', '$type', '".date('Y-m-d H:i:s')."');");
  }
  private function getKey(){
    return rand(100000, 999999);
  }
  /**
   * Get user id.
   * @param type $users
   * @param type $email
   * @return type
   */
  private function getUserId($users, $email){
    $user_id = null;
    foreach ($users as $key => $value) {
      if(strtolower($email)==strtolower(wfArray::get($value, 'email'))){
        $user_id = $key;
        break;
      }
    }
    return $user_id;
  }
  private function getUsers($settings){
    wfPlugin::includeonce('wf/mysql');
    $mysql = new PluginWfMysql();
    $mysql->open($settings->get('mysql'));
    $test = $mysql->runSql('select * from account;');
    return new PluginWfArray($test['data']);
  }
  private function getUser($settings, $user_id){
    wfPlugin::includeonce('wf/mysql');
    $mysql = new PluginWfMysql();
    $mysql->open($settings->get('mysql'));
    $test = $mysql->runSql("select * from account where id='$user_id';");
    return new PluginWfArray($test['data'][$user_id]);
  }
  private function runSQL($settings, $sql){
    wfPlugin::includeonce('wf/mysql');
    $mysql = new PluginWfMysql();
    $mysql->open($settings->get('mysql'));
    $test = $mysql->runSql($sql);
    return new PluginWfArray($test['data']);
  }
  /**
   * Send message if params To and Body is set in session.
   */  
  public function page_sendmessage(){
    wfPlugin::includeonce('wf/yml');
    wfPlugin::includeonce('wf/array');
    $json = new PluginWfArray();
    $json->set('success', false);
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    if(wfArray::get($_SESSION, 'plugin/wf/account/send_email/To') && wfArray::get($_SESSION, 'plugin/wf/account/send_email/Body')){
      $settings->set('phpmailer/To', wfArray::get($_SESSION, 'plugin/wf/account/send_email/To'));
      $settings->set('phpmailer/Body', wfArray::get($_SESSION, 'plugin/wf/account/send_email/Body'));
      wfPlugin::includeonce('wf/phpmailer');
      $wf_phpmailer = new PluginWfPhpmailer();
      $wf_phpmailer->send($settings->get('phpmailer'));
      $_SESSION = wfArray::setUnset($_SESSION, 'plugin/wf/account/send_email');
      $json->set('success', true);
    }else{
      $json->set('script/0', "alert('There was a problem to send email!');");
    }
    exit(json_encode($json->get()));
  }
  public function page_password(){
    wfPlugin::includeonce('wf/array');
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    $this->checkAllow($settings, 'change_password');
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/wf/account2/layout');
    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/page/password.yml';
    $page = wfFilesystem::loadYml($filename);
    $form = wfFilesystem::loadYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/form/password.yml');
    $form['url'] = '/'.wfArray::get($GLOBALS, 'sys/class').'/action';
    $page = wfArray::set($page, 'content/login_form/innerHTML/frm_login/data/data', $form);
    wfDocument::mergeLayout($page);
  }
  /**
  User will immediately be signed out when they load this page.
  */
  public function page_signout(){
    wfEvent::run('signout');
    session_destroy();
    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/page/signout.yml';
    $page = wfFilesystem::loadYml($filename);
    if(wfRequest::get('auto')){
      $page = wfArray::set($page, 'content/script/settings/disabled', false);
    }
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/wf/login/layout');
    wfDocument::mergeLayout($page);
  }
  private function sign_in($key, $users, $settings){
    wfPlugin::includeonce('wf/array');
    $user = new PluginWfArray($users[$key]);
    $_SESSION['secure']=true;
    $_SESSION['email']=$user->get('email');
    $_SESSION['user_id']=$key;
    $_SESSION['role'] = $this->get_roles($key, $settings);
    if($user->get('theme')){
      $_SESSION['theme'] = $user->get('theme');
    }
    wfEvent::run('signin');
  }
  /**
   * Get user roles from db.
   * @param type $key
   * @param type $settings
   * @return type
   */
  private function get_roles($key, $settings){
    $role = $this->runSQL($settings, "select role from account_role where account_id='$key';");
    $temp = array();
    foreach ($role->get() as $key2 => $value2) {
      $temp[] = $value2['role'];
    }
    return $temp;
  }
  
  public function validate_current_password($field, $form){
    // If field is valid we check if password match user.
    if(wfArray::get($form, "items/$field/is_valid")){
      wfPlugin::includeonce('wf/array');
      $settings = new PluginWfArray(wfPlugin::getModuleSettings());
      $users = $this->getUsers($settings);
      if(!$this->validatePassword($users->get(wfArray::get($_SESSION, 'user_id').'/password'), wfArray::get($form, "items/$field/post_value"))){
        $form = wfArray::set($form, "items/$field/is_valid", false);
        $form = wfArray::set($form, "items/$field/errors/", __('Password does not match!'));
      }
    }
    return $form;
  }
  /**
   * If call signin page via ajax one has to include script.
   */
  public static function widget_include(){
    $element = wfDocument::createHtmlElement('script', null, array('src' => '/plugin/wf/account2/include.js?x=2'));
    wfDocument::renderElement(array($element));
  }
}
