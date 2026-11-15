# Password recovery email

The forgotten-password form sends reset links through Laravel's configured mailer. Keep credentials in the hosting provider's secret environment settings.

## Render Free

Render Free web services block outbound SMTP ports 25, 465, and 587. A Gmail SMTP connection to `smtp.gmail.com:587` therefore times out even when the Gmail credentials are correct. Use an email API over HTTPS instead.

This project supports the Resend mailer. To enable it:

1. Verify a sending domain in Resend and create an API key. Resend requires a verified sender domain for production delivery.
2. In the Render service environment, set `MAIL_MAILER=resend`, `RESEND_API_KEY` to the API key, and `MAIL_FROM_ADDRESS` to an address on the verified domain. `MAIL_FROM_NAME` is optional.
3. Save the environment changes and let Render redeploy. The Resend API uses HTTPS; `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, and `MAIL_SCHEME` are not used by this mailer.
4. Submit the forgot-password form for an account you control and check both the inbox and spam folder. Check the Render logs for the sanitized `Password reset email delivery failed` event if delivery does not occur.

The project includes Laravel's Resend transport SDK. Do not paste the API key into source files, commits, or chat.

## SMTP on hosts that allow outbound SMTP

For Gmail SMTP, use `smtp.gmail.com`, port `587`, STARTTLS (`MAIL_SCHEME=smtp`), the full Gmail address as `MAIL_USERNAME`, and a Google app password as `MAIL_PASSWORD`. Render Free cannot use this route because it blocks outbound SMTP ports.
