<?php
/**
 * Registration handling class, inserts user in DB and sends email
 *
 * Open Trade Engine
 */
require('engine/helper.php');
class Register
{
    private function encrypt($password)
    {
        $options = array('cost' => 12);
        return password_hash($password, PASSWORD_BCRYPT, $options);
    }

    //adds the new member to the database
    public function insertMember($userName, $firstName, $lastName, $birthDate, $phoneNumber, $securityQuestion,
                                 $securityAnswer, $pin, $email, $password, $referrer)
    {
        $connection = ConnectionFactory::getConnection();

        $statement = $connection->prepare("INSERT INTO `Traders`(`UserName`, `FirstName`, `LastName`,"
        ."`BirthDate`, `PhoneNumber`, `SecurityQuestion`, `SecurityAnswer`, `PIN`, `Email`, `PasswordHash`, `Referrer`)"
        ." VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        //create password hash from posted password
        $passHash = $this->encrypt($password);

        $statement->bind_param("ssssssssssi", $userName, $firstName, $lastName,
              $birthDate, $phoneNumber, $securityQuestion, $securityAnswer,
              $pin, $email, $passHash, $referrer);


        $statement->execute();
        $ID = $statement->insert_id;
        $statement->close();

        //initiate trader currencies to zero
        $helper = new Helper();
        $currencies = $helper->getCurrencies();

        $query = "INSERT INTO `TraderCurrencies`(`Currency`, `Trader`) VALUES ";
        foreach($currencies as $symbol=>$currency) {
            $currencyID = $currency['ID'];
            $query .= " ($currencyID, $ID),";
        }
        $query = rtrim($query, ',');
        $connection->query($query);
    }
} 