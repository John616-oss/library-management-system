 <?php


function e($v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function redirect(string $p):never{header('Location: '.BASE_URL.'/'.ltrim($p,'/'));exit;}
function csrf_token():string{if(empty($_SESSION['csrf_token']))$_SESSION['csrf_token']=bin2hex(random_bytes(32));return $_SESSION['csrf_token'];}
function verify_csrf():void{if($_SERVER['REQUEST_METHOD']==='POST'&&!hash_equals($_SESSION['csrf_token']??'',(string)($_POST['csrf_token']??''))){http_response_code(403);exit('Invalid request token. Go back and retry.');}}
function flash(string $t,string $m):void{$_SESSION['flash']=['type'=>$t,'message'=>$m];}
function show_flash():void{if(!empty($_SESSION['flash'])){$f=$_SESSION['flash'];echo '<div class="alert '.e($f['type']).'">'.e($f['message']).'</div>';unset($_SESSION['flash']);}}
function current_user():?array{return $_SESSION['user']??null;}
function require_login():void{if(!current_user()){flash('error','Please log in first.');redirect('login.php');}}
function require_verified():void{
    require_login();
    global $conn;
    $id=(int)($_SESSION['user']['id']??0);
    $s=$conn->prepare('SELECT id,full_name,email,role,email_verified,is_active FROM users WHERE id=? LIMIT 1');
    $s->bind_param('i',$id);$s->execute();$fresh=$s->get_result()->fetch_assoc();
    if(!$fresh || !(int)$fresh['is_active']){$_SESSION=[];if(session_status()===PHP_SESSION_ACTIVE)session_destroy();redirect('login.php');}
    $_SESSION['user']=['id'=>(int)$fresh['id'],'full_name'=>$fresh['full_name'],'email'=>$fresh['email'],'role'=>$fresh['role'],'email_verified'=>(int)$fresh['email_verified']];
    if(empty($_SESSION['user']['email_verified'])){flash('error','Verify your email before continuing.');redirect('verify_notice.php');}
}
function require_role(array $roles):void{require_verified();if(!in_array($_SESSION['user']['role'],$roles,true)){http_response_code(403);exit('403 Forbidden: insufficient permissions.');}}
function log_activity(mysqli $c,int $uid,string $action,string $details=''):void{$ip=substr($_SERVER['REMOTE_ADDR']??'unknown',0,45);$s=$c->prepare('INSERT INTO activity_logs(user_id,action,details,ip_address) VALUES(?,?,?,?)');$s->bind_param('isss',$uid,$action,$details,$ip);$s->execute();}
function send_verification_email(
    string $email,
    string $name,
    string $token
): bool {

    try {
        require_once __DIR__ . '/../vendor/autoload.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure =
            \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        $mail->CharSet = 'UTF-8';
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($email, $name);

        $url = BASE_URL . '/verify.php?token=' . urlencode($token);

        $safeName = htmlspecialchars(
            $name,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $safeUrl = htmlspecialchars(
            $url,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $mail->isHTML(true);
        $mail->Subject = APP_NAME . ' - Verify your email';

        $mail->Body = "
            <h2>Hello {$safeName}!</h2>
            <p>Please click the link below to verify your account.</p>
            <p><a href=\"{$safeUrl}\">Verify My Email</a></p>
            <p>This link expires in 24 hours.</p>
            <p>If you did not register, ignore this email.</p>
        ";

        $mail->AltBody =
            "Hello {$name},\n\n" .
            "Verify your account using this link (expires in 24 hours):\n" .
            "{$url}\n\n" .
            "If you did not register, ignore this email.";

        $mail->send();

        return true;

    } catch (\Throwable $e) {
        error_log(
            'Verification email failed: ' . $e->getMessage()
        );

        return false;
    }
}

function status_badge(string $s):string{return '<span class="badge '.e($s).'">'.e(ucfirst($s)).'</span>';}
function page_header(string $title):void{$u=current_user();?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title)?> · <?=e(APP_NAME)?></title><link rel="stylesheet" href="<?=e(BASE_URL)?>/assets/style.css"></head><body><header class="topbar"><a class="brand" href="<?=e(BASE_URL)?>/index.php">📚 <?=e(APP_NAME)?></a><nav><?php if($u):?><a href="<?=e(BASE_URL)?>/dashboard.php">Dashboard</a><a href="<?=e(BASE_URL)?>/books.php">Books</a><a href="<?=e(BASE_URL)?>/reservations.php">Reservations</a><?php if(in_array($u['role'],['admin','librarian'],true)):?><a href="<?=e(BASE_URL)?>/categories.php">Categories</a><a href="<?=e(BASE_URL)?>/borrowings.php">Borrowing</a><a href="<?=e(BASE_URL)?>/reports.php">Reports</a><?php endif;?><?php if($u['role']==='admin'):?><a href="<?=e(BASE_URL)?>/users.php">Users</a><a href="<?=e(BASE_URL)?>/activity_logs.php">Logs</a><?php endif;?><span><?=e($u['full_name'])?> · <?=e($u['role'])?></span><a href="<?=e(BASE_URL)?>/logout.php">Logout</a><?php else:?><a href="<?=e(BASE_URL)?>/login.php">Login</a><a href="<?=e(BASE_URL)?>/register.php">Register</a><?php endif;?></nav></header><main class="container"><?php show_flash();?>
<?php }
function page_footer():void{?><footer class="footer">© <?=date('Y')?> <?=e(APP_NAME)?> · PHP + MySQLi</footer><script src="<?=e(BASE_URL)?>/assets/app.js"></script></main></body></html><?php }