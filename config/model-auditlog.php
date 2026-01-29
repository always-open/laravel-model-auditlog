<?php

return [
    /*
     * This is the default suffix applied to a Model's table name.
     *
     * Example: The User model with a table name of 'users' would have an
     * audit log table name of 'users_auditlog'.
     *
     * Feel free to override this on each model by overriding the getAuditLogTableName() method.
     */
    'table_suffix' => '_auditlog',

    /*
     * This is the default suffix applied to models' class names.
     *
     * Example: The User model would have an audit log model of UserAuditLog.
     */
    'model_suffix' => 'AuditLog',

    /**
     * The namespace for the audit log models. You only need to set this value if
     * your app's audit log model namespace differs from their related audit loggable model.
     */
    'model_namespace' => null,

    /**
     * The path where the audit log models are stored.
     */
    'model_path' => app_path(),

    'model_stub' => __DIR__ . '/../stubs/model.stub',

    /**
     * The path where the database migrations are stored.
     */
    'migration_path' => database_path('migrations'),

    'migration_stub' => __DIR__ . '/../stubs/migration.stub',

    /*
     * Enable foreign keys between the audit tables and the subject model's primary key.
     */
    'enable_subject_foreign_keys' => true,

    /*
     * Enable foreign keys between the audit tables' user fields and the users model.
     */
    'enable_user_foreign_keys' => true,

    'user_model' => '',

    /*
     * Enable the process stamps (sub) package to log which process/url/job invoked a change.
     */
    'enable_process_stamps' => true,

    /*
     * Fields that should be ignored in the audit logs for every model.
     */
    'global_ignored_fields' => [
        'id',
        'created_at',
        'updated_at',
    ],

    /*
     * Function on the auth service provider that will return the user id editing a model
     */
    'auth_id_function' => 'id',

    /*
     * Precision value used in generating audit log tables
     */
    'log_timestamp_precision' => 0,

    'allowed_audit_actions' => [
        'created',
        'updated',
        'deleted',
        'restored',
    ],
];
