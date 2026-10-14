<?php

require_once __DIR__ . '/includes/bootstrap.php';

require_login();
verify_csrf();

// Get the currently logged-in user
$u = current_user();
$id = (int) $u['id'];

// Get user's email verification status
$s = $conn->prepare(
    'SELECT email, full_name, email_verified
     FROM users
     WHERE id = ?'
);

$s->bind_param('i', $id);
$s->execute();

$r = $s->get_result()->fetch_assoc();

// Check if user exists
if (!$r) {
    flash('error', 'User account not found.');
    redirect('login.php');
}

// Stop if email is already verified
if ($r['email_verified']) {
    flash('success', 'Email already verified.');
    redirect('dashboard.php');
}

// Generate a new verification token
$token = bin2hex(random_bytes(32));

// Set token expiration to 24 hours
$expires = date(
    'Y-m-d H:i:s',
    time() + 86400
);

// Update the verification token in the database
$s = $conn->prepare(
    'UPDATE users
     SET verification_token = ?,
         verification_expires = ?
     WHERE id = ?'
);

$s->bind_param(
    'ssi',
    $token,
    $expires,
    $id
);

$s->execute();

// Send the verification email
$sent = send_verification_email(
    $r['email'],
    $r['full_name'],
    $token
);

// Display the result
if ($sent) {
    flash(
        'success',
        'Verification email sent. Please check your inbox and spam folder.'
    );
} else {
    flash(
        'error',
        'Email delivery failed. Please check the SMTP configuration.'
    );
}

redirect('verify_notice.php');
