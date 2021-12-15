# Buto-Plugin-WfAccount2
To hande signin, signout, create, change email or password using MySql database.

## Theme settings

```
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
        WordWrap: '255'
```
Param foreing_email is used to get email from other table than account.email.
```
      foreing_email_OPTIONAL:
        table: memb_account
        field: email
        join: account_id
```
One could restrict sign in due to roles. 
Param date from/to is optional.
In this example only user with role webmaster can sign in between 2021-12-01 and 2021-12-31.
```
      allow:
        signin_role:
          roles:
            - webmaster
          date:
            from: '2021-12-01'
            to: '2021-12-31'
```


## Auto sign in
To activate auto sign in set remember param along with signin event.
Set param remember_signout_username to remember username on sign out.

```
plugin_modules:
  account:
    plugin: 'wf/account2'
    settings:
      allow:
        remember: true
        remember_signout_username: true
```

```
events:
  load_theme_config_settings_after:
    -
      plugin: 'wf/account2'
      method: signin
```

## Schema

```
/plugin/wf/account2/mysql/schema.yml
```

## Sign in external method

```
wfPlugin::includeonce('wf/account2');
$obj = new PluginWfAccount2();
$obj->sign_in_external('_a_account_id_', '_optional_log_tag_');
```


## Links

```
/account/signin
/account/signout
/account/create
```

## Data
SQL to create a webmaster account.
```
/mysql/account_insert_webmaster.sql
```
