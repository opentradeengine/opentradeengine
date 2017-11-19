<?php
/**
 * Described an order book of buy orders. It initializes with a copy of data from the database
 *
 * Open Trade Engine
 */

//TO DO: UPDATE TraderCurrencies table in queries
class OrderBookSell extends orderBookBase {
    //Deletes an order by removing it from the database
    function cancelOrder($orderID, $traderID) {
        //get the order, null is returned if there is no match for the combo
        $order = $this->getByID($orderID, $traderID);

        
        $returnFee =  $order->getQuantity() * 0.0015;
        $orderAmount = $order->getQuantity() + $returnFee;
        
        if($order != NULL) {
            $connection = connectionFactory::getConnection();

            //START TRANSACTION
            $connection->query("START TRANSACTION");
            
            //execute delete, subtract from held balance, and add back to the right side available balance
            $query1 = $connection->query("DELETE FROM `".$this->symbol."Sells` WHERE `ID`=".$orderID." AND `owner`=".$traderID);
            $query2 = $connection->query("UPDATE `Traders` SET `$held`=(`$held`-$orderAmount) WHERE `ID`=".$traderID);
            $query3 = $connection->query("UPDATE `Traders` SET `$symbolLeft`=(`$symbolLeft`+$orderAmount) WHERE `ID`=" .$traderID);
            
            //execute all or roll back
            if($query1 && $query2 && $query3) {
                $connection->query("COMMIT");
            } else {        
                $connection->query("ROLLBACK");
            }
        }
    }
    
    //1. Adds the order to the database, ID is auto-assigned by auto-increment.
    //orders are deleted when canceled and the ID increment is not reset
    //2. Executed orders are moved to a different auto-increment table which
    //gives them their final ID in the OrderBook class(combines buy and sell)
    function addOrder($newOrder) {
        //order data
        $price = $newOrder->getPrice();
        $quantity= $newOrder->getQuantity();
        $type = $newOrder->getType();
        $owner = $newOrder->getOwner();
        $symbol = $newOrder->getSymbol();
        $leftTotal = ($quantity)+($quantity * $this->fee);
        $held = $this->symbolLeft."Held";
        $balance = $this->symbolLeft."Balance";
        
        //prepare connection, start order adding transaction
        $connection = connectionFactory::getConnection();
  
        $connection->query("START TRANSACTION");

        //TO DO: Update queries to use relation table
        $add = $connection->query("INSERT INTO ".$this->symbol."Sells(Price, Quantity, Type, Side, owner, Symbol)
              VALUES($price, $quantity, '$type', '$this->side', '$owner', '$symbol')");
          
         //subtract from balance and add it to held balance
        $update = $connection->query("UPDATE `Traders` SET `$held`=(`$held`+$leftTotal), `$balance`=(`$balance`-$leftTotal) 
            WHERE `ID`=$owner AND $balance >= $leftTotal");
        $countUpdate = $connection->affected_rows;

        //commit transactions if all succeed
        if ($add && $update && $countUpdate == 1) 
        {
            $connection->query("COMMIT");
        } 
        else 
        {
            THROW NEW EXCEPTION ("Could not add sell order");
            $connection->query("ROLLBACK");
        }
    } 
}