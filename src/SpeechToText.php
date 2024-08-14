<?php

namespace App;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class SpeechToText {
    private $client;
    private $apiKey;

    public function __construct($apiKey) {
        $this->client = new Client();
        $this->apiKey = $apiKey;
    }

    public function uploadAudio($audioFilePath)
    {
        try {
            $response = $this->client->request('POST', 'https://api.assemblyai.com/v2/upload', [
                'headers' => [
                    'authorization' => $this->apiKey,
                    'Content-Type' => 'audio/mpeg'
                ],
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => fopen($audioFilePath, 'r'),
                        'filename' => basename($audioFilePath)
                    ]
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['upload_url'] ?? null;
        } catch (GuzzleException $e) {
            echo "Upload failed: " . $e->getMessage() . "\n";
            return null;
        }
    }


    public function transcribe($audioUrl)
    {
        try {
            $response = $this->client->request('POST', 'https://api.assemblyai.com/v2/transcript', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'audio_url' => $audioUrl,
                    'auto_highlights' => true,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            echo "Transcription request failed: " . $e->getMessage() . "\n";
            return null;
        }
    }


    public function getTranscription($transcriptionId)
    {
        try {
            $response = $this->client->request('GET', "https://api.assemblyai.com/v2/transcript/{$transcriptionId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            echo "Polling request failed: " . $e->getMessage() . "\n";
            return null;
        }
    }


}
