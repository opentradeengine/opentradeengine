<?php
/**
 * Describes an order book of buy orders. Combined with sell side for the
 * complete order book.
 * Open Trade Engine
 */

class OrderBookBuy 
{
    //Setup class global variables
    private $symbol;
    private $symbolLeft;
    private $symbolRight;
    private $side = "Buy";
    private $fee = 0.0015;
    
    //Set the symbol in construction
    function __construct($setSymbol, $setSymbolLeft, $setSymbolRight) 
    {
     $this->symbol = $setSymbol; 
     $this->symbolLeft = $setSymbolLeft;
     $this->symbolRight = $setSymbolRight;
    }
    
    //load $numberOfOrders buy orders with matching symbol into buyOrders array
    function getOrders($numberOfOrders) 
    {   
        //Selects the appropriate table and retrieves $numberOfOrders in descending 
        //order(highest buy order ontop). Timestamp are used as secondary consideration
        //for multiple orders at the same price point
        $connection = connectionFactory::getConnection();
        
        $result = $connection->query("SELECT * FROM `".$this->symbol."Buys` ORDER BY `Price` DESC, `ts` DESC LIMIT ". $numberOfOrders);
         
        //Gets the data from DB(each row is an order) and creates a new
        //Order object from it with all the required variables
        $i = 0;
        $buyOrders = new SplFixedArray($numberOfOrders);
        
        while($i < $numberOfOrders) 
        {
            $order = mysqli_fetch_array($result);
            
            $buyOrders[$i] = new Order($order['Price'], $order['Quantity'], $order['Type'], $order['Side'], $order['owner'], $order['Symbol']);
            
            //set ID and timestamp
            $buyOrders[$i]->setID($order['ID']);
            $buyOrders[$i]->setTimestamp($order['ts']);
            
            //increment
            $i++;
        }
        
        /* free result set*/
        $result->close();
         
        //Returns an array of the highest price $numberOfOrders buy orders
        return $buyOrders;    
    }
    
    function getCombinedOrders($numberOfOrders) 
    {   
        //Selects the appropriate table and retrieves $numberOfOrders in descending 
        //order(highest lowest sell order first, ascending order). Timestamp are used as secondary consideration
        //for multiple orders at the same price point
        $connection = connectionFactory::getConnection();
        
        $result = mysqli_query($connection,"SELECT * FROM `".$this->symbol."Buys` ORDER BY `Price` DESC, `ts` DESC LIMIT ". $numberOfOrders);
         
        //Gets the data from DB(each row is an order) and creates a new
        //Order object from it with all the required variables
        $count = 0;
        $buyOrders = array();
        $prices = array();
        
        for($i = 0; $i < $result->num_rows; $i++) 
        {    
            $order = mysqli_fetch_assoc($result);
            
            //check if an order exists at that price in the prices array
            $orderID = array_search($order["Price"], $prices);
            
            //if there is already an order with this price, add the quantity of the retrieved order to it
            if($orderID !== FALSE)
            {
                $tempQuantity = $buyOrders[$orderID]->getTempQuantity() + $order["Quantity"];
                
                //update the matching order
                $buyOrders[$orderID]->setTempQuantity($tempQuantity);
            }
            else if($orderID == FALSE) // no order with this price exists so add a new order at that price
            {
                $buyOrders[$count] = new Order($order["Price"], $order["Quantity"], $order["Type"], $order["Side"], $order["owner"], $order["Symbol"]);

                //set ID and timestamp
                $buyOrders[$count]->setID($order["ID"]);
                $buyOrders[$count]->setTimestamp($order["ts"]);
                $buyOrders[$count]->setTempQuantity($order["Quantity"]);

                //keep track of prices that were used
                $prices[$count] = $order["Price"];
                
                //increment buy order array index
                $count++;
            }
        } 
        
        /* free result set*/
        mysqli_free_result($result);
         
        //Returns an array of the lowest priced $numberOfOrders sell orders
        return $buyOrders;    
    }
    
    //get orders for certain ID
     function getUserOrders($numberOfOrders, $traderID) 
    {   
        //Selects the appropriate table and retrieves $numberOfOrders in descending 
        //order(highest buy order ontop). Timestamp are used as secondary consideration
        //for multiple orders at the same price point
        $connection = connectionFactory::getConnection();
        
        $result = $connection->query($connection,"SELECT * FROM `".$this->symbol ."Buys` WHERE `Quantity` > 0 AND `owner`='".$traderID."' ORDER BY `Price` DESC, `ts` DESC LIMIT".$numberOfOrders);
         
        //Gets the data from DB(each row is an order) and creates a new
        //Order object from it with all the required variables
        $i = 0;
        $buyOrders = new SplFixedArray($numberOfOrders);
        
        while($i < $numberOfOrders) 
        {
            $order = mysqli_fetch_array($result);
            
            $buyOrders[$i] = new Order($order['Price'], $order['Quantity'], $order['Type'], $order['Side'], $order['owner'], $order['Symbol']);
            
            //set ID and timestamp
            $buyOrders[$i]->setID($order['ID']);
            $buyOrders[$i]->setTimestamp($order['ts']);
            
            //increment
            $i++;
        }
        
        /* free result set*/
        $result->close();
         
        //Returns an array of the highest price $numberOfOrders buy orders
        return $buyOrders;    
    }
    
    //Returns the top order currently 
    function getTop()
    {
        //fetch highest buy row and return it
        $connection = connectionFactory::getConnection();
        
        $result = $connection->query("SELECT * FROM `".$this->symbol."Buys` ORDER BY `Price` DESC, `ts` DESC LIMIT 1");
        
        $order = NULL;
        
        if($row = $result->fetch_assoc())
        {
            $result->close();      
            
            //make an order from the row 
            $order = new Order($row['Price'], $row['Quantity'], $row['Type'], $row['Side'], $row['Owner'], $row['Symbol']);
            
            //set order ID and timestamp
            $order->setID($row['ID']);
            $order->setTimestamp($row['TS']);
        } 
        else if (!$result)
        {
            //echo "Failed to load the buy order's top for:".$this->symbol;
            exit();
        }

        return $order;
    }
    
    //Deletes an order by removing it from the database
    function deleteOrder($orderID)
    {
        $connection = connectionFactory::getConnection();
        
        $query = "DELETE FROM `".$this->symbol."Buys` WHERE `ID`=".$orderID;
        
        $connection->query($query);
    }
    
    //Deletes an order by removing it from the database
    function cancelOrder($orderID, $traderID)
    {   
        //get the order, null is returned if there is no match for the combo
        $order = $this->getByID($orderID, $traderID);
        
        //declare variables for use
        $held = $this->symbolRight."Held";
        $symbolRight = $this->symbolRight."Balance";
        
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
    
    //gets order by ID
    function getByID($orderID, $ownerID)
    {
        //fetch highest buy row and return it
        $connection = connectionFactory::getConnection();
        
        $result = $connection->query("SELECT * FROM `".$this->symbol."Buys` WHERE `ID`=$orderID AND `owner`=$ownerID LIMIT 1");
        
        $order = NULL;
        
        if ($result)
        {   
            /* seek to row no. 1*/
            $result->data_seek(0);
            
            //fetch row
            $row = $result->fetch_row();

            /* free result set*/
            $result->close();      
            
            //make an order from the row 
            $order = new Order($row[2], $row[3], $row[4], $row[5], $row[6], $row[7]);
            
            //set order ID and timestamp
            $order->setID($row[0]);
            $order->setTimestamp($row[1]);
        } 
        else if (!$result)
        {
            return;
        }
        return $order;
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
        $held = $this->symbolRight."Held";
        $balance = $this->symbolRight."Balance";
        
        //prepare connection, start order adding transaction
        $connection = connectionFactory::getConnection();
  
        $connection->query("START TRANSACTION");

        //TO DO: Update queries to use relation table
        $add = $connection->query("INSERT INTO ".$this->symbol."Buys(Price, Quantity, Type, Side, owner, Symbol)
              VALUES($price, $quantity, '$type', '$this->side', '$owner', '$symbol')");
          
         //move balances to held balance
         //if order is a buy order, hold the buyer's right balance
        $update = $connection->query("UPDATE `Traders` SET `$held`=(`$held`+$rightTotal), `$balance`=(`$balance`-$rightTotal) 
            WHERE `ID`=$owner AND $balance >= $rightTotal");
        $countUpdate = $connection->affected_rows;

        //commit transaction if all succeed
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