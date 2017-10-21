<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Work in progress.
 *
 * Open Trade Engine
 */
class tableCreator {
    
    function _construct()
    {
            $connection = connectionFactory::getConnection();

            $symbol = "USD";
            //create queries
           $queryBuys = "CREATE TABLE " .$symbol. "Buys(ID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
               ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP, Price decimal(8,8) NOT NULL, Quantity decimal(8,8) NOT NULL,
               Type varchar(10) NOT NULL, Side varchar(4) NOT NULL, owner varchar(10))";
           
           $querySells = "CREATE TABLE " .$symbol. "Sells(ID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
               ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP, Price decimal(8,8) NOT NULL, Quantity decimal(8,8) NOT NULL,
               Type varchar(10) NOT NULL, Side varchar(4) NOT NULL, owner varchar(10))";
           
           //DEBUG CODE
            echo "beginning queries";
            
            if ($connection->query($queryBuys) && $connection->query($querySells))
            {
              echo "Tables created successfully";
            }
            else
            {
              echo "Error creating tables: " .mysqli_error($connection);
            }
    }
}