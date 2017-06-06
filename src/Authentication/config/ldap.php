<?php

return [

	/*
    |--------------------------------------------------------------------------
    | LDAP host
    |--------------------------------------------------------------------------
    |
    | The hostname/IP address of the LDAP server
    |
    */
	'host' => env("LDAP_HOST"),

	/*
    |--------------------------------------------------------------------------
    | LDAP base DN
    |--------------------------------------------------------------------------
    |
    | The base DN that will be used for searching for people
    |
    */
    'basedn' => env("LDAP_BASE_DN"),

    /*
    |--------------------------------------------------------------------------
    | LDAP admin DN
    |--------------------------------------------------------------------------
    |
    | The admin DN to use when searching for people. This is only used when
    | user password validation is turned off (so searching can still happen)
    |
    */
    'dn' => env("LDAP_DN", ""),

    /*
    |--------------------------------------------------------------------------
    | LDAP admin password
    |--------------------------------------------------------------------------
    |
    | The admin passwrod to use when searching for people. This is only used
    | when user password validation is turned off (so searching can still happen)
    |
    */
    'password' => env("LDAP_PASSWORD", ""),

    /*
    |--------------------------------------------------------------------------
    | LDAP password validation
    |--------------------------------------------------------------------------
    |
    | True to turn off password validation (and therefore use the admin DN and
    | password for searching for people). When false, binding and searching is
    | done with the username and password passed to Auth::attempt(). Default is
    | false.
    |
    */
    'allow_no_pass' => env("LDAP_ALLOW_NO_PASS", false),

    /*
    |--------------------------------------------------------------------------
    | LDAP employee ID attribute
    |--------------------------------------------------------------------------
    |
    | The field to use when looking-up a person in LDAP by their user ID; this
    | is typically a numeric field. Default is "employeeNumber". This is the
    | field value that will be used when checking for a user in the associated
    | data model and database table/view.
    |
    | This can also be the same as the search_username value if you want to
    | perform both the LDAP and database lookups with the same value.
    |
    */
    'search_user_id' => env("LDAP_SEARCH_USER_ID", "employeeNumber"),

    /*
    |--------------------------------------------------------------------------
    | LDAP username (POSIX ID) attribute
    |--------------------------------------------------------------------------
    |
    | The field to use when looking-up a person in LDAP by their username; this
    | is typically the POSIX ID. Default is "uid". This is the value that will
    | be used to perform the search operation as the username passed to the
    | call to Auth::attempt().
    | 
    | If password validation is turned on, this is also the username that will
    | be used for the bind operation when combined with the base DN.
    |
    */
    'search_username' => env("LDAP_SEARCH_USERNAME", "uid"),

    /*
    |--------------------------------------------------------------------------
    | LDAP email attribute
    |--------------------------------------------------------------------------
    |
    | The field to use when looking-up a person in LDAP by their email address;
    | Default is "mail".
    |
    */
    'search_user_mail' => env("LDAP_SEARCH_MAIL", "mail"),

    /*
    |--------------------------------------------------------------------------
    | LDAP email array attribute
    |--------------------------------------------------------------------------
    |
    | The field to use when looking-up a person in LDAP by all valid email
    | addresses and aliases; this is typically an array attribute. Default is
    | "mailLocalAddress".
    |
    */
    'search_user_mail_array' => env("LDAP_SEARCH_MAIL_ARRAY", "mailLocalAddress"),

    /*
    |--------------------------------------------------------------------------
    | LDAP/DB user ID prefix
    |--------------------------------------------------------------------------
    |
    | Optional prefix before the value of the employee ID in the associated
    | database table/view. Default is blank (no prefix).
    |
    */
    'search_user_id_prefix' => env("LDAP_DB_USER_ID_PREFIX", ""),

];