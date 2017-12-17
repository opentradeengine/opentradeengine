<?php
/**
 * Represents an order for both sides of orderbook
 *
 * Open Trade Engine
 */

class Order
{
    private $price;
    private $quantity;
    private $type;
    private $side;
    private $owner;
    private $ID;
    private $TS;
    private $symbol;
    private $tempQuantity;
    private $feePercent;
    
    //Constructor
    function __construct($setPrice, $setQuantity, $setType, $setSide, $setOwner, $setSymbol, $setFeePercent)
    {
       $this->price = $setPrice;
       $this->quantity = $setQuantity;
       $this->type = $setType;
       $this->side = $setSide;
       $this->owner = $setOwner;
       $this->symbol = $setSymbol;
       $this->feePercent= $setFeePercent;
    }
    
    //getters 
    function getFee()
    {
        return $this->feePercent;
    }
    
    function getID()
    {
        return $this->ID;
    }
    
    function getPrice()
    {
        return $this->price;
    }
    
    function getQuantity()
    {
        return $this->quantity;
    }
    
    function getTempQuantity()
    {
         return $this->tempQuantity;
    }
    
    function getTempTotal()
    {
        return $this->tempQuantity * $this->price;
    }
    
    function getTimestamp()
    {
        return $this->TS;
    }
    
    function getType()
    {
        return $this->type;
    }
    
    function getSide()
    {
        return $this->side;
    }
    
    
    function getOwner()
    {
        return $this->owner;
    }
    
    function getSymbol()
    {
        return $this->symbol;
    }
    
    function getTotal()
    {
        $total = $this->quantity * $this->price;
        return $total;
    }

    //setters
    //sets quantity in database and object, internal use only
    function setQuantity($newQuantity)
    {
        $this->quantity = $newQuantity;
        
        //setup the connection
        $connection = connectionFactory::getConnection();
        
        //update trade balances in database
        mysqli_query($connection,"UPDATE $this->symbol".$this->side."s SET Quantity=" .$newQuantity. " WHERE ID=" .$this->ID);
    }
    
    //sets a temporary quantity for use in displaying a combined order, internal use only
    function setTempQuantity($newQuantity)
    {
         $this->tempQuantity = $newQuantity;
    }
    
    function setTimestamp($newTS)
    {
        $this->TS = $newTS;
    }
    
    function setID($newID)
    {
        $this->ID = $newID;
    }
    
    //Check if orders are equal
    function compareTo($order)
    {
        //used to calculate if two orders are equal
        $requiredAccuracy = 0.00000001;
        
        if(abs($order->getPrice() - $this->price) < $requiredAccuracy)
        {   
            //orders are equal so return 0
            return 0;
        }
        else if($order->getPrice() > $this->price)
        {
            //return one if the order being compared is greater than this order
            return 1;
        }
        else if($order->getPrice() < $this->price)
        {
            //return negative one if the order being checked is less than this one
            return -1;
        }
    }
}