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
 <li>If using two factor authentication plugin sms/pixie_v1 is required.
</ul>
#code-yml#
plugin_modules:
  account:
    plugin: 'wf/account2'
    settings:
      on_signin:
        script: "location.href='/some/page';" 
      on_activate:
        script: "location.href='/some/page';"
      allow:
        signin: true
        registration: true
        change_email: true
        change_password: true
        two_factor_authentication: true
      two_factor_authentication:
        key_timeout: 600
      sms_pixie:
        account: account_name
        pwd: account_password
        sender: max lenth of 11, can be anything
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
        Username: me@gmail.com
        Password: 'my_secret_password'
        Host: smtp.gmail.com
        From: me@gmail.com
        FromName: 'PluginWfAccount2'
        To: me@world.com
        Subject: 'Action of PluginWfAccount2'
        Body: Body.
        WordWrap: '255'
#code#
*/
class PluginWfAccount2{
  private $ajax = false;
  /**
  Page with a create form.  
  */
  public function page_create(){
    $this->init_page();
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
      $form = new PluginWfYml('/plugin/wf/account2/form/create.yml');
      $form->set('url', '/'.wfArray::get($GLOBALS, 'sys/class').'/action');
      if(!$this->ajax){
        unset($page['content']['script_move_element']);
      }else{
        $form->setUnset('buttons');
      }
      $page = wfArray::set($page, 'content/login_form/innerHTML/frm_login/data/data', $form->get());
      wfDocument::mergeLayout($page);
      return null;
    }
  }
  private function checkAllow($settings, $type){
    if(!$settings->get('allow/'.$type)){
      exit("Param settings/allow/$type must exist and be true!");
    }
  }
  private function init_page(){
    if(wfRequest::get('_time')){
      $this->ajax = true;
    }
    /**
     * Include.
     */
    wfPlugin::includeonce('wf/array');
    wfPlugin::includeonce('wf/yml');
    wfPlugin::includeonce('wf/i18n');
    wfPlugin::includeonce('wf/form_v2');
    /**
     * Enable.
     */
    wfPlugin::enable('wf/form_v2');
  }
  /**
   * 
   * Set flash in sessin if needed.
   * $_SESSION = wfArray::set($_SESSION, 'plugin/wf/account2/flash', array(wfDocument::createHtmlElement('div', 'My sign in message.', array('class' => 'alert alert-info'))));
   */
  public function page_signin(){
    $this->init_page();
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    $this->checkAllow($settings, 'signin');
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/wf/account2/layout');
    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/page/signin.yml';
    $page = wfFilesystem::loadYml($filename);
    $form = new PluginWfYml('/plugin/wf/account2/form/signin.yml');
    $form->set('url', '/'.wfArray::get($GLOBALS, 'sys/class').'/action');
    /**
     * Show create account button.
     */
    if($settings->get('allow/registration')){
      $page = wfArray::set($page, 'content/create/settings/disabled', false);
    }
    /**
     * Two-factor authentication.
     */
    if($settings->get('allow/two_factor_authentication')){
      $form->set('items/two_factor_authentication/type', 'varchar');
      $form->set('items/two_factor_authentication/mandatory', true);
    }else{
      $form->set('items/two_factor_authentication/container_style', 'display:none;');      
    }
    /**
     * Cancel button.
     */
    if($this->ajax){
      $form->set('buttons/btn_cancel/attribute/href', '#!');
      $form->set('buttons/btn_cancel/attribute/onclick', "$('.modal').modal('hide');");
    }
    /**
     * 
     */
    $page = wfArray::set($page, 'content/login_form/innerHTML/frm_login/data/data', $form->get());
    /**
     * Flash.
     */
    if(wfPlugin::flashHas('wf/account2', 'signin')){
      $page = wfArray::set($page, 'content/flash/innerHTML', wfPlugin::flashGet('wf/account2', 'signin'));
    }
    wfDocument::mergeLayout($page);
  }  
  public function page_email(){
    $this->init_page();
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    $this->checkAllow($settings, 'change_email');
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/wf/account2/layout');
    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/page/email.yml';
    $page = wfFilesystem::loadYml($filename);
    $form = new PluginWfYml('/plugin/wf/account2/form/email.yml');
    $form->set('url', '/'.wfArray::get($GLOBALS, 'sys/class').'/action');
    if(!$this->ajax){
      //unset($page['content']['script_move_element']);
    }else{
      $form->setUnset('buttons');
    }
    $page = wfArray::set($page, 'content/login_form/innerHTML/frm_login/data/data', $form->get());
    wfDocument::mergeLayout($page);
  }
  public function page_action(){
    if(!wfRequest::isPost()){
      exit('');
    }
    $this->init_page();
    /**
     * i18n.
     */
    $i18n = new PluginWfI18n();
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    $action = wfRequest::get('action');
    $script = new PluginWfArray();
    $json = new PluginWfArray();
    $json->set('success', false);
    $users = $this->getUsers($settings);
    $uid = wfCrypt::getUid();
    if(($action=='create' || $action=='activate')){
      $form = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/form/create.yml');
    }elseif($action=='signin' || $action=='two_factor_authentication'){
      $form = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/form/signin.yml');
    }elseif($action=='email' || $action=='email_verify'){
      $form = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/form/email.yml');
    }elseif($action=='password'){
      $form = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/form/password.yml');
    }
    if($action=='create'){
      $this->checkAllow($settings, 'registration');
      $activate_key = $this->getKey();
      $form->set(null, PluginWfForm_v2::bindAndValidate($form->get()));
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".PluginWfForm_v2::getErrors($form->get())."');"));
      }else{
        $user_id = $this->getUserId($users->get(), $form->get('items/email/post_value'));
        if(!$user_id){
          // Create account.
          $user_id = wfCrypt::getUid();
          $this->runSQL($settings, "insert into account (id, email, password) values ('$user_id', '".$form->get('items/email/post_value')."', '".wfCrypt::getHashAndSaltAsString($form->get('items/password/post_value'))."');");
          $this->log('create', $user_id);
          wfEvent::run('account_create');
        }else{
          $this->runSQL($settings, "update account set activate_password='".wfCrypt::getHashAndSaltAsString($form->get('items/password/post_value'))."' where id='$user_id';");
          $this->runSQL($settings, "update account set activate_date='".date('Y-m-d H:i:s')."' where id='$user_id';");
          $this->log('recreate', $user_id);
          wfEvent::run('account_recreate');
        }
        $this->runSQL($settings, "update account set activate_key='".$activate_key."' where id='$user_id';");
        // Script
        $script->set(true, 'document.getElementById(\'div_frm_account_email\').style.display=\'none\'');
        $script->set(true, 'document.getElementById(\'div_frm_account_password\').style.display=\'none\'');
        $script->set(true, 'document.getElementById(\'div_frm_account_key\').style.display=\'\'');
        $script->set(true, 'document.getElementById(\'frm_account_save\').innerHTML=\''.$i18n->translateFromTheme('Verify').'\'');
        $script->set(true, 'document.getElementById(\'frm_account_action\').value=\'activate\'');
        $script->set(true, 'document.getElementById(\'btn_goto_signin\').style=\'display:none\'');
        $script->set(true, 'PluginWfAccount2.sendmessage("'.wfArray::get($GLOBALS, 'sys/class').'");');
        $script->set(true, 'PluginWfAccount2.saveForm("frm_account_save", "Check your email for the key!", true);');
        $json->set('success', true);
        $json->set('script', $script->get());
        // Set params to send mail via page_sendmessage().
        $_SESSION = wfArray::set($_SESSION, 'plugin/wf/account/send_email/To',   $form->get('items/email/post_value'));
        $_SESSION = wfArray::set($_SESSION, 'plugin/wf/account/send_email/Body', 'Key to activate account is: '.$activate_key);
      }
    }elseif($action=='activate'){
      $this->checkAllow($settings, 'registration');
      $form->set('items/key/mandatory', true);
      $form->set(null, PluginWfForm_v2::bindAndValidate($form->get()));
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".PluginWfForm_v2::getErrors($form->get())."');"));
      }else{
        $user_id = $this->getUserId($users->get(), $form->get('items/email/post_value'));
        if(!$user_id){
          $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".$i18n->translateFromTheme('User is missing!')."');"));
        }else{
          $this->log('activate', $user_id);
          /// Get user data...
          $user = $this->getUser($settings, $user_id);
          if($user->get('activate_key') != $form->get('items/key/post_value')){
            $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".$i18n->translateFromTheme('Key does not match!')."');"));
          }else{
            $this->runSQL($settings, "update account set activated=1 where id='$user_id';");
            if($user->get('activate_password')){
              $this->runSQL($settings, "update account set password=activate_password where id='$user_id';");
            }
            $this->runSQL($settings, "update account set activate_key=null where id='$user_id';");
            $this->runSQL($settings, "update account set activate_password=null where id='$user_id';");
            $this->runSQL($settings, "update account set activate_date=null where id='$user_id';");
            $script->set(true, 'document.getElementById(\'frm_account\').innerHTML=\''.$i18n->translateFromTheme('Account was activated! <a href="/">Home</a>.').'\'');
            if($settings->get('on_activate/script')){
              $script->set(true, $settings->get('on_activate/script'));
            }else{
              $script->set(true, "location.href='/'");
            }              
            $json->set('success', true);
            $json->set('script', $script->get());
            wfEvent::run('account_activate');
            $this->sign_in($user_id, $users->get(), $settings);
          }
        }
      }
    }elseif($action=='signin'){
      $this->checkAllow($settings, 'signin');
      /**
       * Two-factor authentication.
       */
      if($settings->get('allow/two_factor_authentication')){
        $form->set('items/two_factor_authentication/type', 'varchar');
        $form->set('items/two_factor_authentication/mandatory', true);
      }
      /**
       * 
       */
      $form->set(null, PluginWfForm_v2::bindAndValidate($form->get()));
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".PluginWfForm_v2::getErrors($form->get())."');"));
      }else{
        $user_id = $this->getUserId($users->get(), $form->get('items/email/post_value'));
        if(!$user_id){
          $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".$i18n->translateFromTheme('Username or password does not match!')."');"));
        }else{
          if($this->validatePassword($users->get($user_id.'/password'), $form->get('items/password/post_value'))){
            if(!$users->get($user_id.'/activated')){
              $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".$i18n->translateFromTheme('User is not activated!')."');"));
            }else{
              if(!$settings->get('allow/two_factor_authentication')){
                /**
                 * Normal signin.
                 */
                $json->set('success', true);
                $this->sign_in($user_id, $users->get(), $settings);
                if($settings->get('on_signin/script')){
                  $json->set('script', array($settings->get('on_signin/script')));
                }else{
                  $json->set('script', array("location.href='/';"));
                }              
                $this->log('signin');
              }else{
                /**
                 * Two-factor authentication.
                 */
                if($form->get('items/two_factor_authentication/post_value')=='phone' && !$users->get($user_id.'/phone')){
                  $script->set(true, 'PluginWfAccount2.saveForm("frm_account_save", "'.$i18n->translateFromTheme('Your account does not have a phone number!').'");');
                  $json->set('success', true);
                  $json->set('script', $script->get());
                }elseif($form->get('items/two_factor_authentication/post_value')=='email' || $form->get('items/two_factor_authentication/post_value')=='phone'){
                  /** Send Email **/
                  /**
                   * Generate key.
                   */
                  $get_key = $this->getKey();
                  /**
                   * Update db.
                   */
                  $this->runSQL($settings, "update account set two_factor_authentication_key='".$get_key."' where id='".$user_id."';");
                  $this->runSQL($settings, "update account set two_factor_authentication_date='".date('Y-m-d H:i:s')."' where id='".$user_id."';");
                  /**
                   * Set script.
                   */
                  $script->set(true, "document.getElementById('div_frm_account_email').style.display='none';");
                  $script->set(true, "document.getElementById('div_frm_account_password').style.display='none';");
                  $script->set(true, "document.getElementById('div_frm_account_two_factor_authentication').style.display='none';");
                  $script->set(true, "document.getElementById('div_frm_account_two_factor_authentication_key').style.display='';");
                  $script->set(true, "document.getElementById('frm_account_action').value='two_factor_authentication';");
                  $script->set(true, "document.getElementById('frm_account_save').innerHTML='".$i18n->translateFromTheme('Verify')."';");
                  if(!wfHelp::isLocalhost()){
                    /**
                     * Send message if not in development mode.
                     */
                    $script->set(true, 'PluginWfAccount2.sendmessage("'.wfArray::get($GLOBALS, 'sys/class').'");');
                    /**
                     * Set session.
                     */
                    if($form->get('items/two_factor_authentication/post_value')=='email'){
                      $_SESSION = wfArray::set($_SESSION, 'plugin/wf/account/send_email/To',   $users->get($user_id.'/email'));
                      $_SESSION = wfArray::set($_SESSION, 'plugin/wf/account/send_email/Body', $i18n->translateFromTheme('Key to sign in is:').' '.$get_key);
                      $this->log('two_factor_authentication_email', $user_id);
                    }elseif($form->get('items/two_factor_authentication/post_value')=='phone'){
                      $_SESSION = wfArray::set($_SESSION, 'plugin/wf/account/send_sms/To',   $users->get($user_id.'/phone'));
                      $_SESSION = wfArray::set($_SESSION, 'plugin/wf/account/send_sms/Body', $i18n->translateFromTheme('Key to sign in is:').' '.$get_key);
                      $this->log('two_factor_authentication_phone', $user_id);
                    }
                  }
                  $script->set(true, 'PluginWfAccount2.saveForm("frm_account_save", "'.$i18n->translateFromTheme('An authentication key sent to you!').'", true);');
                  if(wfHelp::isLocalhost()){
                    /**
                     * Set key filed direct if on developer machine.
                     */
                    $script->set(true, "document.getElementById('frm_account_two_factor_authentication_key').value='$get_key';");
                  }
                  $script->set(true, "document.getElementById('frm_account_two_factor_authentication_key').focus();");
                  $json->set('success', true);
                  $json->set('script', $script->get());
                }
              }
            }
          }else{
            $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".$i18n->translateFromTheme('Username or password does not match!')."');"));
          }
        }
      }
    }elseif($action=='two_factor_authentication'){
      /** Tow-factor authentication **/
      $form->set(null, PluginWfForm_v2::bindAndValidate($form->get()));
      $user_id = $this->getUserId($users->get(), $form->get('items/email/post_value'));
      if($user_id){
        if($this->validatePassword($users->get($user_id.'/password'), $form->get('items/password/post_value')) && $users->get($user_id.'/activated')){
          $user = $this->getUser($settings, $user_id);
          /**
           * Check key timout.
           */
          $key_timeout = $settings->get('two_factor_authentication/key_timeout');
          $seconds = round((strtotime(date('Y-m-d H:i:s')) - strtotime($user->get('two_factor_authentication_date'))));
          if($user->get('two_factor_authentication_key') != $form->get('items/two_factor_authentication_key/post_value')){
            /**
             * Key mismatch.
             */
            $script->set(true, "PluginWfAccount2.saveForm('frm_account_save', '".$i18n->translateFromTheme('The key is incorrect!')."');");
            $json->set('success', false);
            $json->set('script', $script->get());
          }elseif($seconds > $key_timeout){
            /**
             * Key timeout.
             */
            $script->set(true, "PluginWfAccount2.saveForm('frm_account_save', '".$i18n->translateFromTheme('Key expired, because of the long time since it was created!')."');");
            $json->set('success', false);
            $json->set('script', $script->get());
          }else{
            /**
             * Script.
             */
            $json->set('success', true);
            $this->sign_in($user_id, $users->get(), $settings);
            if($settings->get('on_signin/script')){
              $json->set('script', array($settings->get('on_signin/script')));
            }else{
              $json->set('script', array("location.href='/';"));
            }
            /**
             * Update db.
             */
            $this->runSQL($settings, "update account set two_factor_authentication_key=null where id='$user_id';");
            $this->log('two_factor_authentication_verify');
          }
        }
      }
    }elseif($action=='email' && wfUser::isSecure()){
      $this->checkAllow($settings, 'change_email');
      $form->set(null, PluginWfForm_v2::bindAndValidate($form->get()));
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".PluginWfForm_v2::getErrors($form->get())."');"));
      }else{
        if(!$this->validatePassword($users->get(wfArray::get($_SESSION, 'user_id').'/password'), $form->get('items/password/post_value'))){
          $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".$i18n->translateFromTheme('Password does not match!')."');"));
        }else{
          $get_key = $this->getKey();
          $this->runSQL($settings, "update account set change_email_email='".$form->get('items/new_email/post_value')."' where id='".wfArray::get($_SESSION, 'user_id')."';");
          $this->runSQL($settings, "update account set change_email_key='".$get_key."' where id='".wfArray::get($_SESSION, 'user_id')."';");
          $this->runSQL($settings, "update account set change_email_date='".date('Y-m-d H:i:s')."' where id='".wfArray::get($_SESSION, 'user_id')."';");
          $script->set(true, 'document.getElementById(\'wf_account_current_email\').style.display=\'none\'');
          $script->set(true, 'document.getElementById(\'div_frm_account_new_email\').style.display=\'none\'');
          $script->set(true, 'document.getElementById(\'div_frm_account_password\').style.display=\'none\'');
          $script->set(true, 'document.getElementById(\'div_frm_account_key\').style.display=\'\'');
          $script->set(true, 'document.getElementById(\'frm_account_save\').value=\''.$i18n->translateFromTheme('Verify').'\'');
          $script->set(true, 'document.getElementById(\'frm_account_action\').value=\'email_verify\'');
          $script->set(true, 'PluginWfAccount2.sendmessage("'.wfArray::get($GLOBALS, 'sys/class').'");');
          $script->set(true, 'PluginWfAccount2.saveForm("frm_account_save", "Check your email for the key!", true);');
          $json->set('success', true);
          $json->set('script', $script->get());
          $_SESSION = wfArray::set($_SESSION, 'plugin/wf/account/send_email/To',   $form->get('items/new_email/post_value'));
          $_SESSION = wfArray::set($_SESSION, 'plugin/wf/account/send_email/Body', 'Key to activate new email is: '.$get_key);
          $this->log('email');
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
      $form->set(null, PluginWfForm_v2::bindAndValidate($form->get()));
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".PluginWfForm_v2::getErrors($form->get())."');"));
      }else{
        // Validate.
        $this->runSQL($settings, "update account set email='".$change_email_email."' where id='".wfArray::get($_SESSION, 'user_id')."';");
        $_SESSION = wfArray::set($_SESSION, 'email', $change_email_email);
        $this->runSQL($settings, "update account set change_email_email=null where id='".wfArray::get($_SESSION, 'user_id')."';");
        $this->runSQL($settings, "update account set change_email_key=null where id='".wfArray::get($_SESSION, 'user_id')."';");
        $this->runSQL($settings, "update account set change_email_date=null where id='".wfArray::get($_SESSION, 'user_id')."';");
        if($settings->get('on_signin/script')){
          $json->set('script', array($settings->get('on_signin/script')));
        }else{
          $json->set('script', array("location.href='/';"));
        }              
        $this->log('email_verify');
      }
    }elseif($action=='password' && wfUser::isSecure()){
      $this->checkAllow($settings, 'change_password');
      $form->set(null, PluginWfForm_v2::bindAndValidate($form->get()));
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".PluginWfForm_v2::getErrors($form->get())."');"));
      }else{
        if($settings->get('on_signin/script')){
          $json->set('script', array($settings->get('on_signin/script')));
        }else{
          $json->set('script', array("location.href='/';"));
        }              
        $this->runSQL($settings, "update account set password='".wfCrypt::getHashAndSaltAsString( $form->get('items/new_password/post_value'))."' where id='".wfArray::get($_SESSION, 'user_id')."';");
        $this->log('password');
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
  private function log($type, $user_id = null){
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
      $phpmailer_settings = $settings->get('phpmailer');
      if(is_null($phpmailer_settings)){
        throw new Exception('Error in PluginWfAccount2::page_sendmessage, param phpmailer is not set.');
      }
      $phpmailer_settings = wfSettings::getSettingsFromYmlString($phpmailer_settings);
      $phpmailer_settings = new PluginWfArray($phpmailer_settings);
      $phpmailer_settings->set('To', wfArray::get($_SESSION, 'plugin/wf/account/send_email/To'));
      $phpmailer_settings->set('Body', wfArray::get($_SESSION, 'plugin/wf/account/send_email/Body'));
      wfPlugin::includeonce('wf/phpmailer');
      $wf_phpmailer = new PluginWfPhpmailer();
      $wf_phpmailer->send($phpmailer_settings->get());
      $_SESSION = wfArray::setUnset($_SESSION, 'plugin/wf/account/send_email');
      $json->set('success', true);
    }elseif(wfArray::get($_SESSION, 'plugin/wf/account/send_sms/To') && wfArray::get($_SESSION, 'plugin/wf/account/send_sms/Body')){
      $sms = new PluginWfArray(wfArray::get($_SESSION, 'plugin/wf/account/send_sms'));
      wfPlugin::includeonce('sms/pixie_v1');
      $default = new PluginWfArray();
      $default->set('account', $settings->get('sms_pixie/account'));
      $default->set('sender', $settings->get('sms_pixie/sender'));
      $default->set('pwd', $settings->get('sms_pixie/pwd'));
      $default->set('to', $sms->get('To'));
      $default->set('message', $sms->get('Body'));
      $str = PluginSmsPixie_v1::send($default);
      $_SESSION = wfArray::setUnset($_SESSION, 'plugin/wf/account/send_sms');
      $json->set('success', true);
    }else{
      $json->set('script/0', "alert('There was a problem to send email!');");
    }
    exit(json_encode($json->get()));
  }
  public function page_password(){
    $this->init_page();
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    $this->checkAllow($settings, 'change_password');
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/wf/account2/layout');
    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/page/password.yml';
    $page = wfFilesystem::loadYml($filename);
    $form = new PluginWfYml('/plugin/wf/account2/form/password.yml');
    $form->set('url', '/'.wfArray::get($GLOBALS, 'sys/class').'/action');
    if(!$this->ajax){
    }else{
      $form->setUnset('buttons');
    }
    $page = wfArray::set($page, 'content/login_form/innerHTML/frm_login/data/data', $form->get());
    wfDocument::mergeLayout($page);
  }
  /**
  User will immediately be signed out when they load this page.
  */
  public function page_signout(){
    wfEvent::run('signout');
    /**
     * If we got the theme session we preserve it.
     */
    $theme = wfArray::get($_SESSION, 'theme');
    session_destroy();
    if($theme){
      /**
       * If theme is set we start a new session with it.
       */
      session_start();
      $_SESSION['theme'] = $theme;
    }
    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/page/signout.yml';
    $page = wfFilesystem::loadYml($filename);
    if(wfRequest::get('auto')){
      $page = wfArray::set($page, 'content/script/settings/disabled', false);
    }
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/wf/account2/layout');
    wfDocument::mergeLayout($page);
  }
  public function sign_in($key, $users, $settings){
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
        wfPlugin::includeonce('wf/i18n');
        $i18n = new PluginWfI18n();
        $form = wfArray::set($form, "items/$field/is_valid", false);
        $form = wfArray::set($form, "items/$field/errors/", $i18n->translateFromTheme('Password does not match!'));
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
