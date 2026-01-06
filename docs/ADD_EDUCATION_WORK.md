## Add Education & WorkExperience - Migration & Test Steps

After adding the new entities (`Education`, `WorkExperience`) you need to update the database schema and test the admin flows.

1. Create a Doctrine migration:

   ```bash
   php bin/console make:migration
   ```

2. Review the generated migration and run it:

   ```bash
   php bin/console doctrine:migrations:migrate
   ```

3. Clear cache and warmup (optional but recommended):

   ```bash
   php bin/console cache:clear
   php bin/console cache:warmup
   ```

4. Test the admin UI:
   - Login as admin
   - Visit `/admin/education` and `/admin/work-experience`
   - Create, edit, delete records and confirm they appear on the public site where relevant

5. If you are using a local web server (symfony CLI):

   ```bash
   symfony serve
   ```

If any issue occurs, check `var/log/` and `php -v`/extensions to ensure migrations run correctly.
