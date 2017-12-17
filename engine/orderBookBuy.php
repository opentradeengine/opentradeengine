<?php
/**
 * Describes an order book of buy orders. Combined with sell side for the
 * complete order book.
 * Open Trade Engine
 */

class OrderBookBuy extends OrderBookBase {
    private $makerFee;
    private $currencyRight;

    function __construct($setSymbol, $setSide, $setCurrencyRight, $setMakerFee) {
        parent::__construct($setSymbol, $setSide);
        $this->currencyRight = $setCurrencyRight;
        $this->makerFee = $setMakerFee;
    }

    function cancelOrder($orderID) {
        $order = $this->getByID($orderID);
        $returnFee = $order->getPrice() * $order->getQuantity() * $this->makerFee;
        $orderAmount = ($order->getPrice() * $order->getQuantity()) + $returnFee;
        
        if($order != NULL) {
            $connection = connectionFactory::getConnection();
            $connection->query("START TRANSACTION");
            
            //execute delete, subtract from held balance, and add back to the right side available balance
            $delete = $connection->query("DELETE FROM `".$this->symbol."Buys` WHERE `ID`=".$order->getID()." AND `Owner`=".$order->getOwner());
            $update = $connection->query("UPDATE `TraderCurrencies`  SET `HeldBalance`=(`HeldBalance`-$orderAmount), `Balance`=(`Balance`+$orderAmount)"
                ."  WHERE `Trader`=".$order->getOwner()." AND `Currency`=".$this->currencyRight);
            $countUpdate = $connection->affected_rows;
            
            //execute all or roll back
            if($delete && $update && $countUpdate == 1) {
                $connection->query("COMMIT");
            } else {        
                $connection->query("ROLLBACK");
                THROW NEW EXCEPTION ("Could not cancel buy order");
            }
        }
    }
    
    //1. Adds the order to the database, ID is auto-assigned by auto-increment.
    //orders are deleted when canceled and the ID increment is not reset
    //2. Executed orders are moved to the trades table which
    //gives them their final ID in the OrderBook class(combines buy and sell)
    function addOrder($order) {
        $price = $order->getPrice();
        $quantity= $order->getQuantity();
        $type = $order->getType();
        $owner = $order->getOwner();
        $rightTotal = ($quantity * $price)+($quantity * $price * $this->makerFee);
        
        //prepare connection, start order adding transaction
        $connection = connectionFactory::getConnection();
  
        $connection->query("START TRANSACTION");

        $add = $connection->query("INSERT INTO `".$this->symbol."Buys`(`Price`, `Quantity`, `Type`, `Owner`, `Symbol`)
              VALUES($price, $quantity, '$type', '$owner', '".$this->symbol."')");

         //hold the buyer's right balance
        $update = $connection->query("UPDATE `TraderCurrencies` SET `HeldBalance`=(`HeldBalance`+$rightTotal), `Balance`=(`Balance`-$rightTotal) WHERE `Trader`=".$order->getOwner()
            ." AND `Currency`= ".$this->currencyRight." AND `Balance` >= $rightTotal");
        $countUpdate = $connection->affected_rows;

        //commit transaction if all criteria pass
        if($add && $update && $countUpdate == 1) {
            $connection->query("COMMIT");
        } else {
            $connection->query("ROLLBACK");
            THROW NEW EXCEPTION ("Could not add buy order");
        }
    }  
}