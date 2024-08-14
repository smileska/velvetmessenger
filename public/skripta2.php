<?php
$transcript_endpoint = "https://api.assemblyai.com/v2/transcript";
$data = array(
    "audio_url" => "https://cdn.assemblyai.com/upload/d945fe8d-914b-4d21-95be-fc723efec607",
    "auto_highlights" => true
);
$headers = array(
    "authorization: 97a37704e7164bb9bbf6b1abadb120d0",
    "content-type: application/json"
);
$curl = curl_init($transcript_endpoint);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($curl);
$response = json_decode($response, true);
curl_close($curl);

if (isset($response['id'])) {
    echo "Transcription initiated successfully. ID: " . $response['id'];
} else {
    echo "Transcription initiation failed. Response: " . json_encode($response);
}
