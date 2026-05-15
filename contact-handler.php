<?php
/**
 * Contact Form Handler
 * Handles form submission, validation, and email sending
 */

header('Content-Type: application/json');

// Configuration
$config = [
    'recipient_email' => 'contact@buyobusinesscommunity.jp',
    'from_email' => 'noreply@buyobusinesscommunity.jp',
    'site_name' => 'Buyokai Business Community',
    'max_message_length' => 5000,
];

// Response helper
function sendResponse($success, $message = '') {
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

// Validate input
function validateInput($data) {
    $errors = [];

    // Name validation
    if (empty($data['name'])) {
        $errors[] = 'お名前は必須項目です。';
    } elseif (strlen($data['name']) > 100) {
        $errors[] = 'お名前は100文字以内で入力してください。';
    }

    // Email validation
    if (empty($data['email'])) {
        $errors[] = 'メールアドレスは必須項目です。';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = '有効なメールアドレスを入力してください。';
    }

    // Phone validation (optional)
    if (!empty($data['phone'])) {
        if (!preg_match('/^[\d\-\s()+]*$/', $data['phone'])) {
            $errors[] = '電話番号の形式が正しくありません。';
        }
    }

    // Company validation (optional)
    if (!empty($data['company']) && strlen($data['company']) > 100) {
        $errors[] = '会社名は100文字以内で入力してください。';
    }

    // Subject validation
    $valid_subjects = ['inquiry', 'membership', 'event', 'partnership', 'other'];
    if (empty($data['subject']) || !in_array($data['subject'], $valid_subjects)) {
        $errors[] = '件名を正しく選択してください。';
    }

    // Message validation
    if (empty($data['message'])) {
        $errors[] = 'メッセージは必須項目です。';
    } elseif (strlen($data['message']) > 5000) {
        $errors[] = 'メッセージは5000文字以内で入力してください。';
    }

    return $errors;
}

// Sanitize input
function sanitizeInput($data) {
    $sanitized = [];
    foreach ($data as $key => $value) {
        $sanitized[$key] = htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
    }
    return $sanitized;
}

// Get subject label
function getSubjectLabel($subject) {
    $subjects = [
        'inquiry' => '一般的なお問合せ',
        'membership' => '会員について',
        'event' => 'イベントについて',
        'partnership' => '提携・協業について',
        'other' => 'その他',
    ];
    return $subjects[$subject] ?? 'その他';
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendResponse(false, 'Invalid request format');
}

// Validate input
$validation_errors = validateInput($input);
if (!empty($validation_errors)) {
    sendResponse(false, implode(' ', $validation_errors));
}

// Sanitize input
$data = sanitizeInput($input);

// Prepare email content
$subject_label = getSubjectLabel($data['subject']);
$email_subject = "【" . $config['site_name'] . "】お問合せを受け付けました";

$email_body = <<<EOT
{$config['site_name']} へのお問合せありがとうございます。

お問合せ内容を確認させていただきました。
お返事は{$data['email']}宛にお送りさせていただきます。

【お問合せ内容】
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

■ お名前
{$data['name']}

■ メールアドレス
{$data['email']}

EOT;

if (!empty($data['phone'])) {
    $email_body .= "■ 電話番号\n{$data['phone']}\n\n";
}

if (!empty($data['company'])) {
    $email_body .= "■ 会社名\n{$data['company']}\n\n";
}

$email_body .= <<<EOT
■ 件名
{$subject_label}

■ メッセージ
{$data['message']}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━

このメールに返信いただくか、contact@buyobusinesscommunity.jpまでお問合せください。

{$config['site_name']}

EOT;

// Prepare admin notification email
$admin_subject = "【新規問合せ】{$subject_label} - {$data['name']}";
$admin_body = <<<EOT
新しいお問合せが入りました。

【送信日時】
{$_SERVER['REQUEST_TIME']}

【お問合せ内容】
お名前: {$data['name']}
メールアドレス: {$data['email']}
EOT;

if (!empty($data['phone'])) {
    $admin_body .= "\n電話番号: {$data['phone']}";
}

if (!empty($data['company'])) {
    $admin_body .= "\n会社名: {$data['company']}";
}

$admin_body .= "\n件名: {$subject_label}\n\n【メッセージ】\n{$data['message']}";

// Send user confirmation email
$user_headers = "From: {$config['from_email']}\r\n";
$user_headers .= "Reply-To: {$config['recipient_email']}\r\n";
$user_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

if (!mail($data['email'], $email_subject, $email_body, $user_headers)) {
    // Log error but don't fail - contact was received
    error_log("Failed to send user confirmation email to {$data['email']}");
}

// Send admin notification email
$admin_headers = "From: {$config['from_email']}\r\n";
$admin_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

if (!mail($config['recipient_email'], $admin_subject, $admin_body, $admin_headers)) {
    error_log("Failed to send admin notification email");
}

// Log contact submission
$log_entry = date('Y-m-d H:i:s') . " | " . $data['email'] . " | " . $subject_label . "\n";
$log_file = __DIR__ . '/contact_log.txt';
if (!is_writable(dirname($log_file))) {
    error_log("Contact form submission not logged - directory not writable");
} else {
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Send success response
sendResponse(true, 'お問合せありがとうございます。確認メールを送信いたしました。');
?>
