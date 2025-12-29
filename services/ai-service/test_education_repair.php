<?php
require 'vendor/autoload.php';
use App\Services\JsonParsingService;

$raw = <<<JSON
{
   "education": [
 {
    	"degree":"Diploma in IT (Programming)",
		"institution":"Polythecnic Seberang Perai ",
	  "year":2010,
        },
       {
         "degree":"Certificate In IT( Programming And Multimedia)  "," institution ": " Polytechnic Sultan Mizzan Z.A.",
         
 year:"2006-7"
      }
   ]
}
JSON;

$service = new JsonParsingService();
$extracted = $service->extractJsonFromResponse($raw);

echo "Extracted Data:\n";
print_r($extracted);

$cleaned = $service->stripJsonComments($raw);
echo "\nCleaned:\n$cleaned\n";
$repaired = $service->repairJson($cleaned);
echo "\nRepaired:\n$repaired\n";
