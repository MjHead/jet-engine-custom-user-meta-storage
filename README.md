# JetEngine. Custom User Meta Storage

Integrates JetEngine Custom Meta Storage feature (https://crocoblock.com/knowledge-base/jetengine/how-to-create-custom-meta-storage-for-cpt/) and User Meta.

Usage:
- Make sure you updated __JetEngine__ plugin to the version __3.5.3__ or higher
- Download and install as usual WP plugin
- Add the next code into the your active theme `functions.php` file or with any code snippet plugin:

```php
add_action( 'init', function() {
	define( 'JET_ENGINE_CUSTOM_USER_META_FIELDS', [ 'test_user_field', 'another_field' ] );
} );
```

Where `'test_user_field', 'another_field'` - is a list of the user meta fields you want to store in the separate table. 

__Please note!__

All the fields not metioned in the list will be stored in the default `usermeta` table
