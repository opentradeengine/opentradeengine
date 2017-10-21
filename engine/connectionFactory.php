<?php
/**
 * Connects to database.
 *
 * Open Trade Engine
 */
class connectionFactory
{   
    private static $db;

    public static function getConnection()
    {
        if(self::$db == NULL)
            self::$db = new mysqli('localhost:3300', 'user', 'password', 'opentradeengine');
            self::$db->query("SET time_zone = 'UTC'");
        return self::$db;
    }

    public static function closeConnection()
    {
       if(self::$db == NULL)
       {
           self::$db = NULL;
       }
    }
}