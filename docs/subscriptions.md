# Technician subscriptions

Technician plans are PHP 500 monthly or PHP 5,000 yearly by default. Prices can be changed with `TECHNICIAN_SUBSCRIPTION_MONTHLY_PRICE` and `TECHNICIAN_SUBSCRIPTION_YEARLY_PRICE`.

This is a manual GCash workflow, not an automated payment integration. Configure `GCASH_ACCOUNT_NAME` and `GCASH_ACCOUNT_NUMBER` in the deployment environment. Technicians transfer the selected amount and submit the transaction reference and a JPG, PNG, or WebP receipt image (maximum 5 MB). Receipt images are stored privately and served only through the authenticated admin review route. An admin must verify the transfer in GCash before approving it under **Admin → Subscriptions**. Approval enables technician work access for the selected period; a pending or rejected transfer does not.

The repository includes a Render Blueprint and Docker setup. Create a Blueprint from the `main` branch to provision the web service and private MySQL service in Singapore. Both services use paid compute and persistent disks; review the current Render pricing before confirming. The web service disk is mounted at `storage`, preserving receipts and other local uploads. The private MySQL service uses a separate 10 GB disk.

The Blueprint generates the database credentials and Laravel application key. Enter your GCash account name and number in the secure Blueprint prompts or in the web service's environment settings. Keep `SUBSCRIPTION_PROOF_DISK=local`; do not store receipt uploads on Render's ephemeral filesystem.

The web service runs `php artisan migrate --force` before each deploy and checks `/up` for health. After the first successful deploy, open the web service Shell and create the admin interactively:

```sh
php artisan admin:create fixitnow_admin@gmail.com
```

The command prompts for the admin name and a password without echoing the password. Running `php artisan db:seed` does not create default users or delete existing data.

Back up the MySQL service with `mysqldump`; do not use a persistent-disk snapshot to restore a live MySQL data directory.
