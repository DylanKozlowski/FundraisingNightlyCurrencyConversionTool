<?php

//Retrieves XML data from 3rd party API
function getDataFromAPI()
{
	$APIurl="https://wikitech.wikimedia.org/wiki/Fundraising/tech/Currency_conversion_sample?ctype=text/xml&action=raw";
	$xml=simplexml_load_file($APIurl);
	
	//Check for and report XML errors, else return the XML
	if ($xml === false) 
	{
	    echo "Cannot Get XML: ";
	    foreach(libxml_get_errors() as $error) 
	    {
	        echo "<br>", $error->message;
	    }
	} 
	else 
	{
	    return $xml;
	}
}

//Parse and store data to the database
function storeData($xml)
{
	//Get connection
	$dbConn = createDBConnection();
	
	//Clear yesterday's rates, for ease of access and to keep the table small
	$dbConn->query("Delete from ConversionRates");	
	
	// Use prepared statement for the insert, since we'll be doing it multiple times.
	$statement = $dbConn->prepare("Insert Into ConversionRates (Currency,Rate) Values (?,?)");
	$statement->bind_param("sd", $currency,$rate);
	
	//Iterate through the XML data and insert into database
	foreach($xml->children() as $conversion)
	{
		$currency = $conversion->currency;
		$rate = $conversion->rate;
		$statement->execute();
	}
	
	$dbConn->close();
}

//Gets a connection to the database.  Seperate function to make it easy to change if necessary.
function createDBConnection()
{
	$server = "localhost";
	$user = "fakeUser";
	$pass = "easypass";
	$dbName = "test";
	
	$connection = new mysqli($server,$user,$pass,$dbName);
	return $connection;
}

// Looks up current conversion rate from the database.
function getConversionRate($currency)
{
	//Get connection
	$dbConn = createDBConnection();
	
	//set and run the query
	$sql = "Select rate From conversionrates Where currency = '" . $currency ."'";
	
	$rate= $dbConn->query($sql);
	//Our table requires unique currencies, so just get the first and only result.
	$result = $rate->fetch_assoc();
		
	
	return $result["rate"];
}

//Given a currency code and an ammount, convert to USD using stored conversion rates
function convert($input)
{
	//tokenize the input string and split into currency and amount portions
	//Not very elegant here, but it works for the rigidly defined input we have.
	$token = strtok($input," ");
	$currency = $token;
	$token = strtok(" ");
	$amount = (float)$token;
	
	//Get current conversion rate
	$rate = getConversionRate($currency);
	
	
	
	//Do the conversion
	$USDAmount = $amount*$rate;
	
	
	return "USD " . $USDAmount;
	
}

//Given an array of currency code and ammount strings, convert them to USD and return an array.
function convertArray($inArray)
{
	$returnArray = array();
	//loop through the array and call our single conversion function, and save result to return array
	foreach($inArray as $input)
	{
		array_push($returnArray, convert($input));
	}
	
	return $returnArray;
}

//Use a command line argument to see if you're trying to do the once-daily update
if(!is_null($argv[1]))
{

	if($argv[1] == "update")
	{
		$data = getDataFromAPI();
		storeData($data);
	}
	else //Do a conversion.  Given more time, I'd do some validation here.
	{
		echo convert($argv[1])."\n";
	}
}
//I don't think you can pass arrays in from the command line, so we won't try to get at that function from there.
//We'll just call it directly to prove it works.
$testArray = array("JPY 5000","ARS 200");
$out = convertArray($testArray);
print_r($out);


?>
