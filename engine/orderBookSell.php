<?php
/**
 * Described an order book of buy orders. It initializes with a copy of data from the database
 *
 * Open Trade Engine
 */

class OrderBookSell 
{
    //Setup class global variables
    private $symbol;
    private $symbolLeft;
    private $symbolRight;
    private $side = "Sell";
    private $fee = 0.0015;
    
    //Set the symbol in construction
    function __construct($setSymbol, $setSymbolLeft, $setSymbolRight) 
    {
     $this->symbol = $setSymbol; 
     $this->symbolLeft = $setSymbolLeft;
     $this->symbolRight = $setSymbolRight;
    }
    
    //load $numberOfOrders sell orders with matching symbol into sellOrders array
    function getOrders($numberOfOrders) 
    {   
        //Selects the appropriate table and retrieves $numberOfOrders in descending 
        //order(highest lowest sell order first, ascending order). Timestamp are used as secondary consideration
        //for multiple orders at the same price point
        $connection = connectionFactory::getConnection();
        
        $result = mysqli_query($connection,"SELECT * FROM `".$this->symbol."Sells` ORDER BY `Price` ASC, `ts` DESC LIMIT ". $numberOfOrders);
         
        //Gets the data from DB(each row is an order) and creates a new
        //Order object from it with all the required variables
        $i = 0;
        $sellOrders = new SplFixedArray($numberOfOrders);
        
        while($i < $numberOfOrders) 
        {   
            $order = mysqli_fetch_array($result);
            $sellOrders[$i] = new Order($order["Price"], $order["Quantity"], $order["Type"], $order["Side"], $order["owner"], $order["Symbol"]);
            
            //set ID and timestamp
            $sellOrders[$i]->setID($order["ID"]);
            $sellOrders[$i]->setTimestamp($order["ts"]);
            
            //increment
            $i++;
        }
        
        /* free result set*/
        mysqli_free_result($result);
         
        //Returns an array of the lowest priced $numberOfOrders sell orders
        return $sellOrders;    
    }
    
    function getCombinedOrders($numberOfOrders) 
    {   
        //Selects the appropriate table and retrieves $numberOfOrders in descending 
        //order(highest lowest sell order first, ascending order). Timestamp are used as secondary consideration
        //for multiple orders at the same price point
        $connection = connectionFactory::getConnection();
        
        $result = mysqli_query($connection,"SELECT * FROM `".$this->symbol."Sells` ORDER BY `Price` ASC, `ts` DESC LIMIT ". $numberOfOrders);
         
        //Gets the data from DB(each row is an order) and creates a new
        //Order object from it with all the required variables
        $count = 0;
        $sellOrders = array();
        $prices = array();
        
        for($i=0; $i < $result->num_rows; $i++) 
        {    
            $order = mysqli_fetch_assoc($result);
             
            //if there is already an order with this price, add the quantity of the retrieved order to it
            $orderID = array_search($order["Price"], $prices);
            
            //if there is already an order at this price, add the quantity of retrieved order to it
            if($orderID !== FALSE)
            {   
                $tempQuantity = $sellOrders[$orderID]->getTempQuantity() + $order["Quantity"];
                
                //update the matching order
                $sellOrders[$orderID]->setTempQuantity($tempQuantity);
            }
            else if($orderID === FALSE)// no order with this price exists so add a new order at that price
            {
                $sellOrders[$count] = new Order($order["Price"], $order["Quantity"], $order["Type"], $order["Side"], $order["owner"], $order["Symbol"]);

                //set ID and timestamp
                $sellOrders[$count]->setID($order["ID"]);
                $sellOrders[$count]->setTimestamp($order["ts"]);
                $sellOrders[$count]->setTempQuantity($order["Quantity"]);

                //keep track of prices that were used
                $prices[$count] = $order["Price"];
                
                //increment sell array counter
                $count++;
            }
        } 
        
        /* free result set*/
        mysqli_free_result($result);
         
        //Returns an array of the lowest priced $numberOfOrders sell orders
        return $sellOrders;    
    }
    
     //load $numberOfOrders sell orders with matching symbol into sellOrders array for a specific user
    function getUserOrders($numberOfOrders, $traderID) 
    {   
        //Selects the appropriate table and retrieves $numberOfOrders in descending 
        //order(highest lowest sell order first, ascending order). Timestamp are used as secondary consideration
        //for multiple orders at the same price point
        $connection = connectionFactory::getConnection();
        
        $result = mysqli_query($connection,"SELECT * FROM `".$this->symbol ."Sells` WHERE `Quantity` > 0 AND `owner`='".$traderID."' ORDER BY `Price` ASC, `ts` DESC LIMIT ". $numberOfOrders);
         
        //Gets the data from DB(each row is an order) and creates a new
        //Order object from it with all the required variables
        $i = 0;
        $sellOrders = new SplFixedArray($numberOfOrders);
        
        while($i < $numberOfOrders) 
        {
            $order = mysqli_fetch_array($result);
            
            $sellOrders[$i] = new Order($order['Price'], $order['Quantity'], $order['Type'], $order['Side'], $order['owner'], $order['Symbol']);
            
            //set ID and timestamp
            $sellOrders[$i]->setID($order['ID']);
            $sellOrders[$i]->setTimestamp($order['ts']);
            
            //increment
            $i++;
        }

        mysqli_free_result($result);
         
        //Returns an array of the lowest priced $numberOfOrders sell orders
        return $sellOrders;    
    }
    
    //Returns the lowest sell order currently 
    function getTop()
    {
        //fetch lowest sell order row and return it
        $connection = connectionFactory::getConnection();
        
        $result = $connection->query("SELECT * FROM `".$this->symbol."Sells` ORDER BY `Price` ASC, `ts` DESC LIMIT 1");
        
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
        else if(!$result)
        {
           exit();
        }
        
        return $order;
    }
    
    //Deletes an order by removing it from the database
    function deleteOrder($orderID)
    {
        $connection = connectionFactory::getConnection();
        
        $query = "DELETE FROM `".$this->symbol."Sells` WHERE `ID`=".$orderID;
        
        $connection->query($query);
    }
    
    //Deletes an order by removing it from the database
    function cancelOrder($orderID, $traderID)
    {   
        //get the order, null is returned if there is no match for the combo
        $order = $this->getByID($orderID, $traderID);
        
        //declare variables for use
        $held = $this->symbolLeft."Held";
        $symbolLeft = $this->symbolLeft."Balance";
        
        $returnFee =  $order->getQuantity() * 0.0015;
        $orderAmount = $order->getQuantity() + $returnFee;
        
        if($order != NULL)
        {
            $connection = connectionFactory::getConnection();

            //START TRANSACTION
            $connection->query("START TRANSACTION");
            
            //execute delete, subtract from held balance, and add back to the right side available balance
            $query1 = $connection->query("DELETE FROM `".$this->symbol."Sells` WHERE `ID`=".$orderID." AND `owner`=".$traderID);
            $query2 = $connection->query("UPDATE `Traders` SET `$held`=(`$held`-$orderAmount) WHERE `ID`=".$traderID);
            $query3 = $connection->query("UPDATE `Traders` SET `$symbolLeft`=(`$symbolLeft`+$orderAmount) WHERE `ID`=" .$traderID);
            
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
        
        $result = $connection->query("SELECT * FROM `".$this->symbol."Sells` WHERE `ID`=$orderID AND `owner`=$ownerID LIMIT 1");
        
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
            //return null
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
} // END OrderBookSell class