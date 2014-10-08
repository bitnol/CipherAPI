CipherAPI
=========

Decrypt Youtube cipher signatures

Home: http://gitnol.com/CipherAPI/

CipherAPI is the service to deliver algorithm required to decrypt Youtube cipher signatures.
It is open for any type of public use. You can easily fetch the latest algo to decode the ciphered signature by making a GET call.
As algo changes randomly, it keeps the record of changing algo. Please use it responsibly.

# How to use:

Algo are provided in two forms by this API:

1. JSON record of the Algo in the form of:

        {"Player-id":["Signature_Format","Algo"]}
        
        example:
        {"en_US-vflz7mN68":["42.40","s[41] + s[3:18] + s[2] + s[19:41] + s[18] + s[42:83]"]}
        
    Usage:
    
    	  URL: http://www.gitnol.com/CipherAPI/getAlgo.php
    	  
    	  Required params: 
		        playerID	
	
	      i.e. http://www.gitnol.com/CipherAPI/getAlgo.php?playerID=en_US-vflz7mN60
	
2. Just the Algo in simple form:

        i.e. [41,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,2,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,18,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82]
	
    Usage:
    
        URL: http://www.gitnol.com/CipherAPI/getAlgo.php
	      
	      Required params: 
		        playerID
		        sigformat
	
	      i.e. http://www.gitnol.com/CipherAPI/getAlgo.php?playerID=en_US-vflz7mN60&sigformat=42.40

Note: It's in beta stage.
