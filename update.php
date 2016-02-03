<?PHP
require("update-config.php");
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

if (ob_get_level() == 0) ob_start();
// Turn off output buffering
ini_set('output_buffering', 'off');
// Turn off PHP output compression
ini_set('zlib.output_compression', false);
         
//Flush (send) the output buffer and turn off output buffering
//ob_end_flush();
while (@ob_end_flush());
         
// Implicitly flush the buffer(s)
ini_set('implicit_flush', true);
ob_implicit_flush(true);
 
//prevent apache from buffering it for deflate/gzip
header("Content-type: text/html");
header('Cache-Control: no-cache'); // recommended to prevent caching of event data.

for($i = 0; $i < 1000; $i++)
{
echo ' ';
}

function say($echotext){
  echo $echotext;
  //ob_flush();
  flush();
}


//connect to fusion invoice, download latest version
$curl = curl_init();
// Set some options - we are passing in a useragent too here
curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => 'https://www.fusioninvoice.com/login',
    CURLOPT_USERAGENT => 'Fusioninvoice Updater',
    CURLOPT_COOKIEJAR => __DIR__.'/login.cookie',
    CURLOPT_POST => 1,
    CURLOPT_POSTFIELDS => array(
        'login_email' => $FI_username,
        'login_password' => $FI_password
    )
));
// Send the request & save response to $resp
$resp = curl_exec($curl);

$login = json_decode($resp, true);
if($login['success']==0){ 
  say("Error logging in. "); 
  say( $login['errors']['general_error'] );
  $error = true;
  exit; 
}
say('Logged In<br>Downloading... ');


curl_setopt($curl, CURLOPT_URL,'https://www.fusioninvoice.com/account');
curl_setopt($curl, CURLOPT_POST, 0);
$resp = curl_exec($curl);
//print(htmlspecialchars($resp));

$DOM = new DOMDocument;
$DOM->loadHTML($resp);
//$tables = $DOM->getElementsByTagName('table');

$items = $DOM->getElementsByTagName('tr');

function tdrows($elements)
{
    $arr = array();
    $i=0;
    foreach ($elements as $element) {
      $i++;
      if(trim($element->nodeValue)!=''){
        $arr[$i]['text'] = trim($element->nodeValue);
        if($element->childNodes->item(1)!==NULL && $element->childNodes->item(1)->tagname='a'){
          $arr[$i]['text'] = $element->childNodes->item(1)->getattribute('href');
        }
      }
    }
    return $arr;
}

$table = array();
foreach ($items as $node) {
    $table[] = tdrows($node->childNodes);
}
function reorder($rows){
  $return = array();
  $keys = array_shift($rows);
  //print_r($keys);
  foreach($rows as $row){
    $i = 0;
    $arr = array();
    foreach($keys as $key=>$value){
      $arr[$value['text']]=$row[$key]['text'];
      $i++;
    }
    $return[] = $arr;
  }
  return $return;
}
$products = reorder($table);

$experations = array();
foreach($products as $product=>$info){
  if($info['Product']=='FusionInvoice'){
    $date = DateTime::createFromFormat('m/j/Y', $info['Expires']);
    $expiration = $date->format('Ymd');
    $experations[ $expiration ] = $info['Download'];
  }
}
krsort($experations);
if(count($experations)>0){
  $download = array_values($experations)[0];
}
if(!isset($download)){
  say('Download failed. Check that you have a valid Fusioninvoice License<br>');
  curl_setopt($curl, CURLOPT_URL,'https://www.fusioninvoice.com/logout');
  curl_setopt($curl, CURLOPT_POST, 0);
  $resp = curl_exec($curl);
  say('Logged Out<br>');
  curl_close($curl);
  $error = true;
  exit;
}

curl_setopt($curl, CURLOPT_URL, $download);
curl_setopt($curl, CURLOPT_POST, 0);
$zipfile = curl_exec($curl);

$zipFileName = __DIR__.'/fusioninvoice.zip';
$fp = fopen($zipFileName, 'w');
fwrite($fp, $zipfile);
fclose($fp);


say('Success<br>');
//print $resp;

curl_setopt($curl, CURLOPT_URL,'https://www.fusioninvoice.com/logout');
curl_setopt($curl, CURLOPT_POST, 0);
$resp = curl_exec($curl);
say('Logged Out<br>');
curl_close($curl);


if (!file_exists($path_to_FI)) {
    mkdir($path_to_FI, 0777, true);
}
chmod($path_to_FI, 0777);


function extractDir($zipfile, $path, $extract) {
  if (file_exists($zipfile)) {
    $files = array();
    $zip = new ZipArchive;
    if ($zip->open($zipfile) === TRUE) {
      for($i = 0; $i < $zip->numFiles; $i++) {
        $entry = $zip->getNameIndex($i);
        //Use strpos() to check if the entry name contains the directory we want to extract
        if (strpos($entry, $extract)===0) {
          //Add the entry to our array if it in in our desired directory and if that's the first directory with that name
          $files[] = $entry;
        }
      }
      //Feed $files array to extractTo() to get only the files we want
      if ($zip->extractTo($path, $files) === TRUE) {
        return TRUE;
      } else {
        say( 'No matching folders found<br>');
        return FALSE;
      }
      $zip->close();
    } else {
      say('ZIP archive unopenable<br>');
      return FALSE;
    }
  } else {
    say('ZIP archive not found<br>');
    return FALSE;
  }
}

$dirs = array('app', 'assets', 'database', 'resources');
foreach($dirs as $dir){
  say("Extracting ".$dir." folder... ");
  if (extractDir($zipFileName, $path_to_FI, $dir)) {
    say("Success<br>");
  } else {
    say("Failure<br>");
    $error = true;
  }
}

say("Removing temporary files...  ");
if(unlink($zipFileName) && unlink(__DIR__.'/login.cookie')){
  say("Success<br>");
} else {
  say("Failure<br>");
  $error = true;
}


if(!$error){
echo "Done.<br>";

echo "Redirecting to database migration script...";

echo '<script type="text/javascript">setTimeout(function(){
    window.location = "'.$FI_base_url.'/setup/migration/";
}, 3000);</script> ';
} else {
  echo "Errors occurred, please see the log above.";
}
//ob_end_flush();
?>