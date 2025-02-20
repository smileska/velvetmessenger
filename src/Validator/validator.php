<?php

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

function getValidator(): ValidatorInterface {
    return Validation::createValidator();
}

function validateUserData($username, $email, $password, $confirm_password) {
    $validator = getValidator();

    $usernameConstraints = [
        new Assert\NotBlank(['message' => 'Username should not be blank']),
        new Assert\Length(['min' => 3, 'max' => 20, 'minMessage' => 'Username must be at least {{ limit }} characters long', 'maxMessage' => 'Username cannot be longer than {{ limit }} characters']),
    ];

    $emailConstraints = [
        new Assert\NotBlank(['message' => 'Email should not be blank']),
        new Assert\Email(['message' => 'Please provide a valid email address']),
    ];

    $passwordConstraints = [
        new Assert\NotBlank(['message' => 'Password should not be blank']),
        new Assert\Length(['min' => 6, 'minMessage' => 'Password must be at least {{ limit }} characters long']),
    ];

    $confirmPasswordConstraints = [
        new Assert\NotBlank(['message' => 'Confirm password should not be blank']),
        new Assert\EqualTo(['value' => $password, 'message' => 'The password and confirmation password do not match']),
    ];

    $usernameViolations = $validator->validate($username, $usernameConstraints);
    $emailViolations = $validator->validate($email, $emailConstraints);
    $passwordViolations = $validator->validate($password, $passwordConstraints);
    $confirmPasswordViolations = $validator->validate($confirm_password, $confirmPasswordConstraints);

    $violations = array_merge(
        iterator_to_array($usernameViolations),
        iterator_to_array($emailViolations),
        iterator_to_array($passwordViolations),
        iterator_to_array($confirmPasswordViolations)
    );

    return $violations;
}
function validateMessageData($message, $recipient, $chatroomId) {
    $validator = getValidator();

    $messageConstraints = [
        new Assert\NotBlank(['message' => 'Message should not be blank']),
    ];

    $recipientOrChatroomConstraints = [
        new Assert\AtLeastOneOf([
            new Assert\NotBlank(['message' => 'Either recipient or chatroom ID must be provided']),
        ]),
    ];

    $messageViolations = $validator->validate($message, $messageConstraints);
    $recipientOrChatroomViolations = $validator->validate([$recipient, $chatroomId], $recipientOrChatroomConstraints);

    $violations = array_merge(
        iterator_to_array($messageViolations),
        iterator_to_array($recipientOrChatroomViolations)
    );

    return $violations;
}