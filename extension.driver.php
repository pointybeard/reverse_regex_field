<?php

require_once realpath(__DIR__ . "/vendor/autoload.php");

class extension_Reverse_Regex_Field extends Extension
{
    public static function init()
    {
    }

    public function uninstall()
    {
        Symphony::Database()->query("DROP TABLE `tbl_fields_reverse_regex`");
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
