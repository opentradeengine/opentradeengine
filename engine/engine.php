<?php
/**
 * Processes trades using the orderbook sub classes.
 *
 * Open Trade Engine
 */

require_once ("orderBook.php");
require_once ("orderBookBuy.php");
require_once ("orderBookSell.php");
require_once ("order.php");
require_once ("trader.php");

class Engine
{
    private $orderBook;
    
    //create orderbook using provided symbols
    public function __construct($symbol, $leftSymbol, $rightSymbol, $feePercentage)
    {
        $this->orderBook = new orderBook($symbol, $leftSymbol, $rightSymbol, $feePercentage);
    }

    //adds an order
    public function addOrder($order)
    {
        $this->orderBook->executeOrder($order);
    }
    
    //cancels an order
    public function cancelOrder($traderID, $orderID, $symbol, $side, $book)
    {
        $this->orderBook->cancelOrder($traderID, $orderID, $side, $book);
    }
    
    //return the top orders for the orderbook by price
    public function lastTrade()
    {
        return $this->orderBook->getLastTrade();
    }
    
    public function currencyVolume()
    {
       $this->orderBook->getVolume();
    }
    
    //calculate daily change in price for the symbol
    public function getDailyChange()
    {
        if ($this->orderBook->get24HourPrice() >= 0.00000001)
        {
            $lastTrade = $this->orderBook->getLastTrade();
            return 100 * ($lastTrade - $this->orderBook->get24HourPrice()) / $lastTrade;
        }
        else
            return 0;
    }
    
    //get 24 hour high
    function get24HourHigh($symbol)
    {
         return $this->orderBook->get24HourHigh();
    }
    
    //get 24 hour low
    function get24HourLow($symbol)
    {
        $this->orderBook->get24HourLow();
    }
    
    //get lowest sell
    function lowestSell($symbol)
    {
        $this->orderBook->getLow();
    }
    
    //get highest buy in orderbook
    function highestBuy($symbol)
    {
        $this->orderBook->getHigh();
    }
    
    //get market buy orders
    function getMarketBuys($number)
    {
        return $this->orderBook->getMarketBuys($number);
    }
    
    //get combined market buy orders
    function getCombinedBuys($number, $symbol)
    {
        return $this->orderBook->getCombinedBuys($number);
    }
    
    //get market sell orders
    function getMarketSells($number)
    {   
        return $this->orderBook->getMarketSells($number);
    }
    
    //get market sell orders
    function getCombinedSells($number)
    {   
        return $this->orderBook->getCombinedSells($number);
    }
    
    //get n completed trades
    function marketTrades($number)
    {
         return $this->orderBookDGCBTC->getTrades($number);
    }
    
    //get user active orders
    function getUserActiveOrders($traderID)
    {
        return $this->orderBook->getUserOrders($traderID);
    }
    
    //get user completed trades
    function getUserCompletedTrades($traderID)
    {
        return $this->orderBook->getUserTrades($traderID);
    }
    
    //get sell total
    function sellTotal($symbol)
    {
        return $this->orderBook->sellTotal($symbol);
    }
    
    //get sell total
    function buyTotal($symbol)
    {
        $this->orderBook->buyTotal($symbol);
    }
}