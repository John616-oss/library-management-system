<?php

require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    // Validate registration inputs
    if (
        !$name ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($pass) < 8 ||
        $pass !== $confirmPass
    ) {
        flash(
            'error',
            'Enter a name, valid email, and matching password of at least 8 characters.'
        );

        redirect('register.php');
    }

    // Check if email is already registered
    $s = $conn->prepare(
        'SELECT id FROM users WHERE email = ?'
    );

    $s->bind_param('s', $email);
    $s->execute();

    if ($s->get_result()->num_rows > 0) {
        flash('error', 'Email already registered.');
        redirect('register.php');
    }

    // Set default role and secure password
    $role = 'member';
    $hash = password_hash($pass, PASSWORD_DEFAULT);

    // Generate verification token valid for 24 hours
    $token = bin2hex(random_bytes(32));
    $expires = date(
        'Y-m-d H:i:s',
        time() + 86400
    );

    // Create the account
    $s = $conn->prepare(
        'INSERT INTO users
        (
            full_name,
            email,
            password_hash,
            role,
            verification_token,
            verification_expires
        )
        VALUES (?, ?, ?, ?, ?, ?)'
    );

    $s->bind_param(
        'ssssss',
        $name,
        $email,
        $hash,
        $role,
        $token,
        $expires
    );

    $s->execute();

    $uid = $conn->insert_id;

    // Record account creation
    log_activity(
        $conn,
        $uid,
        'account_created',
        'Self registration'
    );

    // Send verification email
    if (send_verification_email($email, $name, $token)) {
        flash(
            'success',
            'Account created. Check your email to verify.'
        );
    } else {
        flash(
            'error',
            'Account created, but email delivery failed. Please check the SMTP configuration.'
        );
    }

    redirect('login.php');
}

// Display registration page
page_header('Register');
?>

<div class="auth-card">

    <h1>Create account</h1>

    <p class="muted">
        New accounts start as members.
        Admin can assign staff roles.
    </p>

    <form method="post">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <label>Full name</label>
        <input
            type="text"
            name="full_name"
            required
            maxlength="120"
        >

        <label>Email</label>
        <input
            type="email"
            name="email"
            required
        >

        <label>Password (8+ characters)</label>
        <input
            type="password"
            name="password"
            minlength="8"
            required
        >

        <label>Confirm password</label>
        <input
            type="password"
            name="confirm_password"
            minlength="8"
            required
        >

        <button type="submit" class="btn full">
            Register
        </button>

    </form>

</div>

<?php page_footer(); ?>
