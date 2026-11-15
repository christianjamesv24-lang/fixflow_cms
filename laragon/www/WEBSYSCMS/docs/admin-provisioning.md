# First production admin

The normal setup creates an admin interactively with `php artisan admin:create`. For a Render service without Shell access, the container also supports a one-time production bootstrap. It creates the account directly in Render's database; it does not migrate a local account.

1. Deploy the application code with the bootstrap command.
2. In Render, set `ADMIN_BOOTSTRAP_ONCE=true`, `ADMIN_BOOTSTRAP_EMAIL` to the admin email, and `ADMIN_BOOTSTRAP_PASSWORD` to a new password of at least 12 characters. Enter the password directly in Render's secret environment settings, not in source code or chat.
3. Save the environment changes and let Render deploy. The startup command runs only when explicitly enabled, only in production, and only if no account already exists for that email. It hashes the password and never overwrites an existing account.
4. After the deployment succeeds, remove all three bootstrap variables from Render and save the changes. The entrypoint also clears them from the application process after the bootstrap command completes.

If the email already belongs to an account, the bootstrap skips it without changing its role or password. Use the normal interactive command or a secure production console for existing accounts.
