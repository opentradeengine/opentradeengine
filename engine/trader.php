<?php
/**
 * Trader object used for accessing variables
 *
 * Open Trade Engine
 */
class Trader {
    private $ID = null;
    private $balances = [];
    private $referrer = null;
    private $points = 0;

    function __construct($ID) {
        $this->setupTrader($ID);
    }

    //fetch trader by ID, for internal use only (not parameterized)
    public function setupTrader($ID)
    {
        //set ID of trader
        $this->ID = $ID;

        //get a connection
        $connection = connectionFactory::getConnection();

        //retrieve balances
        $result = $connection->query("SELECT `Currencies`.`Symbol` AS 'symbol', `TraderCurrencies`.`Balance` AS 'balance',".
            " `TraderCurrencies`.`HeldBalance` AS 'heldBalance', `TraderCurrencies`.`PendingBalance` AS 'pendingBalance' FROM".
            " `TraderCurrencies` LEFT JOIN `Currencies` ON `TraderCurrencies`.`Currency` = `Currencies`.`ID` WHERE `TraderCurrencies`.`Trader` = $ID");

        if(!$result)
        {
            throw new Exception("Could not fetch trader currencies.".$connection->error);
        }

        while($row = $result->fetch_assoc())
        {
            //set values from database
            $this->balances[$row['symbol']] = ['balance'=>$row['balance'], 'heldBalance'=>$row['heldBalance'],
                'pendingBalance'=>$row['pendingBalance']];
        }
        $result->close();

        //get referral information
        $referralResult = $connection->query("Select `Referrer`, `Points` FROM `Traders` WHERE `ID` = $ID");
        if($row = $referralResult->fetch_assoc()) {
            $this->referrer = $row['Referrer'];
            $this->points = $row['Points'];
        }
    }
    
    public function getPoints()
    {
        return $this->points;
    }
    
    public function getReferrer()
    {
        return $this->referrer;
    }
    
    public function getID()
    {
        return $this->ID;
    }
    
    //get balance based on symbol
    public function getBalance($symbol)
    {    
        return $this->balances[$symbol]['balance'];
    }
    
    //get balance based on symbol
    public function getHeldBalance($symbol)
    {    
        return $this->heldBalances[$symbol]['heldBalance'];
    }
    
    //get the user's pending deposit balance
    public function getPendingBalance($symbol, $side)
    {    
        return $this->pendingBalances[$symbol]['pendingBalance'];
    }
}