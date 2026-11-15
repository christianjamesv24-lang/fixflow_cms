# Technician subscriptions

Technician plans are PHP 500 monthly or PHP 5,000 yearly by default. Prices can be changed with `TECHNICIAN_SUBSCRIPTION_MONTHLY_PRICE` and `TECHNICIAN_SUBSCRIPTION_YEARLY_PRICE`.

Technicians register their own account and choose a monthly or yearly plan. In local development, the app defaults to a clearly labeled demo checkout so a student project can demonstrate the subscription flow without a merchant account. Simulated payments do not contact GCash or move money.

For a presentation deployment on Render or another production host, set both `SUBSCRIPTION_DEMO_MODE=true` and `SUBSCRIPTION_DEMO_ALLOW_PRODUCTION=true`. This is an explicit opt-in that allows simulated checkouts to activate demo subscriptions in production. The checkout is labeled as a simulation. Use this only for a presentation-only deployment with no real payment processing; turn both settings off after the presentation. On Vercel Preview, only `SUBSCRIPTION_DEMO_MODE=true` is needed.

When PayMongo is configured, technicians are sent to PayMongo Hosted Checkout to pay by GCash. The app activates the subscription and approves the technician account only after receiving a valid `checkout_session.payment.paid` webhook; returning to the site after checkout is not proof of payment. PayMongo payments cannot be manually approved or rejected in the admin panel.

To configure PayMongo:

1. Create and verify a PayMongo merchant account. In **Settings → Developers**, copy the secret key into the local or production `.env` as `PAYMONGO_SECRET_KEY`. Never commit or share the key.
2. In **Settings → Webhooks**, add `https://your-public-domain/webhooks/paymongo`, subscribe to `checkout_session.payment.paid`, and set the generated signing secret as `PAYMONGO_WEBHOOK_SECRET`.
3. Use a publicly reachable HTTPS URL for both the webhook and `APP_URL`. For local testing, use an HTTPS development tunnel; a localhost URL cannot receive PayMongo webhooks.
4. Run `php artisan migrate --force` after deploying. Clear cached configuration after changing environment variables with `php artisan config:clear` (or rebuild the production config cache).
5. Start with PayMongo test-mode keys. Before live payments, complete PayMongo's account verification and enable GCash for the account, then replace both credentials with the live key and webhook signing secret.

Until both PayMongo credentials are configured, an optional manual GCash fallback is available by setting `GCASH_ACCOUNT_NAME` and `GCASH_ACCOUNT_NUMBER`. Technicians submit the transaction reference and a JPG, PNG, or WebP receipt image (maximum 5 MB). Receipt images are private and available only through the authenticated admin review route. An admin must verify each transfer in GCash and approve it under **Admin → Subscriptions**. Manual approval activates the subscription and approves the technician account together. Do not ask a technician to send money until the payment details or PayMongo checkout are properly configured.

Render's local filesystem is temporary unless a persistent disk is attached. Keep `SUBSCRIPTION_PROOF_DISK=local` and mount a persistent disk at `storage/app/private` for durable receipt storage. Ensure the mounted directory is writable by the application. Do not use the ephemeral default filesystem for production receipts.

After configuring the production database and running migrations, create the first admin interactively:

```powershell
php artisan migrate --force
php artisan admin:create fixitnow_admin@gmail.com
```

The command prompts for the admin name and a password without echoing the password. Use the same command in the hosting provider's shell so the account is created in the production database. Running `php artisan db:seed` does not create default users or delete existing data.
