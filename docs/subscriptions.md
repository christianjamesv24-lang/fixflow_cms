# Technician subscriptions

Technician plans are PHP 500 monthly or PHP 5,000 yearly by default. Prices can be changed with `TECHNICIAN_SUBSCRIPTION_MONTHLY_PRICE` and `TECHNICIAN_SUBSCRIPTION_YEARLY_PRICE`.

This is a manual GCash workflow, not an automated payment integration. Configure `GCASH_ACCOUNT_NAME` and `GCASH_ACCOUNT_NUMBER` in the deployment environment. Technicians transfer the selected amount and submit the transaction reference and a JPG, PNG, or WebP receipt image (maximum 5 MB). Receipt images are stored privately and served only through the authenticated admin review route. An admin must verify the transfer in GCash before approving it under **Admin → Subscriptions**. Approval enables technician work access for the selected period; a pending or rejected transfer does not.

Render's local filesystem is temporary unless a persistent disk is attached. Keep `SUBSCRIPTION_PROOF_DISK=local` and mount a persistent disk at `storage/app/private` for durable receipt storage. Ensure the mounted directory is writable by the application. Do not use the ephemeral default filesystem for production receipts.

After configuring the production database and running migrations, create the first admin interactively:

```powershell
php artisan migrate --force
php artisan admin:create fixitnow_admin@gmail.com
```

The command prompts for the admin name and a password without echoing the password. Use the same command in the hosting provider's shell so the account is created in the production database. Running `php artisan db:seed` does not create default users or delete existing data.
