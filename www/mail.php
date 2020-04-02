<?php

$method = $_SERVER['REQUEST_METHOD'];

//Script Foreach
$c = true;
if ($method === 'POST') {

    $project_name = trim($_POST["project_name"]);
    $admin_email = trim($_POST["admin_email"]);
    $form_subject = trim($_POST["form_subject"]);

    foreach ($_POST as $key => $value) {
        if ($value != "" && $key != "project_name" && $key != "admin_email" && $key != "form_subject") {
            $message .= "
			" . (($c = !$c) ? '<tr>' : '<tr style="background-color: #f8f8f8;">') . "
				<td style='padding: 10px; border: #e9e9e9 1px solid;'><b>$key</b></td>
				<td style='padding: 10px; border: #e9e9e9 1px solid;'>$value</td>
			</tr>
			";
        }
    }
} else if ($method === 'GET') {

    $project_name = trim($_GET["project_name"]);
    $admin_email = trim($_GET["admin_email"]);
    $form_subject = trim($_GET["form_subject"]);

    foreach ($_GET as $key => $value) {
        if ($value != "" && $key != "project_name" && $key != "admin_email" && $key != "form_subject") {
            $message .= "
			" . (($c = !$c) ? '<tr>' : '<tr style="background-color: #f8f8f8;">') . "
				<td style='padding: 10px; border: #e9e9e9 1px solid;'><b>$key</b></td>
				<td style='padding: 10px; border: #e9e9e9 1px solid;'>$value</td>
			</tr>
			";
        }
    }
}

$message = "<table style='width: 100%;'>$message</table>";

function adopt($text)
{
    return '=?UTF-8?B?' . Base64_encode($text) . '?=';
}

$headers = "MIME-Version: 1.0" . PHP_EOL .
    "Content-Type: text/html; charset=utf-8" . PHP_EOL .
    'From: ' . adopt($project_name) . ' <' . $admin_email . '>' . PHP_EOL .
    'Reply-To: ' . $admin_email . '' . PHP_EOL;

//mail($admin_email, adopt($form_subject), $message, $headers);
Mail::smtp_mail_send('', $admin_email, adopt($form_subject), $message, $headers);

class Mail
{

    private static $config = array(
        'smtp_username' => '',
        'smtp_port' => '465',
        'smtp_host' => 'ssl://smtp.yandex.ru',
        'smtp_password' => '',
        'smtp_debug' => 'true',
        'smtp_charset' => 'utf-8',
        'smtp_from' => ''
    );

    public static function smtp_mail_send($to = '', $mail_to, $subject, $message, $headers = '')
    {
        $SEND = "Date: " . date("D, d M Y H:i:s") . " UT\r\n";
        $SEND .= 'Subject: =?' . self::$config['smtp_charset'] . '?B?' . base64_encode($subject) . "=?=\r\n";
        if ($headers)
            $SEND .= $headers . "\r\n\r\n";
        else {
            $SEND .= "Reply-To: " . self::$config['smtp_username'] . "\r\n";
            $SEND .= "To: \"=?" . self::$config['smtp_charset'] . "?B?" . base64_encode($to) . "=?=\" <$mail_to>\r\n";
            $SEND .= "MIME-Version: 1.0\r\n";
            $SEND .= "Content-Type: text/html; charset=\"" . self::$config['smtp_charset'] . "\"\r\n";
            $SEND .= "Content-Transfer-Encoding: 8bit\r\n";
            $SEND .= "From: \"=?" . self::$config['smtp_charset'] . "?B?" . base64_encode(self::$config['smtp_from']) . "=?=\" <" . self::$config['smtp_username'] . ">\r\n";
            $SEND .= "X-Priority: 3\r\n\r\n";
        }
        $SEND .= $message . "\r\n";
        if (!$socket = fsockopen(self::$config['smtp_host'], self::$config['smtp_port'], $errno, $errstr, 300)) {
            if (self::$config['smtp_debug']) {
                echo $errno . "<br>" . $errstr;
            }
            return false;
        }

        if (!self::server_parse($socket, "220", __LINE__))
            return false;

        fputs($socket, "HELO " . self::$config['smtp_host'] . "\r\n");
        if (!self::server_parse($socket, "250", __LINE__)) {
            if (self::$config['smtp_debug'])
                echo '<p>Не могу отправить HELO!</p>';
            fclose($socket);
            return false;
        }
        fputs($socket, "AUTH LOGIN\r\n");
        if (!self::server_parse($socket, "334", __LINE__)) {
            if (self::$config['smtp_debug'])
                echo '<p>Не могу найти ответ на запрос авторизаци.</p>';
            fclose($socket);
            return false;
        }
        fputs($socket, base64_encode(self::$config['smtp_username']) . "\r\n");
        if (!self::server_parse($socket, "334", __LINE__)) {
            if (self::$config['smtp_debug'])
                echo '<p>Логин авторизации не был принят сервером!</p>';
            fclose($socket);
            return false;
        }
        fputs($socket, base64_encode(self::$config['smtp_password']) . "\r\n");
        if (!self::server_parse($socket, "235", __LINE__)) {
            if (self::$config['smtp_debug'])
                echo '<p>Пароль не был принят сервером как верный! Ошибка авторизации!</p>';
            fclose($socket);
            return false;
        }
        fputs($socket, "MAIL FROM: <" . self::$config['smtp_username'] . ">\r\n");
        if (!self::server_parse($socket, "250", __LINE__)) {
            if (self::$config['smtp_debug'])
                echo '<p>Не могу отправить комманду MAIL FROM: </p>';
            fclose($socket);
            return false;
        }
        fputs($socket, "RCPT TO: <" . $mail_to . ">\r\n");

        if (!self::server_parse($socket, "250", __LINE__)) {
            if (self::$config['smtp_debug'])
                echo '<p>Не могу отправить комманду RCPT TO: </p>';
            fclose($socket);
            return false;
        }
        fputs($socket, "DATA\r\n");

        if (!self::server_parse($socket, "354", __LINE__)) {
            if (self::$config['smtp_debug'])
                echo '<p>Не могу отправить комманду DATA</p>';
            fclose($socket);
            return false;
        }
        fputs($socket, $SEND . "\r\n.\r\n");

        if (!self::server_parse($socket, "250", __LINE__)) {
            if (self::$config['smtp_debug'])
                echo '<p>Не смог отправить тело письма. Письмо не было отправленно!</p>';
            fclose($socket);
            return false;
        }
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        return TRUE;
    }

    public static function server_parse($socket, $response, $line = __LINE__)
    {
        while (@substr($server_response, 3, 1) != ' ') {
            if (!($server_response = fgets($socket, 256))) {
                if (self::$config['smtp_debug'])
                    echo "<p>Проблемы с отправкой почты!</p>$response<br>$line<br>";
                return false;
            }
        }
        if (!(substr($server_response, 0, 3) == $response)) {
            if (self::$config['smtp_debug'])
                echo "<p>Проблемы с отправкой почты!</p>$response<br>$line<br>";
            return false;
        }
        return true;
    }

}
