# Initial administrator and isolated test accounts

InitialAccountSeeder is called by the normal DatabaseSeeder in local and production deployments. It provides a starter administrator identity (Municipal Administrator / admin@munialert.test) and requires a private INITIAL_ADMIN_PASSWORD; production also requires a real INITIAL_ADMIN_EMAIL. No shared password or demo residents are included. Set INITIAL_ADMIN_NAME/EMAIL/PASSWORD privately, run db:seed --force, and sign in within the 15-minute MFA setup window. Use hazard:account-security <email> --authorize-mfa if that window expires. Reruns preserve existing accounts and never recreate a deleted starter admin. Later administrators are created/deleted in Settings > Administrator accounts, with recent authentication, no self-deletion, last-active-admin protection and retained municipal history. A replacement admin must complete MFA/email/policies and sign in before removing the starter.

Run from the project root:

```sh
npm run db:migrate
npm run db:seed
```

On Hostinger run `php artisan migrate --force` then `php artisan db:seed --force` in the private server directory. Configure INITIAL_ADMIN_NAME, INITIAL_ADMIN_EMAIL and INITIAL_ADMIN_PASSWORD in private .env first, clear configuration and protect/rotate the initial password after login. No plaintext password is supplied by this documentation. Change it in My profile; share any new admin password privately. Do not copy local account data into production.

The old public demo accounts/CSV/seeder have been removed. Five known local demos were deleted on 24 November (three current samples and two older demo residents); two other existing accounts and report counts were preserved. No reserved .test account remains in the normal local database. Tests use independent synthetic browser users and random per-run passwords in guarded test databases. Browser fixture seeders refuse normal/local/production databases and are excluded from deployment artifacts.
