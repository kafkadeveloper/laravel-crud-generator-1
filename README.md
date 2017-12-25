# laravel-crud-generator
Laravel Artisan command that generates Create/View/Edit/Delete templates from database fields
## Features

+ Reading database table fields and creating Bootstrap based Laravel Blade templates
+ Creating dropdown form items from MySQL enum types
+ Uses the Laravel default auth template, responsive

## How to use

Set up your migration files, do a migration with Artisan then do artisan view:scaffold, you'll find all the views under resources/views/{table_name}
