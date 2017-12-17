<?php
/**
 * Common buy and sell book base class.
 *
 * Open Trade Engine
 */

class OrderBookBase {
    protected $symbol;
    protected $side;
    protected $sorting = "DESC";
    protected $getOrderQuery;

    function __construct($setSymbol, $setSide) {
        $this->symbol = $setSymbol;
        $this->side = $setSide;

        if($this->side == "Sell") {
            $this->sorting = "ASC";
        }

        $this->getOrderQuery = "SELECT * FROM `".$this->symbol.$this->side."s` ORDER BY `Price` ".$this->sorting.", `TS` DESC";
    }

    //gets order by ID
    function getByID($orderID) {
        $connection = connectionFactory::getConnection();

        $result = $connection->query("SELECT * FROM `".$this->symbol.$this->side."s` WHERE `ID`=$orderID LIMIT 1");

        $order = NULL;
        if($result) {
            $result->data_seek(0);

            //fetch row
            $row = $result->fetch_row();
            $result->close();

            //make an order from the row
            $order = new Order($row[2], $row[3], $row[4], $row[5], $row[6], $row[7]);

            //set order ID and timestamp
            $order->setID($row[0]);
            $order->setTimestamp($row[1]);
        }
        return $order;
    }

    //load $numberOfOrders with matching symbol into orders array
    function getOrders($numberOfOrders) {
        $connection = connectionFactory::getConnection();

        $result = $connection->query($this->getOrderQuery." LIMIT ". $numberOfOrders);

        $i = 0;
        $orders = new SplFixedArray($numberOfOrders);
        while($i < $numberOfOrders) {
            $order = mysqli_fetch_array($result);

            $orders[$i] = new Order($order['Price'], $order['Quantity'], $order['Type'], $order['Side'], $order['Owner'], $order['Symbol']);

            //set ID and timestamp
            $orders[$i]->setID($order['ID']);
            $orders[$i]->setTimestamp($order['TS']);

            $i++;
        }
        $result->close();

        return $orders;
    }

    function getCombinedOrders($numberOfOrders) {
        $connection = connectionFactory::getConnection();

        $result = $connection->query($this->getOrderQuery." LIMIT ". $numberOfOrders);

        $count = 0;
        $orders = array();
        $prices = array();

        for($i = 0; $i < $result->num_rows; $i++) {
            $order = mysqli_fetch_assoc($result);

            //check if an order exists at that price in the prices array
            $orderID = array_search($order["Price"], $prices);

            //if there is already an order with this price, add the quantity of the retrieved order to it
            if($orderID !== FALSE) {
                $tempQuantity = $orders[$orderID]->getTempQuantity() + $order["Quantity"];

                //update the matching order
                $orders[$orderID]->setTempQuantity($tempQuantity);
            } else if($orderID == FALSE) { // no order with this price exists so add a new order at that price
                $orders[$count] = new Order($order["Price"], $order["Quantity"], $order["Type"], $order["Side"], $order["Owner"], $order["Symbol"]);

                //set ID and timestamp
                $orders[$count]->setID($order["ID"]);
                $orders[$count]->setTimestamp($order["TS"]);
                $orders[$count]->setTempQuantity($order["Quantity"]);

                //keep track of prices that were used
                $prices[$count] = $order["Price"];

                $count++;
            }
        }
        $result->close();
        return $orders;
    }

    //get orders for certain trader/user ID
    function getUserOrders($numberOfOrders, $traderID) {
        $connection = connectionFactory::getConnection();

        $result = $connection->query($connection,"SELECT * FROM `".$this->symbol.$this->side ."s` WHERE `Quantity` > 0"
            ." AND `Owner`='".$traderID."' ORDER BY `Price` ".$this->sorting.", `TS` DESC LIMIT".$numberOfOrders);

        $i = 0;
        $orders = new SplFixedArray($numberOfOrders);
        while($i < $numberOfOrders) {
            $order = mysqli_fetch_array($result);

            $orders[$i] = new Order($order['Price'], $order['Quantity'], $order['Type'], $order['Side'], $order['Owner'], $order['Symbol']);

            //set ID and timestamp
            $orders[$i]->setID($order['ID']);
            $orders[$i]->setTimestamp($order['ts']);

            $i++;
        }
        $result->close();
        return $orders;
    }

    //returns the top order currently
    function getTop() {
        $connection = connectionFactory::getConnection();

        $result = $connection->query($this->getOrderQuery." LIMIT 1");

        $order = NULL;

        if($row = $result->fetch_assoc()) {
            $result->close();

            //make an order from the row
            $order = new Order($row['Price'], $row['Quantity'], $row['Type'], $row['Side'], $row['Owner'], $row['Symbol']);

            //set order ID and timestamp
            $order->setID($row['ID']);
            $order->setTimestamp($row['TS']);
        }
        return $order;
    }

    //deletes an order from database
    function deleteOrder($orderID) {
        $connection = connectionFactory::getConnection();

        $query = "DELETE FROM `".$this->symbol.$this->side."s` WHERE `ID`=".$orderID;

        $connection->query($query);
    }
}