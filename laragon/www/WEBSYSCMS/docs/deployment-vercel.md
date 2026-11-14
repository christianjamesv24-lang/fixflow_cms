# Deploying to Vercel

This Laravel application can run on Vercel's Container Runtime, which is currently in beta. The root `Dockerfile` builds the PHP/Apache application and frontend assets. In Vercel project settings, choose the **Container** framework preset. Do not select the Vite preset for the Laravel application.

## Required before deployment

1. **Configure the repository root.** The application is stored in `laragon/www/WEBSYSCMS` within the GitHub repository. Set the Vercel project's Root Directory to that path.
2. **Create and configure a Vercel project.** Import the intended GitHub repository, set the Root Directory as above, and choose the **Container** framework preset. Do not select the Vite preset for the Laravel application.
3. **Provision an external Aiven MySQL service.** In Aiven, create a MySQL service and use its connection details for `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in Vercel. Do not use the local Laragon database. Keep TLS enabled: download the service CA certificate from Aiven, then encode it on Windows PowerShell:

   ```powershell
   [Convert]::ToBase64String([IO.File]::ReadAllBytes('ca.pem'))
   ```

   Add the result as `MYSQL_ATTR_SSL_CA_BASE64` in Vercel. The container writes the certificate to temporary storage and configures Laravel's MySQL TLS connection. Keep database credentials only in Vercel's environment settings, not in Git or chat.
4. **Set application environment variables.** Configure a unique `APP_KEY`, the deployment's `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false`, and `SUBSCRIPTION_DEMO_MODE=false` for Production. Generate a key with `php artisan key:generate --show` and add it only in Vercel's environment settings. To deliver password recovery emails, configure a production mail provider with `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME`, and `MAIL_FROM_ADDRESS`; do not use the log mailer in production.
5. **Run database migrations** against the provisioned database before opening the deployment:

   ```powershell
   php artisan migrate --force
   ```

   Make sure the command is using the production database credentials, and take a backup before applying migrations to an existing database. The container does not run migrations at startup.
6. **Provide persistent object storage before enabling uploads.** The container's Laravel storage is under `/tmp` and is ephemeral. Subscription receipts and the admin branding-logo upload must not rely on local container storage for durable production data. Configure persistent S3-compatible storage and the app's upload paths before accepting uploads.

The container defaults to cookie-backed sessions, in-memory cache, and logs on standard error so it does not depend on writable local storage for those services. The Laravel `APP_KEY` must be set or the container exits with a configuration error.

## School-project demo checkout

The simulated checkout never contacts GCash. To allow it in a Vercel **Preview** deployment, set `SUBSCRIPTION_DEMO_MODE=true` only for the Preview environment and use a separate test database. Vercel supplies `VERCEL_ENV=preview` automatically. Keep `SUBSCRIPTION_DEMO_MODE=false` in Production; simulated success is blocked there. The checkout screen clearly identifies the flow as a simulation.

## Current limitations

- The Vercel account is signed in, but no project has been imported and no Aiven database is configured yet.
- Docker is not installed here, so the image cannot be built locally at this time.
- No live payment provider is configured. Production PayMongo checkout will remain unavailable until merchant credentials and a publicly reachable webhook are configured.
