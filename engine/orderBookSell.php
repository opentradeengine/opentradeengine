<?php
/**
 * Described an order book of buy orders. It initializes with a copy of data from the database
 *
 * Open Trade Engine
 */

//TO DO: UPDATE TraderCurrencies table in queries
class OrderBookSell extends OrderBookBase {
    private $makerFee;
    private $currencyLeft;

    function __construct($setSymbol, $setSide, $setCurrencyLeft, $setMakerFee) {
        parent::__construct($setSymbol, $setSide);
        $this->currencyLeft = $setCurrencyLeft;
        $this->makerFee = $setMakerFee;
    }

    function cancelOrder($orderID) {
        $order = $this->getByID($orderID);
        $returnFee =  $order->getQuantity() * $this->makerFee;
        $orderAmount = $order->getQuantity() + $returnFee;
        
        if($order != NULL) {
            $connection = connectionFactory::getConnection();

            $connection->query("START TRANSACTION");
            
            //execute delete, subtract from held balance, and add back to the right side available balance
            $delete = $connection->query("DELETE FROM `".$this->symbol."Sells` WHERE `ID`=".$order->getID()." AND `owner`=".$order->getOwner());
            $update = $connection->query("UPDATE `TraderCurrencies`  SET `HeldBalance`=(`HeldBalance`-$orderAmount), `Balance`=(`Balance`+$orderAmount)"
                ."  WHERE `Trader`=".$order->getOwner()." AND `Currency`=".$this->currencyLeft);
            $countUpdate = $connection->affected_rows;

            //execute all or roll back
            if($delete && $update && $countUpdate == 1) {
                $connection->query("COMMIT");
            } else {        
                $connection->query("ROLLBACK");
                THROW NEW EXCEPTION ("Could not add sell order");
            }
        }
    }
    
    //1. Adds the order to the database, ID is auto-assigned by auto-increment.
    //orders are deleted when canceled and the ID increment is not reset
    //2. Executed orders are moved to a different auto-increment table which
    //gives them their final ID in the OrderBook class(combines buy and sell)
    function addOrder($order) {
        $price = $order->getPrice();
        $quantity= $order->getQuantity();
        $type = $order->getType();
        $owner = $order->getOwner();
        $leftTotal = ($quantity)+($quantity * $this->fee);
        
        //prepare connection, start order adding transaction
        $connection = connectionFactory::getConnection();
  
        $connection->query("START TRANSACTION");

        $add = $connection->query("INSERT INTO `".$this->symbol."Sells`(`Price`, `Quantity`, `Type`, `Owner`, `Symbol`)
              VALUES($price, $quantity, '$type', '$owner', '".$this->symbol."')");

        //hold the buyer's right balance
        $update = $connection->query("UPDATE `TraderCurrencies` SET `HeldBalance`=(`HeldBalance`+$leftTotal), `Balance`=(`Balance`-$leftTotal) WHERE `Trader`=".$order->getOwner()
            ." AND `Currency`= ".$this->currencyLeft." AND `Balance` >= $leftTotal");
        $countUpdate = $connection->affected_rows;

        //commit transactions if all succeed
        if ($add && $update && $countUpdate == 1) {
            $connection->query("COMMIT");
        } else {
            $connection->query("ROLLBACK");
            THROW NEW EXCEPTION ("Could not add sell order");
        }
    } 
}