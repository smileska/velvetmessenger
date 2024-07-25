<!DOCTYPE html>
<html>
<head>
    <title>Send Message</title>
</head>
<body>
<h1>Send a Message</h1>
<form action="/send-message" method="post">
    <label for="recipient">Recipient:</label>
    <input type="text" id="recipient" name="recipient" required>
    <br>
    <label for="message">Message:</label>
    <textarea id="message" name="message" required></textarea>
    <br>
    <button type="submit">Send</button>
</form>
</body>
</html>
