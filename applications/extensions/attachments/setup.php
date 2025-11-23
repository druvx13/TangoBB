<?php

class Extension_Setup extends TangoBB_Extensions_Setup
{
    public function __construct()
    {
        $this->extension_name = 'Attachments';
    }

    public function install()
    {
        global $MYSQL;
        // Create forum_attachments table
        try {
            $MYSQL->query("
                CREATE TABLE IF NOT EXISTS {prefix}forum_attachments (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    thread_id INT(11) NOT NULL,
                    filename VARCHAR(255) NOT NULL,
                    original_filename VARCHAR(255) NOT NULL,
                    file_size INT(11) NOT NULL,
                    file_type VARCHAR(50) NOT NULL,
                    uploaded_at INT(11) NOT NULL,
                    PRIMARY KEY (id),
                    INDEX (thread_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function uninstall()
    {
        global $MYSQL;
        try {
            $MYSQL->query("DROP TABLE IF EXISTS {prefix}forum_attachments");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
