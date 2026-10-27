# Team sign-in

All application PHP pages and API endpoints require sign-in before reading data or executing application logic. Only explicitly provisioned addresses at `forgedcoreengineering.com` can authenticate; a matching domain alone never grants access. There is no public registration or password reset.

## Provision accounts

The approved `auth-private/users.json` is tracked so the Plesk deployment receives the account list during a Git pull. This file contains email addresses and password hashes, never plaintext passwords. Keep the repository private. Without this file the application stays locked. Apache blocks browser access to the entire auth-private directory; throttling state and deployment archives remain excluded from Git.

To replace the approved account list, supply JSON to the CLI tool on stdin:

    php tools/manage-users.php

Input shape (illustrative values only):

    {"domain":"forgedcoreengineering.com","users":[{"email":"approved-person@forgedcoreengineering.com","name":"Team member","password":"replace-with-the-account-password"}]}

The command reads stdin until EOF, validates the entire list, hashes the passwords and atomically replaces the configuration. Prefer piping from a protected password manager or an interactive process; do not put real passwords in shell arguments/history or a tracked script. Passwords must be 8–72 bytes. Omitted accounts lose access immediately on their next request. Changing a password also invalidates that account's existing sessions.

## Sessions and protection

- Sign-in rotates the session ID. Sessions expire after 30 minutes of inactivity or eight hours from sign-in.
- Cookies are HttpOnly, SameSite=Lax and Secure when served over HTTPS. Use HTTPS in production. Configure the web server's HTTPS indicator correctly if TLS terminates at a proxy.
- Failed sign-ins use both account and IP counters with a ten-attempt, fifteen-minute window. Throttling state is private, file-locked and survives session resets.
- Login and logout require CSRF tokens. Authenticated writes reject mismatched Origin/Referer headers. Existing accounting forms also retain their own CSRF tokens.
- API requests receive JSON 401 responses when unauthenticated. Page requests redirect to sign-in. Return URLs are restricted to local application PHP pages.
- Sign out is available in the sidebar; open the navigation menu on mobile. It ends the session and opens a branded confirmation page with a link back to sign-in. Both authentication screens use the existing company logo and responsive layouts.

Apache rules block direct access to credentials, includes, SQL/workbook/database files, document archives, and generated PDFs. Use authenticated `download.php?file=NAME.pdf` for existing PDFs; public branding images under receipts/static remain available. On another web server, configure equivalent rules. The PHP development server ignores .htaccess and is not a production server.

The PHP process needs write permission on auth-private for throttling and on its configured session directory. Existing business/accounting records are unchanged by sign-in. Account credentials and database credentials are configured separately.

## Verification

The implementation was checked against an isolated Apache/PHP fixture: 69 HTTP checks cover every page/API gate (including footprints), direct-file denial, login/logout CSRF, rejected credentials, cookie flags, session ID rotation, cross-site writes, the branded sign-out page, public logo/styles and throttling across cookie resets. Ten additional checks cover inactivity/absolute expiry, credential changes and return-URL validation. No production financial records are mutated by these checks.

The HTTP regression script is `tools/test-auth.py`. It expects an isolated localhost Apache fixture containing a single dummy test account (specified in the script), a synthetic receipts/test.pdf, the application files and access rules. Run it only against that disposable fixture; it deliberately triggers account/IP throttling. PHP's built-in server cannot verify Apache .htaccess protection.
