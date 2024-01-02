# Buto-Plugin-WfAccount2



<a name="key_0"></a>

## Settings

<pre><code>plugin_modules:
  account:
    plugin: 'wf/account2'
    settings:
      on_signin:
        script: "location.href='/some/page';" 
      on_activate:
        script: "location.href='/some/page';"
      allow:
        signin: true
        signin_method: null(email and username)/email/username
        registration: true
        change_email: true
        change_username: true
        change_password: true
        two_factor_authentication: true
        remember: false
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
        WordWrap: '255'</code></pre>
<p>Param foreing_email is used to get email from other table than account.email.</p>
<pre><code>      foreing_email_OPTIONAL:
        table: memb_account
        field: email
        join: account_id</code></pre>
<p>One could restrict sign in due to roles. 
Param date from/to is optional.
In this example only user with role webmaster can sign in between 2021-12-01 and 2021-12-31.</p>
<pre><code>      allow:
        signin_role:
          roles:
            - webmaster
          date:
            from: '2021-12-01'
            to: '2021-12-31'</code></pre>
<p>Auto sign in.</p>
<ul>
<li>To activate auto sign in set remember param along with signin event.</li>
<li>Set param remember_signout_username to remember username on sign out.</li>
</ul>
<pre><code>plugin_modules:
  account:
    plugin: 'wf/account2'
    settings:
      allow:
        remember: true
        remember_signout_username: true</code></pre>
<pre><code>events:
  load_theme_config_settings_after:
    -
      plugin: 'wf/account2'
      method: signin</code></pre>

<a name="key_1"></a>

## Schema

<pre><code>/plugin/wf/account2/mysql/schema.yml</code></pre>

<a name="key_2"></a>

## Methods



<a name="key_2_0"></a>

### sign_in_external

<pre><code>wfPlugin::includeonce('wf/account2');
$obj = new PluginWfAccount2();
$obj-&gt;sign_in_external('_a_account_id_', '_optional_log_tag_');</code></pre>

<a name="key_3"></a>

## Links

<pre><code>/account/signin
/account/signout
/account/create</code></pre>

<a name="key_4"></a>

## Data

<p>SQL to create a webmaster account.</p>
<pre><code>/mysql/account_insert_webmaster.sql</code></pre>

<a name="key_5"></a>

## Session

<pre><code>secure: true
email: _
username: _
user_id: _
role:
  - webmaster
  - webadmin
details:
  log_count_except_this: 2
  last_login_before_today: null
  days_login_before_today: null
theme_data:
  version: 1.36.0</code></pre>

