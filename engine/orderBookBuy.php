<?php
/**
 * Describes an order book of buy orders. Combined with sell side for the
 * complete order book.
 * Open Trade Engine
 */

//TO DO: UPDATE TraderCurrencies table in queries
class orderBookBuy extends orderBookBase {
    //Deletes an order by removing it from the database
    function cancelOrder($orderID, $traderID)
    {   
        //get the order, null is returned if there is no match for the combo
        $order = $this->getByID($orderID, $traderID);
        
        $returnFee =  $order->getPrice() * $order->getQuantity() * 0.0015;
        $orderAmount = ($order->getPrice() * $order->getQuantity()) + $returnFee;
        
        if($order != NULL)
        {
            $connection = connectionFactory::getConnection();

            //START TRANSACTION
            $connection->query("START TRANSACTION");
            
            //execute delete, subtract from held balance, and add back to the right side available balance
            $query1 = $connection->query("DELETE FROM `".$this->symbol."Buys` WHERE `ID`=".$orderID." AND `owner`=".$traderID);
            $query2 = $connection->query("UPDATE `Traders` SET `$held`=(`$held`-$orderAmount) WHERE `ID`=".$traderID);
            $query3 = $connection->query("UPDATE `Traders` SET `$symbolRight`=(`$symbolRight`+$orderAmount) WHERE `ID`=".$traderID);
            
            //execute all or roll back
            if ($query1 && $query2 && $query3) {
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
    function addOrder($newOrder)
    {          
        //order data
        $price = $newOrder->getPrice();
        $quantity= $newOrder->getQuantity();
        $type = $newOrder->getType();
        $owner = $newOrder->getOwner();
        $symbol = $newOrder->getSymbol();
        $rightTotal = ($quantity * $price)+($quantity * $price * $this->fee);
        
        //prepare connection, start order adding transaction
        $connection = connectionFactory::getConnection();
  
        $connection->query("START TRANSACTION");

        $add = $connection->query("INSERT INTO `".$this->symbol."Buys`(`Price`, `Quantity`, `Type`, `Side`, `Owner`, `Symbol`)
              VALUES($price, $quantity, '$type', '$this->side', '$owner', '$symbol')");
          
         //move balances to held balance
         //if order is a buy order, hold the buyer's right balance TO DO: Update to use relation table
        $update = $connection->query("UPDATE `TraderCurrencies` SET `HeldBalance`=(`HeldBalance`+$rightTotal),"
            ." `Balance`=(`Balance`-$rightTotal) WHERE `Trader`=$owner AND `Balance` >= $rightTotal");
        $countUpdate = $connection->affected_rows;

        //commit transaction if all criteria pass
        if($add && $update && $countUpdate == 1) {
            $connection->query("COMMIT");
        } else {
            echo "$add $update $countUpdate";
            $connection->query("ROLLBACK");
            THROW NEW EXCEPTION ("Could not add sell order");
        }
    }  
}