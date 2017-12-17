<?php
/**
 * Helper class used for currency and symbol related data retrieval.
 *
 * Open Trade Engine
 */
require_once('connectionFactory.php');

class SymbolManager {
    private $connection;

    function __construct() {
        $this->connection = connectionFactory::getConnection();
    }

    function getCurrencies() {
        $result = $this->connection->query("SELECT `ID`, `Symbol`, `Name` FROM `Currencies`");

        if(!$result) {
            throw new Exception("Could not fetch currencies.".$this->connection->error);
        }

        $currencies = [];
        while($row = $result->fetch_assoc()) {
            $currencies[$row['Symbol']] = ['name'=>$row['Name'], 'ID'=>$row['ID']];
        }

        $result->close();
        return $currencies;
    }

    function getSymbolConfig($symbolID) {
        $connection = connectionFactory::getConnection();

        $statement = $connection->prepare("SELECT `Symbol`, `LeftCurrency`, `RightCurrency`, `MakerFee`,"
            ." `TakerFee` FROM `Symbols` WHERE `SymbolID` = ?");
        $statement->bind_param('i', $symbolID);
        $statement->execute();

        $config = [];
        if($result = $statement->fetch_assoc()) {
            $config = ["symbol"=>$result["Symbol"], "leftCurrency"=>$result["LeftCurrency"], "rightCurrency"=>$result["RightCurrency"],
                "makerFee"=>$result["MakerFee"], "takerFee"=>$result["TakerFee"]];
        }
        $statement->close();
        return $config;
    }

}