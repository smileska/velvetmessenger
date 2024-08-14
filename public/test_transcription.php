<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$YOUR_API_KEY = "97a37704e7164bb9bbf6b1abadb120d0";

$FILE_URL = "https://github.com/AssemblyAI-Community/audio-examples/raw/main/20230607_me_canadian_wildfires.mp3";

$transcript_endpoint = "https://api.assemblyai.com/v2/transcript";

$data = array(
    "audio_url" => $FILE_URL,
    "auto_highlights" => true
);

$headers = array(
    "authorization: $YOUR_API_KEY",
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
    $transcript_id = $response['id'];
    $polling_endpoint = "https://api.assemblyai.com/v2/transcript/" . $transcript_id;

    while (true) {
        $polling_response = curl_init($polling_endpoint);

        curl_setopt($polling_response, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($polling_response, CURLOPT_RETURNTRANSFER, true);

        $transcription_result = json_decode(curl_exec($polling_response), true);
        curl_close($polling_response);

        if ($transcription_result['status'] === "completed") {
            echo "Transcription: " . $transcription_result['text'];
            break;
        } elseif ($transcription_result['status'] === "error") {
            echo "Transcription failed: " . $transcription_result['error'];
            break;
        } else {
            sleep(3);
        }
    }
} else {
    echo "Failed to initiate transcription";
}
