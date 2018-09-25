# Buto-Plugin-WfAccount2
To hande signin, signout, create, change email or password using MySql database.



## Auto sign in
To activate auto sign in set remember param along with signin event.

```
plugin_modules:
  account:
    plugin: 'wf/account2'
    settings:
      allow:
        remember: true
```

```
events:
  load_theme_config_settings_after:
    -
      plugin: 'wf/account2'
      method: signin
```

