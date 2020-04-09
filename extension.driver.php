<?php

declare(strict_types=1);

if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    throw new Exception(sprintf(
        'Could not find composer autoload file %s. Did you run `composer update` in %s?',
        __DIR__.'/vendor/autoload.php',
        __DIR__
    ));
}

require_once __DIR__.'/vendor/autoload.php';

// This file is included automatically in the composer autoloader, however,
// Symphony might try to include it again which would cause a fatal error.
// Check if the class already exists before declaring it again.
if (!class_exists('\\Extension_Reverse_Regex_Field')) {
    class Extension_Reverse_Regex_Field extends Extension
    {
        public static function init()
        {
        }

        public function uninstall()
        {
            Symphony::Database()->query('DROP TABLE `tbl_fields_reverse_regex`');
        }

        public function install()
        {
            return Symphony::Database()->query("
            CREATE TABLE `tbl_fields_reverse_regex` (
                `id` int(11) unsigned NOT NULL auto_increment,
                `field_id` int(11) unsigned NOT NULL,
                `pattern` varchar(100) NOT NULL,
                `unique` enum('yes','no') NOT NULL default 'no',
                PRIMARY KEY  (`id`),
                UNIQUE KEY `field_id` (`field_id`)
            )  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ");
        }
    }
}
