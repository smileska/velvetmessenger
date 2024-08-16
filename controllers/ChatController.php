<?php

namespace Controllers;

use GuzzleHttp\Exception\GuzzleException;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use App\SpeechToText;
use Repositories\Repository;

class ChatController
{
    private $pdo;
    private $repository;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->repository = new Repository($pdo);
    }

    public function sendMessage(Request $request, Response $response): Response
    {
        $pdo = $this->pdo;

        $parsedBody = $request->getParsedBody();
        $sender = $_SESSION['username'];
        $recipient = $parsedBody['recipient'] ?? '';
        $message = $parsedBody['message'] ?? '';
        $chatroomId = $parsedBody['chatroom_id'] ?? null;

        if (empty($message) || (empty($recipient) && empty($chatroomId))) {
            $response->getBody()->write('Recipient or chatroom and message are required.');
            return $response->withStatus(400);
        }

        try {
            if ($chatroomId) {
                $stmt = $pdo->prepare('INSERT INTO chatroom_messages (chatroom_id, sender, message) VALUES (:chatroom_id, :sender, :message)');
                $stmt->execute(['chatroom_id' => $chatroomId, 'sender' => $sender, 'message' => $message]);
                return $response->withHeader('Location', '/chatroom/' . $chatroomId)->withStatus(302);
            } else {
                $stmt = $pdo->prepare('INSERT INTO messages (sender, recipient, message) VALUES (:sender, :recipient, :message)');
                $stmt->execute(['sender' => $sender, 'recipient' => $recipient, 'message' => $message]);
                return $response->withHeader('Location', '/chat/' . $recipient)->withStatus(302);
            }
        } catch (PDOException $e) {
            $response->getBody()->write("Error: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    public function getMessages(Request $request, Response $response, $args): Response
    {
        $pdo = $this->pdo;
        $sender = $_SESSION['username'];
        $recipient = $args['recipient'];

        $messages = $this->repository->fetch(
            'messages m LEFT JOIN message_reactions mr ON m.id = mr.message_id AND mr.user_id = :user_id',
            ['m.*', 'mr.reaction_type'],
            '(m.sender = :sender AND m.recipient = :recipient) OR (m.sender = :recipient AND m.recipient = :sender)',
            [
                'sender' => $sender,
                'recipient' => $recipient,
                'user_id' => $_SESSION['user_id']
            ]
        );

        $response->getBody()->write(json_encode($messages));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function reactToMessage(Request $request, Response $response, array $args): Response {
        $messageId = $args['id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        $reactionType = $request->getParsedBody()['reactionType'] ?? null;

        error_log("Received reaction request: " . json_encode(['messageId' => $messageId, 'userId' => $userId, 'reactionType' => $reactionType]));

        if (!$messageId || !$userId || !$reactionType) {
            error_log("Invalid message ID, user ID, or reaction type");
            $data = ['success' => false, 'error' => 'Invalid message ID, user ID, or reaction type'];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $pdo = $this->pdo;

        try {
            $stmt = $pdo->prepare('
            INSERT INTO message_reactions (message_id, user_id, reaction_type) 
            VALUES (:message_id, :user_id, :reaction_type)
            ON CONFLICT (message_id, user_id) 
            DO UPDATE SET reaction_type = :reaction_type
        ');
            $result = $stmt->execute([
                'message_id' => $messageId,
                'user_id' => $userId,
                'reaction_type' => $reactionType
            ]);

            if ($result) {
                error_log("Reaction saved successfully");
                $data = ['success' => true];
            } else {
                error_log("Failed to save reaction: " . json_encode($stmt->errorInfo()));
                $data = ['success' => false, 'error' => 'Failed to save reaction'];
            }
        } catch (PDOException $e) {
            error_log("PDO Exception: " . $e->getMessage());
            $data = ['success' => false, 'error' => $e->getMessage()];
        }

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function getPreviousMessages(Request $request, Response $response): Response {
        $pdo = $this->pdo;
        $sender = $request->getQueryParams()['sender'] ?? '';
        $recipient = $request->getQueryParams()['recipient'] ?? '';

        $messages = $this->repository->fetch(
            'messages',
            ['*'],
            '(sender = :sender AND recipient = :recipient) OR (sender = :recipient AND recipient = :sender)',
            [
                'sender' => $sender,
                'recipient' => $recipient
            ]
        );

        $payload = json_encode($messages);

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
    public function showChat(Request $request, Response $response, array $args): Response {
        $pdo = $this->pdo;

        $currentUser = $_SESSION['username'];
        $chatUser = $args['username'];

        if ($currentUser === $chatUser) {
            return $response->withHeader('Location', '/')->withStatus(400);
        }

        $user = $this->repository->fetchOne(
            'users',
            ['*'],
            'username = :username',
            ['username' => $chatUser]
        );

        if (!$user) {
            $response->getBody()->write('User not found');
            return $response->withStatus(404);
        }

        $html = view('chat.view.php', ['chatUser' => $user]);
        $response->getBody()->write($html);
        return $response;
    }

    private function transcribeAudio($audioStream)
    {
        $apiKey = '97a37704e7164bb9bbf6b1abadb120d0';

        $tempAudioFilePath = tempnam(sys_get_temp_dir(), 'audio') . '.mp3';
        file_put_contents($tempAudioFilePath, $audioStream);

        $reencodedFilePath = $this->reencodeMp3($tempAudioFilePath);

        $speechToText = new SpeechToText($apiKey);
        $audioUrl = $speechToText->uploadAudio($reencodedFilePath);

        if (!$audioUrl) {
            echo "Failed to upload audio file to AssemblyAI.\n";
            unlink($tempAudioFilePath);
            return null;
        }
        $data = [
            'audio_url' => $audioUrl,
            'auto_highlights' => true,
        ];
        try {
            $response = $speechToText->transcribe($audioUrl);
            if (isset($response['id'])) {
                $transcriptId = $response['id'];
                $pollingEndpoint = "https://api.assemblyai.com/v2/transcript/" . $transcriptId;
                while (true) {
                    $pollingResponse = $speechToText->getTranscription($transcriptId);
                    if ($pollingResponse['status'] === 'completed') {
                        unlink($tempAudioFilePath);
                        return $pollingResponse;
                    } elseif ($pollingResponse['status'] === 'error') {
                        echo "Transcription failed: " . ($pollingResponse['error'] ?? 'Unknown error') . "\n";
                        unlink($tempAudioFilePath);
                        return null;
                    } else {
                        sleep(3);
                    }
                }
            } else {
                unlink($tempAudioFilePath);
                echo "Failed to initiate transcription.\n";
                return null;
            }
        } catch (GuzzleException $e) {
            echo "Request failed: " . $e->getMessage() . "\n";
            unlink($tempAudioFilePath);
            return null;
        }
    }


    /**
     * @throws GuzzleException
     */
    public function handleSpeechToText(Request $request, Response $response): Response
    {
        $uploadedFiles = $request->getUploadedFiles();
        $audioFile = $uploadedFiles['audio'] ?? null;

        if ($audioFile && $audioFile->getError() === UPLOAD_ERR_OK) {
            $audioStream = $audioFile->getStream()->getContents();

            $transcriptionResult = $this->transcribeAudio($audioStream);

            if ($transcriptionResult && isset($transcriptionResult['text'])) {
                $transcriptionText = $transcriptionResult['text'];
                $response->getBody()->write($transcriptionText);
            } else {
                $response->getBody()->write('Transcription failed or returned no result.');
                return $response->withStatus(500);
            }
        } else {
            $response->getBody()->write('No valid audio file uploaded.');
            return $response->withStatus(400);
        }

        return $response->withHeader('Content-Type', 'text/plain');
    }
    private function reencodeMp3($filePath)
    {
        $ffmpeg = \FFMpeg\FFMpeg::create();
        $audio = $ffmpeg->open($filePath);

        $tempReencodedFilePath = tempnam(sys_get_temp_dir(), 'audio_reencoded') . '.mp3';
        $audio->save(new \FFMpeg\Format\Audio\Mp3(), $tempReencodedFilePath);

        return $tempReencodedFilePath;
    }

}