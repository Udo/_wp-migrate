<?php

  $GLOBALS['config'] = array(
    'max-backup-files' => 3, # max number of backup files to keep around
    'users' => array(
      'root' => array('password' => 'MD5_HASH_OF_THE_DB_PASSWORD'), # MD5 hashed password
      ),
    'db' => array(
      'host' => 'localhost',
      'user' => 'DB_USERNAME',
      'password' => 'DB_PASSWORD',
      'exclude-from-list' => array('information_schema', 'mysql', 'performance_schema', 'phpmyadmin'),
      ),
    'wp' => array(
      'instances' => array(
        array( # example of WP instance
          'url' => 'DOMAIN_OF_WP_INSTANCE',
          'path' => 'ABSOLUTE_PATH_OF_INSTANCE',
          'name' => 'HUMAN_READABLE_NAME',
          'db' => 'ASSOCIATED_DATABASE_NAME',
          'type' => 'wp',
          'exclude' => 'wp-config.php', #files not to include in backup
        ),
        array( # example of non-WP site
          'url' => 'DOMAIN_NAME',
          'path' => 'ABSOLUTE_PATH_TO_SITE',
          'name' => 'HUMAN_READABLE_NAME',
        ),
      ),
      ),
    );

