<?php
class PluginWfAccount2{
  private $ajax = false;
  private $settings = null;
  function __construct() {
    wfPlugin::includeonce('form/form_v1');
    wfPlugin::enable('form/form_v1');
    /**
     * 
     */
    wfPlugin::includeonce('wf/array');
    $this->settings = new PluginWfArray(wfPlugin::getModuleSettings());
  }
  /**
  Page with a create form.  
  */
  public function page_create(){
    /**
     * 
     */
    $this->init_page();
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    $this->checkAllow($settings, 'registration');
    wfGlobals::setSys('layout_path', '/plugin/wf/account2/layout');
    /**
     * 
     */
    if(wfUser::isSecure()){
      /**
       * User is already verified.
       */
      $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/page/signedin.yml';
      $page = wfFilesystem::loadYml($filename);
      $page = wfArray::set($page, 'content/signin/innerHTML/signout/attribute/href', '/'.wfArray::get($GLOBALS, 'sys/class').'/signout');
      wfDocument::mergeLayout($page);
    }else{
      /**
       * 
       */
      $page = new PluginWfYml(__DIR__.'/page/create.yml');
      $form = new PluginWfYml('/plugin/wf/account2/form/create.yml');
      $form->set('url', '/'.wfArray::get($GLOBALS, 'sys/class').'/action');
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
      $page->set('content/login_form/innerHTML/frm_login/data/data', $form->get());
      /**
       * 
       */
      $page->setByTag(array('ajax' => $this->ajax));
      /**
       * 
       */
      wfDocument::mergeLayout($page->get());
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
    wfPlugin::includeonce('i18n/translate_v1');
    /**
     * Enable.
     */
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
    wfGlobals::setSys('layout_path', '/plugin/wf/account2/layout');
    $page = new PluginWfYml(__DIR__.'/page/signin.yml');
    $form = $this->getFormSignin($settings);
    /**
     * Show create account button.
     */
    if($settings->get('allow/registration')){
      $page->set('content/create/settings/disabled', false);
    }
    /**
     * 
     */
    $page->set('content/login_form/innerHTML/frm_login/data/data', $form->get());
    /**
     * Flash.
     */
    if(wfPlugin::flashHas('wf/account2', 'signin')){
      $page->set('content/flash/innerHTML', wfPlugin::flashGet('wf/account2', 'signin'));
    }
    /**
     * 
     */
    $page->setByTag(array('ajax' => $this->ajax));
    /**
     * 
     */
    wfDocument::mergeLayout($page->get());
  }
  public function api_sign_check(){
    $result = new PluginWfArray();
    if(wfUser::hasRole('client')){
      $user = wfUser::getSession();
      $result->set('email', $user->get('email'));
      $result->set('username', $user->get('username'));
      $result->set('user_id', $user->get('user_id'));
      $result->set('role', $user->get('role'));
      $result->set('rights', $user->get('rights'));
      $result->set('theme_data/version', $user->get('theme_data/version'));
      $result->set('theme_data/theme', wfGlobals::getTheme());
    }
    return array('data' => $result->get());
  }
  public function api_sign_in($email, $password, $settings){
    /**
     * 
     */
    if(!$email || !$password){
      $error = array();
      $error[] = array('message' => 'Some credentials are missing!');
      return array('error' => $error);
    }
    /**
     * users
     */
    $users = $this->getUsers($settings);
    /**
     * user_id
     */
    $user_id = $this->getUserId($users->get(), $email);
    /**
     * validate_password
     */
    $validate_password = $this->validatePassword($users->get($user_id.'/password'), $password);
    /**
     * activated
     */
    $activated = $users->get($user_id.'/activated');
    /**
     * result
     */
    $result = new PluginWfArray();
    /**
     * sign_in
     */
    if($validate_password && $activated){
      $this->sign_in($user_id, $users->get(), $settings);
      $this->log('signin', null, $settings);
      $user = wfUser::getSession();
      $result->set('email', $user->get('email'));
      $result->set('username', $user->get('username'));
      $result->set('user_id', $user->get('user_id'));
      $result->set('role', $user->get('role'));
      $result->set('rights', $user->get('rights'));
      $result->set('theme_data/version', $user->get('theme_data/version'));
      $result->set('theme_data/theme', wfGlobals::getTheme());
    }
    /**
     * 
     */
    $error = array();
    if(!$validate_password){
      $error[] = array('message' => 'Credentials seems to be wrong!');
    }
    if(!$activated && $validate_password){
      $error[] = array('message' => 'There seems to be a problem with the account!');
    }
    return array('data' => $result->get(), 'error' => $error);
  }
  public function page_email(){
    /**
     * 
     */
    $this->init_page();
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    $this->checkAllow($settings, 'change_email');
    wfGlobals::setSys('layout_path', '/plugin/wf/account2/layout');
    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/page/email.yml';
    $page = wfFilesystem::loadYml($filename);
    $form = new PluginWfYml('/plugin/wf/account2/form/email.yml');
    $form->set('url', '/'.wfArray::get($GLOBALS, 'sys/class').'/action');
    /**
     * Cancel button.
     */
    if($this->ajax){
      $form->set('buttons/btn_cancel/attribute/href', '#!');
      $form->set('buttons/btn_cancel/attribute/onclick', "$('.modal').modal('hide');");
    }
    $page = wfArray::set($page, 'content/login_form/innerHTML/frm_login/data/data', $form->get());
    wfDocument::mergeLayout($page);
  }
  public function page_username(){
    $this->init_page();
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    $this->checkAllow($settings, 'change_username');
    wfGlobals::setSys('layout_path', '/plugin/wf/account2/layout');
    $page = new PluginWfYml(__DIR__.'/page/username.yml');
    $form = new PluginWfYml('/plugin/wf/account2/form/username.yml');
    $form->set('url', '/'.wfArray::get($GLOBALS, 'sys/class').'/action');
    $form->setByTag(wfUser::getSession()->get());
    /**
     * Cancel button.
     */
    if($this->ajax){
      $form->set('buttons/btn_cancel/attribute/href', '#!');
      $form->set('buttons/btn_cancel/attribute/onclick', "$('.modal').modal('hide');");
    }
    $page->setByTag(array('form' => $form->get()));
    wfDocument::mergeLayout($page->get());
  }
  private function getFormSignin($settings){
    $form = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/form/signin.yml');
    /**
     * Set url.
     */
    $form->set('url', '/'.wfArray::get($GLOBALS, 'sys/class').'/action');
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
     * Change email label if signin_method is email.
     */
    if($settings->get('allow/signin_method')=='email'){
      $form->set('items/email/label', 'Email');
    }elseif($settings->get('allow/signin_method')=='username'){
      $form->set('items/email/label', 'Username');
    }elseif(is_null($settings->get('allow/signin_method'))){
      /**
       * Both email and username is used.
       */
    }else{
      throw new Exception('PluginWfAccount2 says: Param allow/signin_method has invalid value.');
    }
    /**
     * Add email validator if signin_method is email.
     */
    if($settings->get('allow/signin_method')=='email'){
      $form->set('items/email/validator/', array('plugin' => 'form/form_v1', 'method' => 'validate_email'));
    }
    /**
     * Set username.
     */
    $form->set('items/email/default', $this->cookie_get()->get('wf_account2_1'));
    return $form;
  }
  public function page_action(){
    if(!wfRequest::isPost()){
      exit('');
    }
    $this->init_page();
    /**
     * i18n.
     */
    $i18n = new PluginI18nTranslate_v1();
    $i18n->path = '/plugin/wf/account2/i18n';
    /**
     * 
     */
    $settings = $this->settings;
    $action = wfRequest::get('action');
    $script = new PluginWfArray();
    $json = new PluginWfArray();
    $json->set('success', false);
    $users = $this->getUsers($settings);
    $uid = wfCrypt::getUid();
    if(($action=='create' || $action=='activate')){
      $form = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/form/create.yml');
    }elseif($action=='signin' || $action=='two_factor_authentication'){
      $form = $this->getFormSignin($settings);
    }elseif($action=='email' || $action=='email_verify'){
      $form = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/form/email.yml');
    }elseif($action=='username'){
      $form = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/form/username.yml');
    }elseif($action=='password'){
      $form = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/form/password.yml');
    }
    if($action=='create'){
      $this->checkAllow($settings, 'registration');
      $activate_key = $this->getKey();
      $f = new PluginFormForm_v1();
      $f->data = $form->get();
      $f->bindAndValidate();
      $form->set(null, $f->data);
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".$f->getErrors()."');"));
      }else{
        /**
         * 
         */
        $user_id = $this->getUserId($users->get(), $form->get('items/email/post_value'), true);
        /**
         * 
         */
        if(!$user_id){
          $user_id = wfCrypt::getUid();
          $username = $this->get_username();
          $this->runSQL($settings, "insert into account (id, email, password, username) values ('$user_id', '".$form->get('items/email/post_value')."', '".wfCrypt::getHashAndSaltAsString($form->get('items/password/post_value'))."', '".$username."');");
          $this->log('create', $user_id);
          wfEvent::run('account_create');
        }else{
          $this->runSQL($settings, "update account set activate_password='".wfCrypt::getHashAndSaltAsString($form->get('items/password/post_value'))."' where id='$user_id';");
          $this->runSQL($settings, "update account set activate_date='".date('Y-m-d H:i:s')."' where id='$user_id';");
          $this->log('recreate', $user_id);
          wfEvent::run('account_recreate');
        }
        /**
         * Get user
         */
        $user = $this->getUser($settings, $user_id);
        /**
         * 
         */
        $this->runSQL($settings, "update account set activate_key='".$activate_key."' where id='$user_id';");
        // Script
        $script->set(true, 'document.getElementById(\'div_frm_account_email\').style.display=\'none\'');
        $script->set(true, 'document.getElementById(\'div_frm_account_password\').style.display=\'none\'');
        $script->set(true, 'document.getElementById(\'div_frm_account_key\').style.display=\'\'');
        $script->set(true, 'document.getElementById(\'frm_account_save\').innerHTML=\''.$i18n->translateFromTheme('Verify').'\'');
        $script->set(true, 'document.getElementById(\'frm_account_action\').value=\'activate\'');
        $script->set(true, 'document.getElementById(\'btn_goto_signin\').style=\'display:none\'');
        $script->set(true, 'PluginWfAccount2.sendmessage("'.wfArray::get($GLOBALS, 'sys/class').'");');
        $script->set(true, 'PluginWfAccount2.saveForm("frm_account_save", "'.$i18n->translateFromTheme('Check your email for the key!').'", true);');
        $json->set('success', true);
        $json->set('script', $script->get());
        // Set params to send mail via page_sendmessage().
        $_SESSION = wfArray::set($_SESSION, 'plugin/wf/account/send_email/To',   $form->get('items/email/post_value'));
        $Body = $i18n->translateFromTheme('Key to activate account is:').' '.$activate_key.'<br>';
        $Body .= $i18n->translateFromTheme('Your user name is:').' '.$user->get('username').'<br>';
        $_SESSION = wfArray::set($_SESSION, 'plugin/wf/account/send_email/Body', $Body);
        $_SESSION = wfArray::set($_SESSION, 'plugin/wf/account/send_email/Subject', 'Activate account');
      }
    }elseif($action=='activate'){
      $this->checkAllow($settings, 'registration');
      $form->set('items/key/mandatory', true);
      $f = new PluginFormForm_v1();
      $f->data = $form->get();
      $f->bindAndValidate();
      $form->set(null, $f->data);
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".$f->getErrors()."');"));
      }else{
        $user_id = $this->getUserId($users->get(), $form->get('items/email/post_value'), true);
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
       * 
       */
      
      $f = new PluginFormForm_v1();
      $f->data = $form->get();
      $f->bindAndValidate();
      $form->set(null, $f->data);
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '". $f->getErrors() ."');"));
        $json->set('errors', $f->getErrors());
      }else{
        $user_id = $this->getUserId($users->get(), $form->get('items/email/post_value'));
        if(!$user_id){
          $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".$i18n->translateFromTheme('Username or password does not match!')."');"));
          $json->set('errors', $i18n->translateFromTheme('Username or password does not match!'));
        }else{
          if($this->validatePassword($users->get($user_id.'/password'), $form->get('items/password/post_value'))){
            $users->set("$user_id/roles", $this->get_roles($user_id, $settings));
            $users->set("$user_id/signin_role", $this->get_signin_role($users, $user_id, $settings));
            if(!$users->get($user_id.'/activated')){
              $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".$i18n->translateFromTheme('User is not activated!')."');"));
              $json->set('errors', $i18n->translateFromTheme('User is not activated!'));
            }elseif($users->get($user_id.'/signin_role')){
              /**
               * message
               */
              $message = null;
              if($settings->get('allow/signin_role/message')){
                $message = $settings->get('allow/signin_role/message');
              }else{
                $message = $i18n->translateFromTheme('You are not able to sign in due to role restriction.');
              }
              /**
               * 
               */
              $json->set('script', array("alert('".$message."');"));
              $json->set('errors', $message);
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
                  $script->set(true, "document.getElementById('div_frm_account_show_password').style.display='none';");
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
            $json->set('errors', $i18n->translateFromTheme('Username or password does not match!'));
          }
        }
      }
    }elseif($action=='two_factor_authentication'){
      /** Tow-factor authentication **/
      $f = new PluginFormForm_v1();
      $f->data = $form->get();
      $f->bindAndValidate();
      $form->set(null, $f->data);
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
      $f = new PluginFormForm_v1();
      $f->data = $form->get();
      $f->bindAndValidate();
      $form->set(null, $f->data);
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".$f->getErrors()."');"));
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
          $script->set(true, 'PluginWfAccount2.saveForm("frm_account_save", "'.$i18n->translateFromTheme('Check your email for the key!').'", true);');
          $json->set('success', true);
          $json->set('script', $script->get());
          $_SESSION = wfArray::set($_SESSION, 'plugin/wf/account/send_email/To',   $form->get('items/new_email/post_value'));
          $_SESSION = wfArray::set($_SESSION, 'plugin/wf/account/send_email/Body', 'Key to activate new email is: '.$get_key);
          $this->log('email');
        }
      }
    }elseif($action=='username' && wfUser::isSecure()){
      $this->checkAllow($settings, 'change_username');
      $f = new PluginFormForm_v1();
      $f->data = $form->get();
      $f->bindAndValidate();
      $form->set(null, $f->data);
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".$f->getErrors()."');"));
      }else{
        $this->db_account_update_username();
        $_SESSION['username'] = wfRequest::get('username');
        $script->set(true, "location.href='/'");
        $json->set('success', true);
        $json->set('script', $script->get());
        $this->log('username');
      }
    }elseif($action=='email_verify' && wfUser::isSecure()){
      $this->checkAllow($settings, 'change_email');
      $user = $this->getUser($settings, wfArray::get($_SESSION, 'user_id'));
      $change_email_key = $user->get('change_email_key');
      $change_email_email = $user->get('change_email_email');
      $form->set('items/key/mandatory', true);
      $validator = new PluginWfArray();
      $validator->set('plugin', 'wf/form_v2');
      $validator->set('method', 'validate_equal');
      $validator->set('data/value', $change_email_key);
      $form->set('items/key/validator/', $validator->get());
      $f = new PluginFormForm_v1();
      $f->data = $form->get();
      $f->bindAndValidate();
      $form->set(null, $f->data);
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".$f->getErrors()."');"));
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
      $f = new PluginFormForm_v1();
      $f->data = $form->get();
      $f->bindAndValidate();
      $form->set(null, $f->data);
      if(!$form->get('is_valid')){
        $json->set('script', array("PluginWfAccount2.saveForm('frm_account_save', '".$f->getErrors()."');"));
      }else{
        if($settings->get('on_signin/script')){
          $json->set('script', array($settings->get('on_signin/script')));
        }else{
          $json->set('script', array("alert('".$i18n->translateFromTheme('Password was updated!')."');location.href='/';"));
        }
        $sql = "update account set password='".wfCrypt::getHashAndSaltAsString( $form->get('items/new_password/post_value'))."' where id='".wfArray::get($_SESSION, 'user_id')."';";
        $this->runSQL($settings, $sql);
        $this->log('password');
      }
    }
    exit(json_encode($json->get()));
  }
  /**
   * Validate password.
   * @param string $password Password in db.
   * @param string $post_password Password to validate.
   * @return boolean True if password match.
   */
  private function validatePassword($password, $post_password){
    /**
     * match_crypt
     */
    $match_crypt  = wfCrypt::isValid($post_password,          $password);
    /**
     * Plain
     * To prevent sign in with encrypted password we has to remove the space limiter.
     */
    $match_plain = false;
    $post_password = wfPhpfunc::str_replace(' ', '_', $post_password);
    if($post_password==$password){
      $match_plain = true;
    }
    /**
     * 
     */
    if($match_crypt || $match_plain){
      return true;
    }else{
      return false;
    }
  }
  /**
   * Log into db.account_log.
   * @param string $type
   * @param string $user_id
   * @param mixed $settings Null or PluginWfArray object.
   */
  private function log($type, $user_id = null, $settings = null){
    if(is_null($settings)){
      $settings = new PluginWfArray(wfPlugin::getModuleSettings('wf/account2'));
    }
    wfPlugin::includeonce('wf/mysql');
    $mysql = new PluginWfMysql();
    $mysql->open($settings->get('mysql'));
    if(!$user_id){
      $user_id = wfArray::get($_SESSION, 'user_id');
    }
    $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
    $REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
    $session_id = session_id();
    $mysql->runSql("insert into account_log (account_id, type, date, HTTP_USER_AGENT, REMOTE_ADDR, session_id) values ('".$user_id."', '$type', '".date('Y-m-d H:i:s')."', '$HTTP_USER_AGENT', '$REMOTE_ADDR', '$session_id');");
  }
  private function getKey(){
    return rand(100000, 999999);
  }
  /**
   * Get user id.
   * @param array $users
   * @param string $email
   * @return string
   */
  private function getUserId($users, $email, $activate = false){
    $user_id = null;
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    $signin_method = $settings->get('allow/signin_method');
    foreach ($users as $key => $value) {
      if(!$activate){
        /**
         * Check email.
         */
        if(!$signin_method || $signin_method=='email'){
          if(strtolower((string)$email)==strtolower((string)wfArray::get($value, 'email'))){
            $user_id = $key;
            break;
          }
        }
        /**
         * Check username.
         */
        if(!$signin_method || $signin_method=='username'){
          if($email==wfArray::get($value, 'username')){
            $user_id = $key;
            break;
          }
        }
      }else{
        /**
         * Activate always with email
         */
        if(strtolower((string)$email)==strtolower((string)wfArray::get($value, 'email'))){
          $user_id = $key;
          break;
        }
      }
    }
    /**
     * 
     */
    return $user_id;
  }
  private function getUsers($settings){
    wfPlugin::includeonce('wf/mysql');
    $mysql = new PluginWfMysql();
    $mysql->open($settings->get('mysql'));
    if($settings->get('foreing_email')){
      $table = $settings->get('foreing_email/table');
      $field = $settings->get('foreing_email/field');
      $join = $settings->get('foreing_email/join');
      $sql = <<<ABC
        select 
        a.id, 
        f.$field as email, 
        a.password, 
        a.activated, 
        a.phone, 
        a.username,
        a.language 
        from account as a
        inner join $table as f on a.id=f.$join
        ;
ABC;
              
    }else{
      $sql = "select id, email, password, activated, phone, username, language from account;";
    }
    $rs = $mysql->runSql($sql);
    return new PluginWfArray($rs['data']);
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
  private function db_account_update_username(){
    wfPlugin::includeonce('wf/mysql');
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    $mysql = new PluginWfMysql();
    $mysql->open($settings->get('mysql'));
    $sql = new PluginWfYml(__DIR__.'/mysql/sql.yml', 'account_update_username');
    $mysql->execute($sql->get());
    return null;
  }
  private function db_account_username_exist(){
    wfPlugin::includeonce('wf/mysql');
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    $mysql = new PluginWfMysql();
    $mysql->open($settings->get('mysql'));
    $sql = new PluginWfYml(__DIR__.'/mysql/sql.yml', 'account_username_exist');
    $mysql->execute($sql->get());
    return $mysql->getOne();
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
      /**
       * Email
       */
      $phpmailer_settings = $settings->get('phpmailer');
      if(is_null($phpmailer_settings)){
        throw new Exception('Error in PluginWfAccount2::page_sendmessage, param phpmailer is not set.');
      }
      $phpmailer_settings = wfSettings::getSettingsFromYmlString($phpmailer_settings);
      $phpmailer_settings = new PluginWfArray($phpmailer_settings);
      /**
       * 
       */
      if(wfGlobals::get('settings/application/title')){
        $phpmailer_settings->set('FromName', wfGlobals::get('settings/application/title'));
      }
      $phpmailer_settings->set('Subject', 'Change email');
      if(wfArray::get($_SESSION, 'plugin/wf/account/send_email/Subject')){
        $phpmailer_settings->set('Subject', wfArray::get($_SESSION, 'plugin/wf/account/send_email/Subject'));
      }
      $phpmailer_settings->set('To', wfArray::get($_SESSION, 'plugin/wf/account/send_email/To'));
      $phpmailer_settings->set('Body', wfArray::get($_SESSION, 'plugin/wf/account/send_email/Body'));
      wfPlugin::includeonce('wf/phpmailer');
      $wf_phpmailer = new PluginWfPhpmailer();
      $result = $wf_phpmailer->send($phpmailer_settings->get());
      $result = new PluginWfArray($result);
      $_SESSION = wfArray::setUnset($_SESSION, 'plugin/wf/account/send_email');
      $json->set('success', $result->get('success'));
      $json->set('alert', $result->get('alert'));
    }elseif(wfArray::get($_SESSION, 'plugin/wf/account/send_sms/To') && wfArray::get($_SESSION, 'plugin/wf/account/send_sms/Body')){
      /**
       * SMS
       */
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
    /**
     * 
     */
    $this->init_page();
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    $this->checkAllow($settings, 'change_password');
    wfGlobals::setSys('layout_path', '/plugin/wf/account2/layout');
    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/account2/page/password.yml';
    $page = wfFilesystem::loadYml($filename);
    $form = new PluginWfYml('/plugin/wf/account2/form/password.yml');
    $form->set('url', '/'.wfArray::get($GLOBALS, 'sys/class').'/action');
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
    wfDocument::mergeLayout($page);
  }
  /**
  User will immediately be signed out when they load this page.
  */
  public function page_signout(){
    wfEvent::run('signout');
    $this->init_page();
    $settings = new PluginWfArray(wfPlugin::getModuleSettings());
    /**
     * If we got the theme session we preserve it.
     */
    $theme = wfArray::get($_SESSION, 'theme');
    session_destroy();
    $this->cookie_forget($settings);
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
    wfGlobals::setSys('layout_path', '/plugin/wf/account2/layout');
    wfDocument::mergeLayout($page);
  }
  public function api_sign_out($settings){
    wfEvent::run('signout');
    $theme = wfArray::get($_SESSION, 'theme');
    session_unset();
    session_destroy();
    $this->cookie_forget($settings);
    if($theme){
      /**
       * If theme is set we start a new session with it.
       */
      session_start();
      $_SESSION['theme'] = $theme;
    }
    return array('data' => array());
  }
  private function cookie_forget($settings){
    if(headers_sent()){
      return null;
    }
    /**
     * 
     */
    wfPlugin::includeonce('php/cookie');
    $cookie = new PluginPhpCookie();
    /**
     * 
     */
    if(!$settings->get('allow/remember_signout_username')){
      $cookie->del('wf_account2_1');
    }
    $cookie->del('wf_account2_2');
    $cookie->del('wf_account2_3');
    return null;
  }
  private function cookie_remember($settings, $user){
    if(headers_sent()){
      return null;
    }
    /**
     * 
     */
    wfPlugin::includeonce('php/cookie');
    $cookie = new PluginPhpCookie();
    /**
     * 
     */
    if($settings->get('allow/remember')){
      if(wfRequest::get('email')){
        /**
         * Email could be username also.
         */
        $cookie->set('wf_account2_1', wfRequest::get('email'));
      }else{
        /**
         * Check signin method...
         */
        if(!$settings->get('allow/signin_method')){
          if($user->get('username')){
            $cookie->set('wf_account2_1', $user->get('username'));
          }else{
            $cookie->set('wf_account2_1', $user->get('email'));
          }
        }elseif($settings->get('allow/signin_method')=='email'){
          $cookie->set('wf_account2_1', $user->get('email'));
        }elseif($settings->get('allow/signin_method')=='username'){
          $cookie->set('wf_account2_1', $user->get('username'));
        }
      }
      $cookie->set('wf_account2_2', wfCrypt::getHashAndSaltAsString($user->get('password')));
      $cookie->set('wf_account2_created_at', date('ymdHis'));
    }
    return null;
  }
  private function cookie_get(){
    return new PluginWfArray($_COOKIE);
  }
  /**
   * 
   */
  public function event_signin(){
    /**
     * Skip if user already in.
     */
    if(wfUser::hasRole('client')){
      return null;
    }
    /**
     * Skip if no cookies 1, 2 or has 3.
     */
    if(!isset($_COOKIE['wf_account2_1']) || !isset($_COOKIE['wf_account2_2']) || isset($_COOKIE['wf_account2_3']) ){
      return null;
    }
    /**
     * Skip if settings is incorrect.
     */
    $this->init_page();
    wfPlugin::includeonce('wf/array');
    $settings = new PluginWfArray(wfPlugin::getModuleSettings('wf/account2'));
    if(!$settings->get('allow/remember')){
      return null;
    }
    if(!$settings->get('allow/signin')){
      return null;
    }
    /**
     * 
     */
    wfPlugin::includeonce('php/cookie');
    $cookie = new PluginPhpCookie();
    /**
     *  Check if user match by email. 
     */
    $users = $this->getUsers($settings);
    $user_id = $this->getUserId($users->get(), $_COOKIE['wf_account2_1']);
    if(!$user_id){
      $cookie->set('wf_account2_3', '1');
      return null;
    }
    /**
     * signin_role
     */
    $users->set("$user_id/roles", $this->get_roles($user_id, $settings));
    $users->set("$user_id/signin_role", $this->get_signin_role($users, $user_id, $settings));
    if($users->get("$user_id/signin_role")){
      return null;
    }
    /**
     * Validate password.
     */
    if(wfCrypt::isValid($users->get($user_id.'/password'), $_COOKIE['wf_account2_2'])){
      $this->sign_in($user_id, $users->get(), $settings);
      $this->log('auto');
    }else{
      /**
       * Set cookie to not try again on failure.
       */
      $cookie->set('wf_account2_3', '1');
    }
  }
  /**
   * Set session params and run event signin.
   * @param type $account_id
   * @param type $users
   * @param type $settings
   */
  public function sign_in($account_id, $users, $settings){
    wfPlugin::includeonce('wf/array');
    $user = new PluginWfArray($users[$account_id]);
    $this->cookie_forget($settings);
    $this->cookie_remember($settings, $user);
    $_SESSION['secure']=true;
    $_SESSION['email']=$user->get('email');
    $_SESSION['username']=$user->get('username');
    $_SESSION['user_id']=$account_id;
    $_SESSION['role'] = $this->get_roles($account_id, $settings);
    $_SESSION['details'] = $this->db_account_details($settings)->get();
    if($user->get('theme')){
      $_SESSION['theme'] = $user->get('theme');
    }
    if($user->get('language')){
      $_SESSION['i18n']['language'] = $user->get('language');
    }
    /**
     * theme_data/version
     */
    $theme_manifest = new PluginWfYml('/theme/[theme]/config/manifest.yml');
    wfUser::setSession('theme_data/version', $theme_manifest->get('version'));
    wfUser::setSession('theme_data/theme', wfGlobals::getTheme());
    /**
     *
     */
    wfEvent::run('signin');
  }
  /**
   * Sign in via account_id.
   * @param type $account_id
   */
  public function sign_in_external($account_id, $log_tag = null){
    unset($_SESSION['plugin']);
    $settings = new PluginWfArray(wfPlugin::getModuleSettings('wf/account2'));
    $users = $this->getUsers($settings);
    $this->sign_in($account_id, $users->get(), $settings);
    if(!$log_tag){
      $this->log('sign_in_external');
    }else{
      $this->log($log_tag);
    }
  }
  public function verify_account($data){
    $data = new PluginWfArray($data);
    $settings = new PluginWfArray(wfPlugin::getModuleSettings('wf/account2'));
    $users = $this->getUsers($settings);
    $user_id = $this->getUserId($users->get(), $data->get('username'));
    if(!$user_id){
      return null;
    }else{
      if($this->validatePassword($users->get($user_id.'/password'), $data->get('password'))){
        return new PluginWfArray($users->get($user_id));
      }else{
        return null;
      }
    }
  }
  /**
   * Get user roles from db.
   * @param type $key
   * @param type $settings
   * @return type
   */
  private function get_roles($account_id, $settings){
    $role = $this->db_account_roles($account_id, $settings);
    $temp = array();
    foreach ($role as $key2 => $value2) {
      $temp[] = $value2['role'];
    }
    return $temp;
  }
  private function db_account_roles($account_id, $settings){
    wfPlugin::includeonce('wf/mysql');
    $mysql = new PluginWfMysql();
    $mysql->open($settings->get('mysql'));
    $sql = new PluginWfYml(__DIR__.'/mysql/sql.yml', 'account_roles');
    $sql->setByTag(array('account_id' => $account_id));
    $mysql->execute($sql->get());
    $rs = $mysql->getMany();
    return $rs;
  }
  private function db_account_details($settings){
    wfPlugin::includeonce('wf/mysql');
    $mysql = new PluginWfMysql();
    $mysql->open($settings->get('mysql'));
    $sql = new PluginWfYml(__DIR__.'/mysql/sql.yml', 'account_details');
    $mysql->execute($sql->get());
    $rs = $mysql->getOne();
    return $rs;
  }
  private function get_signin_role($users, $user_id, $settings){
    /**
     * Restrict is true as default.
     */
    $return = true;
    /*
     * allow/signin_role (if set)
     */
    if($settings->get('allow/signin_role')){
      /*
       * Set date/from, date/to if not set.
       */
      if(!$settings->get('allow/signin_role/date/from')){
        $settings->set('allow/signin_role/date/from', date('Y-m-d'));
      }
      if(!$settings->get('allow/signin_role/date/to')){
        $settings->set('allow/signin_role/date/to', date('Y-m-d').' 23:59:59');
      }
      /*
       * Convert date to time.
       */
      $settings->set('allow/signin_role/time/from', strtotime($settings->get('allow/signin_role/date/from')));
      $settings->set('allow/signin_role/time/now', time());
      $settings->set('allow/signin_role/time/to', strtotime($settings->get('allow/signin_role/date/to')));
      /*
       * If time/now between time/from and time/to.
       */
      if(
        $settings->get('allow/signin_role/time/from') <= $settings->get('allow/signin_role/time/now') && 
        $settings->get('allow/signin_role/time/to') >= $settings->get('allow/signin_role/time/now')){
        if($settings->get('allow/signin_role/roles') && $users->get($user_id.'/roles')){
          foreach($settings->get('allow/signin_role/roles') as $v){
            $i = array_search($v, $users->get($user_id.'/roles'));
            if(wfPhpfunc::strlen($i)){
              /**
               * Find a role match.
               */
              $return = false;
              break;
            }
          }
        }
      }else{
        /**
         * No restriction due to time not match.
         */
        $return = false;
      }
    }else{
      /**
        * No settings.
        */
      $return = false;
    }
    return $return;
  }
  private function get_username(){
    /**
     * Trying to generate and check if username is available a lot of times
     */
    for($i=0; $i<100; $i++){
      $username = $this->generateRandomString(8);
      if(!$this->username_exist($username)){
        return $username;
      }
    }
    /**
     * We should not end up here.
     */
    throw new Exception(__CLASS__.' says: Could not generate new username in method get_username.');
  }
  private function generateRandomString($length = 10) {
    return wfPhpfunc::substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyz', ceil($length/strlen($x)) )),1,$length);
  }
  private function username_exist($username){
    $exist = $this->runSQL($this->settings, "select id as checking from account where username='$username';");
    if($exist->get('0/checking')){
      return true;
    }else{
      return false;
    }
  }
  public function validate_current_password($field, $form){
    // If field is valid we check if password match user.
    if(wfArray::get($form, "items/$field/is_valid")){
      wfPlugin::includeonce('wf/array');
      $settings = new PluginWfArray(wfPlugin::getModuleSettings());
      $users = $this->getUsers($settings);
      if(!$this->validatePassword($users->get(wfArray::get($_SESSION, 'user_id').'/password'), wfArray::get($form, "items/$field/post_value"))){
        wfPlugin::includeonce('i18n/translate_v1');
        $i18n = new PluginI18nTranslate_v1();
        $i18n->path = '/plugin/wf/account2/i18n';
        $form = wfArray::set($form, "items/$field/is_valid", false);
        $form = wfArray::set($form, "items/$field/errors/", $i18n->translateFromTheme('Current password does not match!'));
      }
    }
    return $form;
  }
  public function validate_new_password($field, $form){
    // If field is valid we check if new passwords match.
    if(wfArray::get($form, "items/$field/is_valid")){
      wfPlugin::includeonce('wf/array');
      if(wfArray::get($form, "items/$field/post_value")!=wfArray::get($form, "items/new_password_again/post_value")){
        wfPlugin::includeonce('i18n/translate_v1');
        $i18n = new PluginI18nTranslate_v1();
        $i18n->path = '/plugin/wf/account2/i18n';
        $form = wfArray::set($form, "items/$field/is_valid", false);
        $form = wfArray::set($form, "items/$field/errors/", $i18n->translateFromTheme('New passwords does not match!'));
      }
    }
    return $form;
  }
  public function validate_current_username($field, $form){
    if(wfArray::get($form, "items/$field/is_valid")){
      $exist = $this->db_account_username_exist();
      if($exist->get('count')){
        wfPlugin::includeonce('i18n/translate_v1');
        $i18n = new PluginI18nTranslate_v1();
        $i18n->path = '/plugin/wf/account2/i18n';
        $form = wfArray::set($form, "items/$field/is_valid", false);
        $form = wfArray::set($form, "items/$field/errors/", $i18n->translateFromTheme('Username is in usage!'));
      }
    }
    return $form;
  }
  /**
   * If call signin page via ajax one has to include script.
   */
  public static function widget_include(){
    $path = '/plugin/wf/account2/include.js';
    $time = wfFilesystem::getFiletime(wfGlobals::getWebDir().$path);
    $element = wfDocument::createHtmlElement('script', null, array('src' => '/plugin/wf/account2/include.js?x='.$time));
    wfDocument::renderElement(array($element));
  }
}
