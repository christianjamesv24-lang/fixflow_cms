# Password recovery email

The forgotten-password form sends reset links through Laravel's configured mailer. Keep credentials in the hosting provider's secret environment settings.

## Gmail API on Render Free

Render Free blocks outbound SMTP ports 25, 465, and 587. This project supports Gmail API over HTTPS, using the Gmail account you authorize as the sender.

1. In Google Cloud Console, enable the Gmail API and create an OAuth client ID of type **Web application**. Add `https://developers.google.com/oauthplayground` as an authorized redirect URI.
2. In [Google OAuth 2.0 Playground](https://developers.google.com/oauthplayground), enable **Use your own OAuth credentials** and enter the OAuth client ID and secret.
3. Request only `https://www.googleapis.com/auth/gmail.send`, authorize with the sender Gmail account, then exchange the code for a refresh token. Treat the refresh token like a password.
4. In the Render service environment, set `MAIL_MAILER=gmail-api`, `MAIL_FROM_ADDRESS` to the authorized Gmail address, and set `GMAIL_OAUTH_CLIENT_ID`, `GMAIL_OAUTH_CLIENT_SECRET`, and `GMAIL_OAUTH_REFRESH_TOKEN`. `MAIL_FROM_NAME` is optional.
5. Save the environment changes and let Render redeploy. Submit the forgot-password form for an account you control, then check the inbox and spam folder.

Temporary Gmail API failures (connection errors, HTTP 408, 429, and 5xx responses) are retried up to three times. Logs include the failed endpoint's HTTP status without logging message contents or credentials. If Google's OAuth consent screen is in Testing mode, refresh tokens may expire after seven days.

## Resend alternative

To use Resend instead, verify a sending domain, create an API key, and set `MAIL_MAILER=resend`, `RESEND_API_KEY`, and `MAIL_FROM_ADDRESS` to an address on the verified domain. `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, and `MAIL_SCHEME` are not used by this mailer. Do not paste the API key into source files, commits, or chat.

## SMTP on hosts that allow outbound SMTP

For Gmail SMTP, use `smtp.gmail.com`, port `587`, STARTTLS (`MAIL_SCHEME=smtp`), the full Gmail address as `MAIL_USERNAME`, and a Google app password as `MAIL_PASSWORD`. Render Free cannot use this route because it blocks outbound SMTP ports.
