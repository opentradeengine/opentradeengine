<?php
/**
 * This order book is made up of a book that keeps tracks of buys
 * And a separate book to keep track of sells
 * Open Trade Engine
 */
require_once("connectionFactory.php");
include ("tradeComplete.php");

class orderBook {
    
    //Variables: buys and sells represent orderBookBuy and orderBookSell
    private $symbol;
    private $symbolLeft;
    private $symbolRight;
    private $sells;
    private $buys;
    private $feePercentage;
    private $lastTrade;
    private $lastSellFee;
    private $lastBuyFee;
    private $lastRightTotal;
    private $lastLeftTotal;
    private $volume;
   
    //Constructor for setting the orderbook symbol and order array size
    function __construct($setSymbol, $setSymbolLeft, $setSymbolRight, $setFeePercentage) 
    {
        $this->symbol = $setSymbol;
        $this->symbolLeft = $setSymbolLeft;
        $this->symbolRight = $setSymbolRight;
        $this->feePercentage = $setFeePercentage;
        
        //Setup both sides of the order book
        $this->buys = new OrderBookBuy($this->symbol, $this->symbolLeft, $this->symbolRight);
        $this->sells = new OrderBookSell($this->symbol, $this->symbolLeft, $this->symbolRight);  
        
        //call set volume from db method
        $this->volume= $this->setVolume();
    }
    
    //Executes an order by determining it's type and matching it with
    //orders currently in the order book. Any uncompleted quantity is added 
    //to the appropriate order book
    function executeOrder($newOrder)
    {    
        $connection = connectionFactory::getConnection();
        
        $connection->query("START TRANSACTION");
        try 
        {
            //variable declarations and initialization
            $topBuy = $this->buys->getTop();
            $lowestSell = $this->sells->getTop();
            $added = FALSE;

            //If the order book is empty on buy side
            if($topBuy == null || $topBuy->getPrice() == NULL)
            { 
                //If order is a sell, add it directly to sell book
                if($newOrder->getSide() == "Sell")
                {
                    $this->sells->addOrder($newOrder);
                    $added = TRUE;
                }
                //if the order is a buy, compare to sell and execute if crosses
                else if($newOrder->getSide() == "Buy")
                {
                    //if the lowestSell price is lower than or equal to the buy order
                    if($lowestSell !== null && $newOrder->compareTo($lowestSell) <= 0 && $lowestSell->getPrice() > 0)
                    {
                       //Execute a trade by calling the dedicated buy function
                        $this->executeBuy($newOrder, $lowestSell); 
                        $added = TRUE;
                    }
                    else if($lowestSell == null || $newOrder->compareTo($lowestSell) > 0) //buy order is less than the lowest sell or top sell is 0
                    {
                        echo "adding to buys";
                        //add it to the buy book
                        $this->buys->addOrder($newOrder);
                        $added = TRUE;
                    }
                }
            }

            //If the order book is empty on the sell side
            if(($lowestSell == null || $lowestSell->getPrice() == NULL) && !$added)
            {
                //if order is a buy, add it directly to buy book
                if($newOrder->getSide() == "Buy")
                {
                    $this->buys->addOrder($newOrder);
                    $added = TRUE;
                }
                //if the order is a sell
                else if ($newOrder->getSide() == "Sell")
                {
                    //if the topbuy price is higher than or equal to the sell order price and top buy is greater than 0
                    if($topBuy !== null && $newOrder->compareTo($topBuy) >= 0 && $topBuy->getPrice() > 0)
                    {   
                       //Execute a trade by calling the dedicated sell function
                        $this->executeSell($newOrder, $topBuy);
                        $added = TRUE;
                    }
                    else if($topBuy == null || $newOrder->compareTo($topBuy) < 0) //sell order is lower than the top buy or top buy is 0
                    {
                        //add it to the sell book
                        $this->sells->addOrder($newOrder);
                        $added = TRUE;
                    }
                }
            }        

            //If both sides of the database have at least 1 order
            if($newOrder->getSide() == "Buy" && !$added)
            {         
                //check if the order buy price is lower than or equal to the highest buy price
                //if it is, add the order to the order book
                if($newOrder->getPrice() <= $topBuy->getPrice())
                {
                    $this->buys->addOrder($newOrder);
                    $added = TRUE;
                }
                //if the price of this buy order is higher than the top buy
                else if($newOrder->getPrice() > $topBuy->getPrice())
                {
                    //if new order price is lower than the highest sell order
                    if($newOrder->getPrice() < $lowestSell->getPrice())
                    {
                        //if it is, add it to the buy order book
                        $this->buys->addOrder($newOrder);
                        $added = TRUE;
                    }
                    //if the lowest sell is at a higher price or equal to the buy order, a trade happens
                    else if($newOrder->getPrice() >= $lowestSell->getPrice())
                    {
                       //Execute a trade by calling the dedicated buy function
                        $this->executeBuy($newOrder, $lowestSell);
                        $added = TRUE;
                    }
                }
            }
            else if($newOrder->getSide() == "Sell" && !$added) //If the order is a sell order
            {
                //If this sell price is higher than the current lowest sell order
                //We can add it directly to the sell order book 
                if($newOrder->compareTo($lowestSell) < 0)
                {
                    $this->sells->addOrder($newOrder);
                }
                //If sell order is lower or equal to buy order price
                else if($newOrder->compareTo($lowestSell) >= 0)
                {
                   // check if the new order sell price is lower than or equal to the top buy price
                     if($newOrder->compareTo($topBuy) >= 0)  
                     {
                         //execute sell order if it is
                         $this->executeSell($newOrder, $topBuy);
                     }
                     //if the sell order is lower than the top buy order
                     else if($newOrder->compareTo($topBuy) < 0)
                     {
                         //add the order to the sell book
                         $this->sells->addOrder($newOrder);
                     }
                }
                //commit query if no errors occured
                $connection->query("COMMIT");
        }
     }
     catch (Exception $e)
        {
            //otherwise roll it back
            $connection->query("ROLLBACK");
            
            //log execute order error
            $connection->query("INSERT INTO `OrderErrors`(`Error`) VALUES('$e') ");

            THROW NEW Exception("Could not execute order. Error: $e");
        }   
    }
    
    //Execute a buy order by performing different operations depending on quantity
    function executeBuy($newOrder, $lowestSellOrder)
    {   
        // If the new buy order's quantity is met by one sell order
        if($newOrder->getQuantity() <= $lowestSellOrder->getQuantity() && $newOrder->getPrice() >= $lowestSellOrder->getPrice())
        {
            //calculate the different between orders
            //the difference is what is left of the sell order
            $difference = $lowestSellOrder->getQuantity() - $newOrder->getQuantity();
            
            //update account balances of traders: update balances cancels the process if anything goes wrong: e.g negative
            //this must stay first in the process
            $this->updateBalancesBuy($newOrder, $lowestSellOrder, $newOrder->getQuantity());
            
            //Add the sell order as completed in the database
            $this->addTrade($newOrder, $lowestSellOrder->getOwner(), $lowestSellOrder->getPrice());
        
            //lower the sell order's quantity
            $lowestSellOrder->setQuantity($difference);
            
            //delete the completed buy order from the order book
            $this->buys->deleteOrder($newOrder->getID());
            
            //If the sell order's quantity is less than 8 decimal figures, also delete it from sell book
            if($difference < 0.00000001)
            {
                $this->sells->deleteOrder($lowestSellOrder->getID());
            }  
        }
        //If the buy order is only partially met by a sell order
        else if($newOrder->getQuantity() > $lowestSellOrder->getQuantity() && $newOrder->getPrice() >= $lowestSellOrder->getPrice())
        {   
            //if buy order price is higher than or equal to the lowest sell
            if($newOrder->getPrice() >= $lowestSellOrder->getPrice())
            {   
                //updates balance for a partial order, completes the sell orders that are on the book
                $this->updateBalancesBuy($newOrder, $lowestSellOrder, $lowestSellOrder->getQuantity());

                //Add the sell order as completed in the database
                $this->addTrade($lowestSellOrder, $newOrder->getOwner(), $lowestSellOrder->getPrice());

                //subtract the sell order quantity from new order
                $newOrder->setQuantity(($newOrder->getQuantity()) - ($lowestSellOrder->getQuantity()));

                //delete completed sell order from sell book and get a new one
                $this->sells->deleteOrder($lowestSellOrder->getID());

                $lowestSellOrder = $this->sells->getTop();

                //sell order is null so we add the rest of order as a buy order
                if($lowestSellOrder->getID() == NULL && $newOrder->getQuantity() > 0.00000001)
                {
                    $this->buys->addOrder($newOrder);
                }
                //if quantity of the buy order is still above zero, run executeBuy method again
                else if($newOrder->getQuantity() >= 0.00000001)
                {
                    $this->executeBuy($newOrder, $lowestSellOrder);
                }
                //order is filled completely, delete it
                else if($newOrder->getQuantity() < 0.00000001)
                {
                    $this->buys->deleteOrder($newOrder);
                }
            }
        }
        //lowest sell order is empty or this buy order's price is lower than the sell order, add what's left to the buy book
        else if ($lowestSellOrder->getQuantity() < 0.00000001 || $newOrder->getPrice() < $lowestSellOrder->getPrice())
        {
            $this->buys->addOrder($newOrder);
        }
    }
    
    //Execute a sell order
    function executeSell($newOrder, $highestBuyOrder)
    {
        // If the new sell order's quantity is MET by one buy order
        if($newOrder->getQuantity() <= $highestBuyOrder->getQuantity() && $highestBuyOrder->getPrice() >= $newOrder->getPrice())
        {               
            //calculate the difference between orders
            //the difference is what is left of the buy order after removing sell quantity
            $difference = $highestBuyOrder->getQuantity() - $newOrder->getQuantity();
            
            //update account balances of traders
            $this->updateBalancesSell($newOrder, $highestBuyOrder, $newOrder->getQuantity());
            
            //Add this order as completed in the database
            $this->addTrade($newOrder, $highestBuyOrder->getOwner(), $highestBuyOrder->getPrice());
        
            //lower the buy order's quantity
            $highestBuyOrder->setQuantity($difference);
            
            //delete the completed sell order from the order book
            $this->sells->deleteOrder($newOrder->getID());
            
            //If the buy order's quantity is less than 8 significant figures, also delete it from sell book
            if($difference < 0.00000001)
            {
                $this->buys->deleteOrder($highestBuyOrder->getID());
            } 
        }
        //If the sell order is only partially met by a buy order
        else if($newOrder->getQuantity() > $highestBuyOrder->getQuantity() && $highestBuyOrder->getPrice() >= $newOrder->getPrice())
        {   
                //updates balance for a partial order, completes the buy orders that are on the book and filled
                $this->updateBalancesSell($newOrder, $highestBuyOrder, $highestBuyOrder->getQuantity());

                //Add the completed buy order to the database
                $this->addTrade($highestBuyOrder, $newOrder->getOwner(), $highestBuyOrder->getPrice());

                //subtract the sell order quantity from $newOrder
                $newOrder->setQuantity(($newOrder->getQuantity()) - ($highestBuyOrder->getQuantity()));

                //delete completed sell order from buy book and get a new one
                $this->buys->deleteOrder($highestBuyOrder->getID());

                $highestBuyOrder = $this->buys->getTop();
                
                //buy order is null so we add the rest of order as a sell order
                if($highestBuyOrder->getID() == NULL && $newOrder->getQuantity() > 0.00000001)
                {
                    $this->sells->addOrder($newOrder);
                }
                //if new order still has a balance greater than significant figures, execute another sell with new details
                else if($newOrder->getQuantity() >= 0.00000001)
                {            
                   $this->executeSell($newOrder, $highestBuyOrder);
                }
                else if ($newOrder->getQuantity() < 0.00000001)
                {
                    $this->sells->deleteOrder($newOrder);
                } 
        }
        //highest buy order is empty, add what's left to the sell book
        else if ($highestBuyOrder->getQuantity() < 0.00000001 || $newOrder->getPrice() > $highestBuyOrder->getPrice())
        {
            $this->sells->addOrder($newOrder);
        }
    }
    
    //adds an order to the completed table trades table
    function addTrade($newTrade, $otherSideTraderID, $price)
    {
        //update volume
        $this->volume += $newTrade->getQuantity();
        
        //setup the connection
        $connection = connectionFactory::getConnection();
        
        //calculate the total without fees
        $totalRight = (float)($newTrade->getQuantity() * $newTrade->getPrice());
        $totalLeft = $newTrade->getQuantity();
        
        //set the last trade price
        $this->lastTrade = $price;
          
          $query= "INSERT INTO `".$this->symbol."Trades` (Price, Quantity, Type, Side, owner, ActingTraderID, Volume, BuyFee, SellFee, TotalRight, TotalLeft)
              VALUES (".$price.", ".$newTrade->getQuantity().",'".
                  $newTrade->getType()."','".$newTrade->getSide()."','".$newTrade->getOwner()."','$otherSideTraderID',
                  ".$newTrade->getQuantity().", $this->lastBuyFee, $this->lastSellFee, $totalRight, $totalLeft)";
          
          $result = $connection->query($query);
          
          if(!$result)
          {
              throw new Exception("Could not add trade to database.");
          }
    }
    
    //cancels an order by ID
    function cancelOrder($traderID, $orderID, $side, $book)
    {
        if ($book == "Sell")
        {
            $this->sells->cancelOrder($orderID, $traderID);
        }
        else if ($book == "Buy")
        {
            $this->buys->cancelOrder($orderID, $traderID);
        }
    }
    
    //updates account balances, helper function of executeBuy
    function updateBalancesBuy($buyOrder, $sellOrder, $Quantity)
    {
        //******UPDATE ACCOUNT BALANCES*****
                //Get the traders who made the orders
                $buyer = new Trader();
                $seller = new Trader();
                
                $buyer->setupTrader($buyOrder->getOwner());
                $seller->setupTrader($sellOrder->getOwner()); 
                
                //calculate the fees for buyer and seller
                $leftSideFee = $this->feePercentage * $Quantity;
                $rightSideFee = $this->feePercentage * $Quantity * $buyOrder->getPrice();
                
                //set the sell price from the sell order that's being bought
                $sellPrice = $sellOrder->getPrice();
                
        //**UPDATE BUYER**
                //represents currency on the left side of a trade.
                //This currency was bought so it's added to the balance
                $buyerBalanceLeft = $buyer->getBalance($this->symbol, "left") + $Quantity;
                
                //represents currency on the right side of the trade.
                //currency that was bought with so we need to calculate fee
                $rightSideBuyerCost = $Quantity * $sellPrice;
                $rightSideBuyerTotal= $rightSideBuyerCost + $rightSideFee; //total to subtract for buying
                
                //calculate new right side balance of the user
                $buyerBalanceRight = $buyer->getBalance($this->symbol, "right") - $rightSideBuyerTotal;
                
                //update the order fee for the buyer order
                $this->lastBuyFee= $rightSideFee;
                
        //**UPDATE SELLER**
                //calculate the left side trade amount to subtract from seller held balance
                $this->lastSellFee= $leftSideFee;
                $tradeAmount = $Quantity + $leftSideFee;
                
                //Update right side of seller trader: BTC if DGC/BTC. Needs to get added for selling left side currency.
                $sellerBalanceRight = $seller->getBalance($this->symbol, "right") + $rightSideBuyerCost;   
                
                //set the last total value of trade 
                $this->lastRightTotal = $rightSideBuyerCost - $rightSideFee;
                $this->lastLeftTotal = $Quantity - $leftSideFee;
                
                //COMMIT CHANGES TO DATABASE
                
                //variables needed for queries
                $symbolRight = $this->symbolRight;
                $symbolLeft = $this->symbolLeft;
                $held = $this->symbolLeft."Held";
                
                $connection = connectionFactory::getConnection();
                
                //if this transaction will not result in any negative balances
                if($buyerBalanceLeft >= 0 && $buyerBalanceRight >= 0 && $sellerBalanceRight >= 0 && $tradeAmount >= 0)
                {
                    $connection->query("START TRANSACTION");
                    
                    //update buyer by lowering his balance directly
                    $query1 = $connection->query("UPDATE `Traders` SET 
                        `$symbolRight"."Balance`=`$symbolRight"."Balance` - $rightSideBuyerTotal 
                            WHERE `ID`=".$buyer->getID()." AND `$symbolRight"."Balance` >= $rightSideBuyerTotal");
                    $countQuery1 = $connection->affected_rows;
                    
                    $query2 = $connection->query("UPDATE `Traders` SET `$symbolLeft"."Balance`=`$symbolLeft"."Balance` + $Quantity WHERE `ID`=" .$buyer->getID());  
                    $countQuery2 = $connection->affected_rows;
                    
                    //update seller by lowering his held balance of left side, and increasing actual balance of right 
                    $query3 = $connection->query("UPDATE `Traders` SET `$held`=(`$held`-$tradeAmount) WHERE `ID`=".$seller->getID()." AND $held >= $tradeAmount");
                    $countQuery3 = $connection->affected_rows;
                    
                    $query4 = $connection->query("UPDATE `Traders` SET `$symbolRight"."Balance`=`$symbolRight"."Balance` + $rightSideBuyerCost 
                        WHERE `ID`=" .$seller->getID());  
                    $countQuery4 = $connection->affected_rows;

                    if ($query1 && $query2 && $query3 && $query4 
                            && $countQuery1 == 1 && $countQuery2 == 1 && $countQuery3 == 1 && $countQuery4 == 1) 
                    {
                        $connection->query("COMMIT");
                    } 
                    else 
                    {    
                        $connection->query("ROLLBACK");

                        //throw exception for overall transaction
                        throw new Exception("COULD NOT UPDATE BALANCES FOR A BUY ORDER");
                        exit();
                    }

                    //update fee totals for fees charged on right and left
                    $connection->query("UPDATE `FeeTotals` SET `Total`=`Total`+ $leftSideFee WHERE `Currency` = '$this->symbolLeft'");
                    $connection->query("UPDATE `FeeTotals` SET `Total`=`Total`+ $rightSideFee WHERE `Currency` = '$this->symbolRight'");        

                    //update buyer's and seller's completed trades in the currency
                    $connection->query("UPDATE `Traders` SET `$symbolLeft"."Complete` = `$symbolLeft"."Complete` + $Quantity WHERE `ID`=" .$buyer->getID());
                    $connection->query("UPDATE `Traders` SET `$symbolLeft"."Complete` = `$symbolLeft"."Complete` + $Quantity WHERE `ID`=" .$seller->getID());

                     //give the buyer and his referrer points if the currency bought with is equal to USD or BTC 
                     if($this->symbolRight == "USD" || $this->symbolRight == "BTC")
                     {
                         $points = $rightSideFee * 10;

                         if($this->symbolRight == "BTC")
                         {
                             $points = $points * 500;
                         }
                         $connection->query("UPDATE `Traders` SET `Points`=`Points`+$points WHERE `ID`=" .$buyer->getID());

                         //also add points to the earned points column: to keep track of what was earned by user from referrals in normal balance
                         $connection->query("UPDATE `Traders` SET `PointsEarned`=`PointsEarned`+$points WHERE `ID`=" .$buyer->getID());

                         //get referrer and give them points if they exist
                         $referrer = $buyer->getReferrer();
                         $referrerPoints = $points / 100;

                         if ($referrer != "None")
                         {
                             $connection->query("UPDATE `Traders` SET `Points`=`Points`+ $referrerPoints WHERE `UserName`='$referrer'");
                         }
                     }
           }
           else
           {
                //throw exception for overall transaction
                throw new Exception("COULD NOT UPDATE BALANCES FOR A BUY ORDER");
                exit();
           }
    }   
    
    //updates account balances, helper function of executeSell
    function updateBalancesSell($sellOrder, $buyOrder, $Quantity)
    {
        //******UPDATE ACCOUNT BALANCES*****
                //Get the traders who own the orders
                $buyer = new Trader();
                $seller = new Trader();
                
                $buyer->setupTrader($buyOrder->getOwner());
                $seller->setupTrader($sellOrder->getOwner());               
                
                $buyOrderPrice = $buyOrder->getPrice();
                
                $leftSideFee = $this->feePercentage * $sellOrder->getQuantity(); 
                $rightSideFee = $this->feePercentage * $buyOrder->getPrice() * $Quantity;
                
                $rightSideCost = $Quantity * $buyOrderPrice;
                
        //**UPDATE BUYER**
                //represents currency on the left side of a trade.
                //This currency was bought so it's added to the balance: The amount of the sell order unlike the updateBuy function
                $buyerBalanceLeft = $buyer->getBalance($this->symbol, "left") + $Quantity;
                
                //represents currency on the right side of the trade. We are selling quantity of sell order at the existing buy price
                $tradeAmount= $rightSideCost + $rightSideFee; //total to subtract from held balance of user to get new balance
                
                //set the order fee for the buyer order
                $this->lastBuyFee=$rightSideFee;
                
        //**UPDATE SELLER**
                //Update left side of seller trader. Left was sold so subtracted and update last orders fee for adding order
                $this->lastSellFee=$leftSideFee;

                //Update left side of seller trader using getBalance method. Needs to get subtracted for selling
                $sellerBalanceLeft = $seller->getBalance($this->symbol, "left") - $Quantity - $leftSideFee;
                $sellerAdustment = $Quantity + $leftSideFee;
                
                //Update right side of seller trader. Needs to get added for selling left side currency.
                $sellerBalanceRight = $seller->getBalance($this->symbol, "right") + $rightSideCost;
                
                //set the last total value of trade 
                $this->lastRightTotal = $rightSideCost;
                $this->lastLeftTotal = $Quantity;
                
                $held = $this->symbolRight."Held";
                
                //COMMIT CHANGES TO DATABASE
                $connection = connectionFactory::getConnection();
                
                //if this transaction will not result in any negative balances
                if($buyerBalanceLeft >= 0 && $sellerBalanceLeft >= 0 && $sellerBalanceRight >= 0 && $tradeAmount >= 0)
                {
                    $connection->query("START TRANSACTION");
                    
                    //update buyer by subtracting from held balance of right side and adding to their left side balance the sold amount
                    $query1 = $connection->query("UPDATE `Traders` SET `$held`=(`$held`-$tradeAmount) 
                        WHERE `ID`=".$buyer->getID()." AND `$held` >= $tradeAmount");
                    $countQuery1 = $connection->affected_rows;
                    
                    $query2 = $connection->query("UPDATE `Traders` SET `".$this->symbolLeft."Balance`=`".$this->symbolLeft."Balance` + $Quantity 
                        WHERE `ID`=" .$buyer->getID());
                    $countQuery2 = $connection->affected_rows;
                    
                    //update seller lowering left side and adding to right side
                    $query3 = $connection->query("UPDATE `Traders` SET `".$this->symbolLeft."Balance`=`".$this->symbolLeft."Balance` - $sellerAdustment 
                        WHERE `ID`=".$seller->getID()." AND `".$this->symbolLeft."Balance` >= $sellerAdustment");
                    $countQuery3 = $connection->affected_rows;
                    
                    $query4 = $connection->query("UPDATE `Traders` SET `".$this->symbolRight."Balance`=`".$this->symbolRight."Balance` + $rightSideCost 
                        WHERE `ID`=" .$seller->getID());        
                    $countQuery4 = $connection->affected_rows;
                    
                    if ($query1 && $query2 && $query3 && $query4 
                            && $countQuery1 == 1 && $countQuery2 == 1 && $countQuery3 == 1 && $countQuery4 == 1) 
                    {
                        $connection->query("COMMIT");
                    } 
                    else 
                    {    
                        $connection->query("ROLLBACK");

                        throw new Exception("COULD NOT UPDATE BALANCES FOR A SELL ORDER");
                        exit();
                    }
                
                    //update fee totals for fees charged on right and left
                    $connection->query("UPDATE `FeeTotals` SET `Total`=`Total`+ $leftSideFee WHERE `Currency` = '$this->symbolLeft'");
                    $connection->query("UPDATE `FeeTotals` SET `Total`=`Total`+ $rightSideFee WHERE `Currency` = '$this->symbolRight'");        

                    //update buyer's and seller's completed trades in the currency
                    $connection->query("UPDATE `Traders` SET `$this->symbolLeft"."Complete` = `$this->symbolLeft"."Complete` + $Quantity WHERE `ID`=" .$buyer->getID());
                    $connection->query("UPDATE `Traders` SET `$this->symbolLeft"."Complete` = `$this->symbolLeft"."Complete` + $Quantity WHERE `ID`=" .$seller->getID());

                     //give the buyer and his referrer points if the currency bought with is equal to USD or BTC 
                     if($this->symbolRight == "USD" || $this->symbolRight == "BTC")
                     {
                         $points = $rightSideFee * 10;

                         if($this->symbolRight == "BTC")
                         {
                             $points = $points * 500;
                         }
                         $connection->query("UPDATE `Traders` SET `Points`=`Points`+$points WHERE `ID`=" .$buyer->getID());

                         //also add points to the earned points column: to keep track of what was earned by user from referrals in normal balance
                         $connection->query("UPDATE `Traders` SET `PointsEarned`=`PointsEarned`+$points WHERE `ID`=" .$buyer->getID());

                         //get referrer and give them points if they exist
                         $referrer = $buyer->getReferrer();
                         $referrerPoints = $points / 100;

                         if ($referrer != "None")
                         {
                             $connection->query("UPDATE `Traders` SET `Points`=`Points`+ $referrerPoints WHERE `UserName`='$referrer'");
                         }
                     }
                }
                else
                {   //throw new exception
                    throw new Exception("COULD NOT UPDATE BALANCES FOR A SELL ORDER");
                    exit();
                }
    }
    
    //sets methods volume from database at construction
    function setVolume()
    {
        //get the volume
        $connection = connectionFactory::getConnection();
        $result = $connection->query("SELECT SUM(Quantity) AS Volume FROM `".$this->symbol."Trades` WHERE `ts` >= DATE_SUB(NOW(), INTERVAL 1 DAY)"); 
    
        if ($result)
        {   
            // get association
            $row = mysqli_fetch_assoc($result);
            
            return $row['Volume'];
            
        } 
        else if (!$result)
        {
            return;
        }
    }
    
    //accessor methods
    function getSymbol()
    {
        return $this->symbol;
    }
    
    //returns last trade that occured
    function getLastTrade()
    {
        //get the order
        $connection = connectionFactory::getConnection();
        $result = mysqli_query($connection, "SELECT `Price` FROM `".$this->symbol."Trades` ORDER BY `ts` DESC LIMIT 1"); 
    
        if ($row = $result->fetch_assoc())
        {        
            //make an order from the row 
            $price = $row['Price'];
            
            if($price > 0)
            {
                return $price;
            }
        } 
        else
        {
            //no price retrieved
            return 0;
        }
    }
    
    function getVolume()
    {
        return $this->volume;
    }
    
    function getTop()
    {
        $top = $this->buys->getTop();
        return $top->getPrice(); 
    }
    
    //returns lowest buy
    function getHigh()
    {
        $buy = $this->buys->getTop();
        return $buy->getPrice(); 
    }
    
    //returns highest sell
    function getLow()
    {
        $buy = $this->sells->getTop();
        return $buy->getPrice(); 
    }
    
    //gets the closest timestamp to 24 hours ago 
    function get24HourPrice()
    {     
        //get the order
        $connection = connectionFactory::getConnection();
        $result = mysqli_query($connection, "SELECT `Price` FROM `".$this->symbol."Trades` WHERE `ts` >= DATE_SUB(NOW(), INTERVAL 1 DAY) ORDER BY `ts` ASC LIMIT 1"); 
    
        if ($row = $result->fetch_assoc())
        {   
            //make an order from the row 
            $price = $row['Price'];
            
            if($price > 0)
            {
                return $price;
            }
            else 
            {
                return 'NA';
            }
        } 
        else if (!$result)
        {
            //no price retrieved
            return;
        }
    }
    
    //get n user sells
     function getMarketSells($number)
    {
        return $this->sells->getOrders($number);
    }
    
    //get sell orders combined at equal price points
    function getCombinedSells($number)
    {
        return $this->sells->getCombinedOrders($number);
    }
    
    //get n user sells
     function getMarketBuys($number)
    {
        return $this->buys->getOrders($number);
    }
    
    //get n user sells
     function getCombinedBuys($number)
    {
        return $this->buys->getCombinedOrders($number);
    }
    
    //get sell 
    function sellTotal()
    {
         $connection = connectionFactory::getConnection();
         $query= "SELECT sum(Quantity) FROM `".$this->symbol."Sells`";
         $result = $connection->query($query);
         
         if ($result)
        {   
            /* seek to row no. 1*/
            $result->data_seek(0);
            
            //fetch row
            $row = $result->fetch_row();
            
            /* free result set*/
            $result->close();      
            
            return $row[0];
        } 
        else if (!$result)
        {
            return 0;
        }
    }
    
    //get buy total
    function buyTotal()
    {
         $connection = connectionFactory::getConnection();
         $query= "SELECT sum(Quantity * Price) FROM `".$this->symbol."Buys`";
         
         $result = $connection->query($query);
         
         if ($result)
        {   
            /* seek to row no. 1*/
            $result->data_seek(0);
            
            //fetch row
            $row = $result->fetch_row();
            
            /* free result set*/
            $result->close();      
            
            //make an order from the row 
            return $row[0];
        } 
        else if (!$result)
        {
            //no total retrieved
            return 0;
        }
    }
    
    //get all active orders for a user's ID
    function getUserOrders($traderID)
    {
        $query= "(SELECT * FROM `".$this->symbol."Buys` WHERE `owner`=".$traderID.")
                UNION
                (SELECT * FROM `".$this->symbol."Sells` WHERE `owner`=".$traderID.") 
                ORDER BY `ts` ASC";
        
        //Selects the appropriate table and retrieves $numberOfOrders in descending 
        //order(highest buy order ontop). Timestamp are used as secondary consideration
        //for multiple orders at the same price point
        $connection = connectionFactory::getConnection();
        
        $result = $connection->query($query);
         
        //Gets the data from DB(each row is an order) and creates a new
        //Order object from it with all the required variables
        $i = 0;
        $buyOrders = array();
        
        if($result)
        {
            while($order = mysqli_fetch_array($result)) 
            {
                $buyOrders[$i] = new order($order['Price'], $order['Quantity'], $order['Type'], $order['Side'], $order['owner'], $order['Symbol']);

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
        else
        {
            return NULL;
        }
            
    }
    
    //get user completed orders
    function getUserTrades($ID)
    {
        //Selects the appropriate table and retrieves $numberOfOrders in descending 
        //order(highest buy order ontop). Timestamp are used as secondary consideration
        //for multiple orders at the same price point
        $connection = connectionFactory::getConnection();
        
        $result = $connection->query("SELECT * FROM `".$this->symbol ."Trades` WHERE `owner`=".$ID." OR `ActingTraderID`=".$ID." ORDER BY `ts` DESC");
        
        //Gets the data from DB(each row is an order) and creates a new
        //Order object from it with all the required variables
        $i = 0;
        $buyOrders = array();
        
        if($result)
        {
            while($order = mysqli_fetch_array($result)) 
            {
                $buyOrders[$i] = new Trade($order['ID'], $order['ts'], $order['Price'], $order['Quantity'], $order['Type'], $order['Side'], $order['owner'], $order['ActingTraderID'], $order['BuyFee'],$order['SellFee'], $order['TotalRight'], $order['TotalLeft']);
                //increment
                $i++;
            }

            /* free result set*/
            $result->close();

            //Returns an array of the highest price $numberOfOrders buy orders
            return $buyOrders;
        }
        else
            return NULL;
    }
    
    //get market completed trades
    function getTrades($numberOfTrades)
    {
        //Selects the appropriate table and retrieves $numberOfOrders in descending 
        //order(highest buy order ontop). Timestamp are used as secondary consideration
        //for multiple orders at the same price point
        $connection = connectionFactory::getConnection();
        
        $result = $connection->query("SELECT * FROM `".$this->symbol."Trades` ORDER BY `ts` DESC LIMIT ".$numberOfTrades);
         
        //Gets the data from DB(each row is an order) and creates a new
        //Order object from it with all the required variables
        $i = 0;
        $buyOrders = array();
        
        while($order = mysqli_fetch_array($result)) 
        {
            $buyOrders[$i] = new Trade($order['ID'], $order['ts'], $order['Price'], $order['Quantity'], $order['Type'], $order['Side'], $order['owner'], $order['ActingTraderID'], $order['BuyFee'], $order['SellFee'], $order['TotalRight'], $order['TotalLeft']);
            
            //increment
            $i++;
        }
        
        /* free result set*/
        $result->close();
         
        //Returns an array of the highest price $numberOfOrders buy orders
        return $buyOrders;
    }
    
    //get 24 hour highest price
    function get24HourHigh()
    {
        //get the order
        $connection = connectionFactory::getConnection();
        $result = $connection->query("SELECT `Price` FROM `".$this->symbol."Trades` WHERE `ts` >= DATE_SUB(NOW(), INTERVAL 1 DAY) ORDER BY `Price` DESC LIMIT 1"); 
    
        if ($row = $result->fetch_assoc())
        {        
            //make an order from the row 
            $price = $row['Price'];      
            
            if($price > 0)
            {
                return $price;
            }
            else 
            {
                return 'NA';
            }
        } 
        else if (!$result)
        {
            //no price retrieved
            return;
        }
    }
    
    //get 24 hour lowest price
    function get24HourLow()
    {
        //get the order
        $connection = connectionFactory::getConnection();
        $result = mysqli_query($connection, "SELECT `Price` FROM `" .$this->symbol."Trades` WHERE `ts` >= DATE_SUB(NOW(), INTERVAL 1 DAY) ORDER BY `Price` ASC LIMIT 1"); 
    
        if ($row = $result->fetch_assoc())
        {        
            //make an order from the row 
            $price = $row['Price'];
            
            if($price > 0)
            {
                return $price;
            }
            else 
            {
                return 'NA';
            }
        } 
        else if (!$result)
        {
            //no price retrieved
            return;
        }
    }
    
    //get the trader's point balance
    function getPoints($traderID)
    {
        //get the balance
        $connection = connectionFactory::getConnection();
        $result = mysqli_query($connection, "SELECT `Points` FROM `Traders` WHERE `ID`=$traderID LIMIT 1"); 
    
        if ($row = $result->fetch_assoc())
        {        
            $points = $row['Points'];
            
            return $points;
        }
        else
        {
            return 0;
        }
    }
    
    //checks if a username by that name already exists
    function checkTrader($userName)
    {   
        //fetch trader by ID 
        $connection = connectionFactory::getConnection();
        
        $result = $connection->query("SELECT `UserName` FROM `Traders` WHERE `UserName` = '$userName'");
        
        if (mysqli_num_rows($result) >= 1)
        {   
            return TRUE;
        } 
        else if(mysqli_num_rows($result) < 1)
        {
            return FALSE;
        }
    }
    
    function checkEmail($email)
    {
        //fetch trader by ID 
        $connection = connectionFactory::getConnection();
        
        $result = $connection->query("SELECT `Email` FROM `Traders` WHERE `Email` = ".$email);
        
        if ($result)
        {   
            return TRUE;
        } 
        else if (!$result)
        {
            return FALSE;
        }       
    }
    
    function getReferrals($username)
    {
      //fetch trader by ID 
      $connection = connectionFactory::getConnection();
        
      $result = $connection->query("SELECT `ts`, `AccountNumber`, `PointsEarned` FROM `Traders` WHERE `Referrer` = '$username'");
        
       //loop through array and create array within array of referrals
       $referrals = array();      
       while($row = $result->fetch_assoc())  
       {   
           $referral = array("date" => $row['ts'], "ID" => $row['AccountNumber'], "points" => $row['PointsEarned']);
           
           $referrals[] = $referral;
       }      
       return $referrals;
    }
}