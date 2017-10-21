<?php
/**
 * Open Trade Engine usage examples.
 *
 * Open Trade Engine
 */
include('engine/engine.php');
include('register.php');

//add traders to database, usually after receiving a post request from a registration form
/*
$register = new Register();

$register->insertMember("buyer", "John", "Smith","2010-11-11", "999-999-9999",
    "Question", "Answer", "555", "buyer@example.com", "password", 3);

$register->insertMember("seller", "Joe", "Smithie", "12-12-1992", "999-888-9999",
   "Question", "Answer", "444", "seller@example.com", "password", 3);
*/

//TO DO: create new currencies and symbol using them


//TO DO: add balances to traders, usually after a deposit is made manually or through an API


//create an order, usually done after receiving a post request from user's browser
//types are a work in progress
$buyOrder = new Order($price = 0.05, $quantity = 1000, $setType = 1, $side = 'Buy', $owner = 4, $symbol = "EXAMPLE");
$sellOrder = new Order(0.05, 1000, 1, 'Sell', 5, $symbol = "EXAMPLE");

$engine = new Engine($symbol = "EXAMPLE", $leftSymbol = "USD", $rightSymbol = "BTC", $feePercentage = 0.01); //TO DO: fetch from database
$engine->addOrder($buyOrder); //executes or adds order depending on orders already in it
$engine->addOrder($sellOrder);

//use trader object to retrieve trader information
$buyer = new Trader();
$buyer->setupTrader(4);

$seller = new Trader();
$seller->setupTrader(5);

echo "Buyer ID: ".$buyer->getID()." Buyer Balance: ".$buyer->getBalance("USD");
echo "Seller ID: ".$seller->getID()." Seller Balance: ".$seller->getBalance("USD");

