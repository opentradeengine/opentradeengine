<?php
/**
 * Helper class
 *
 * Open Trade Engine
 */
require_once('connectionFactory.php');

class Helper
{
    private $connection;

    function __construct()
    {
        $this->connection = connectionFactory::getConnection();
    }

    function getCurrencies()
    {
        $result = $this->connection->query("SELECT `ID`, `Symbol`, `Name` FROM `Currencies`");

        if(!$result)
        {
            throw new Exception("Could not fetch currencies.".$this->connection->error);
        }

        $currencies = [];
        while($row = $result->fetch_assoc())
        {
            //set values from database
            $currencies[$row['Symbol']] = ['name'=>$row['Name'], 'ID'=>$row['ID']];
        }

        $result->close();
        return $currencies;
    }

}