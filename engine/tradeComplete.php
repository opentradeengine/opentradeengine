<?php
/**
 * Description of a trade
 *
 * Open Trade Engine
 */

class Trade
{ 
    //Variables
    private $price;
    private $quantity;
    private $side;
    private $owner;
    private $actingTrader;
    private $buyFee;
    private $sellFee;
    private $rightTotal;
    private $leftTotal;
    private $ID;
    private $TS;
    private $type;
    
    //Constructor
    function __construct($setID, $setTS, $setPrice, $setQuantity, $setType, $setSide, $setOwner, $setactingTrader, $setBuyFee, $setSellFee, $setRightTotal, $setLeftTotal) 
    {
       $this->ID= $setID;
       $this->TS = $setTS;
       $this->price = $setPrice;
       $this->quantity = $setQuantity;
       $this->type = $setType;
       $this->side = $setSide;
       $this->owner = $setOwner;
       $this->actingTrader = $setactingTrader;
       $this->buyFee= $setBuyFee;
       $this->sellFee=$setSellFee;
       $this->rightTotal = $setRightTotal;
       $this->leftTotal = $setLeftTotal;
    }
    
    //getters  
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
    
    function getTimestamp()
    {
        return $this->TS;
    }
    
    function getBuyFee()
    {
        return $this->buyFee;
    }
    
    function getSellFee()
    {
        return $this->sellFee;
    }
    
    function getSide()
    {
        return $this->side;
    }
    
    function getOwner()
    {
        return $this->owner;
    }
    
    function getActingTraderID()
    {
        return $this->actingTrader;
    }
    
    function getSymbol()
    {
        return $this->symbol;
    }
    
    function getRightTotal()
    {
        return $this->rightTotal;
    }
    
    function getLeftTotal()
    {
        return $this->leftTotal;
    }
    
    function getType()
    {
        return $this->type;
    }
    
    //Check if orders are equal
    function compareTo($thisOrder)
    {
        //used to calculate if two orders are equal
        $requiredAccuracy = 0.00000001;
        
        if(abs($thisOrder->getPrice() - $this->price) < $requiredAccuracy) 
        {   
            //orders are equal so return 0
            return 0;
        }
        else if($thisOrder->getPrice() > $this->price)
        {
            //return one if the order being compared is greater than this order
            return 1;
        }
        else if($thisOrder->getPrice() < $this->price)
        {
            //return negative one if the order being checked is less than this one
            return -1;
        }
    }
}