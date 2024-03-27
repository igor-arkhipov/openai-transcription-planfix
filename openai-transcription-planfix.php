<?php

// Incoming parameters, expect url-encoded
$file_url = $_POST['file_url']  ?? null;
$contact_id = $_POST['contact_id'] ?? null;
$openai_api_key = $_POST['openai_api_key'] ?? null;

// Error logging function
function logError($message)
{
  file_put_contents('log.txt', date('Y-m-d H:i:s') . ' - [ERROR]: ' . $message . "\n", FILE_APPEND);
}

if (!$file_url || !$contact_id || !$openai_api_key) {
  logError('Missing incoming parameters – file ID, contact ID or OpenAI API key');
}


// Download file from Planfix  via file_url
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $file_url);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$file_contents = curl_exec($ch);
$real_location = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

// Strip parameters form file url
$filename = pathinfo(parse_url($real_location, PHP_URL_PATH), PATHINFO_BASENAME);

if ($response === false) {
  logError('Error downloading file from Planfix');
}

curl_close($ch);

$temp_dir = __DIR__ . '/tmp/';
// Create tmp dir if it doesn't exist
if (!file_exists($temp_dir)) {
  mkdir($temp_dir, 0755, true);
}

// Save file to disk
file_put_contents($temp_dir . $filename, $file_contents);

// OpenAI config
$openai_endpoint = 'https://api.openai.com/v1/audio/transcriptions';
$model = 'whisper-1';
$response_format = 'text';

// Send audio file to OpenAI
curl_reset($ch);
curl_setopt($ch, CURLOPT_URL, $openai_endpoint);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
  "Authorization: Bearer $openai_api_key",
  "Content-Type: multipart/form-data"
));
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, array(
  'file' => new CURLFile($temp_dir . $filename),
  'model' => $model,
  'temperature' => 0
));
// 'temperature' => 0
// number, Optional , Defaults to 0
// The sampling temperature, between 0 and 1. Higher values like 0.8 will make the output more random, while lower values like 0.2 will make it more focused and deterministic. If set to 0, the model will use log probability to automatically increase the temperature until certain thresholds are hit.
// See other option on https://platform.openai.com/docs/api-reference/audio/createTranscription

$result = curl_exec($ch);

if ($result === false) {
  $error_message = curl_error($ch);
  logError('Error from OpenAI:' . $error_message);
}
curl_close($ch);

//This will be returned to Planfix, you need to set simple answer parsing
echo $result;

// Delete downloaded file from disk.
unlink($temp_dir . $filename);
// Comment/delete line above if you want to store audio files on server (!! potentially unsafe)



// [TODO] Add text to Planfix via REST API, maybe be usesul with huge audio files
// Planfix config
// $planfix_rest_endpoint = 'https://...planfix.ru/rest/';
// $planfix_rest_api_key = '..';
// curl_reset($ch);
// curl_setopt($ch, CURLOPT_URL, $planfix_rest_endpoint . 'contact/' . $contact_id . '/comments/');
// curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//   "Authorization: Bearer $planfix_rest_api_key",
//   "Content-Type: application/json"
// ));
// curl_setopt($ch, CURLOPT_POST, 1);
// curl_setopt($ch, CURLOPT_POSTFIELDS, array(
// .......
// ));

// $response = curl_exec($ch);

// if ($response === false) {
//   logError('Error sending data to Planfix');
// }

// curl_close($ch);
