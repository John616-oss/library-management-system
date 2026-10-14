LIBRARY MANAGEMENT SYSTEM - XAMPP SETUP
=======================================
Stack: PHP 8.x, MySQL/MariaDB, MySQLi prepared statements, HTML, CSS, JavaScript.

INSTALL
1. Extract folder library_management_system into C:\xampp\htdocs\
2. Start Apache and MySQL in XAMPP.
3. Open http://localhost/phpmyadmin and import database.sql.
4. Confirm config/config.php database values (default XAMPP root / blank password).
5. Visit http://localhost/library_management_system/register.php and create an account.
6. Email verification is required before protected pages can be used.

CREATE FIRST ADMIN
The first registration is a member. After registering, promote your account in phpMyAdmin:
UPDATE users SET role='admin', email_verified=1 WHERE email='your-email@example.com';
Log out and log back in. Use Users page to assign librarian/member roles to other accounts.
For a quick classroom demonstration, this SQL manually marks your account verified. To demonstrate actual email verification, configure email delivery and register another test account.

EMAIL DELIVERY
The application uses PHP mail(). Default XAMPP usually cannot send real mail without configuration. The email message uses a one-time token that expires after 24 hours. Configure [mail function] in php.ini and sendmail_path in sendmail.ini with a working SMTP relay, then restart Apache. For reliable SMTP, integrate PHPMailer using Composer and environment variables. Do not include real email passwords or API keys in your submission ZIP.

DEMO
1. Register and verify a member.
2. Admin creates/assigns librarian account in Users.
3. Staff adds/edits books and categories.
4. Member submits borrowing request.
5. Member submits a reservation (even if no copies are available).
6. Librarian approves -> marks ready for pickup when a copy is available. Marking Ready reserves/deducts one copy immediately so it cannot be allocated twice. Completing pickup creates the borrowing record without deducting stock again.
7. If a ready reservation is not picked up, staff can use Expire / no-show to release the reserved copy back to available stock.
8. Demonstrate queue order, cancellation, dashboard, search/filter, print report, role permissions, invalid actions, and activity logs.
9. Return the issued book from Borrowing; inventory increases and can make the next reservation eligible for pickup. Direct borrowing releases are blocked while an active reservation queue exists.

ROLES
admin: manage users/roles, catalog, borrowing, reports, logs
librarian: manage books/categories, approve/reject/release/return, reports
member: browse books, request borrowing, reserve books, cancel eligible reservations, view only own records

DATA STRUCTURE / SECURITY
Normalized relational tables with primary/foreign keys, indexes and copy-count constraint. Borrowing uses explicit transaction states. InnoDB transactions and row locks protect inventory updates. Reservations use a FIFO queue per book: earlier active reservations take priority; the librarian marks a reservation ready only when a copy is available. Completing pickup creates the borrowing record and reduces available copies in the same transaction. Passwords use password_hash/password_verify; queries use MySQLi prepared statements; forms use CSRF tokens; output is escaped; permissions enforced server-side.

The application refreshes account status and role from the database on protected requests, so deactivated users lose access without relying on a fresh login.
Before public deployment: use HTTPS, dedicated DB user, strong secrets, configured SMTP, backups, rate limiting, and production error logging. This is a classroom starter project; test locally before submission.
