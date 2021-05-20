PHP 8.0 Compatibility
=====================
There are many changes in PHP 8 that break compatibility with PHP 7.    
You can find a listing here: https://www.php.net/manual/en/migration80.incompatible.php
    
The Scavix WDF is updated to be compatible with both (PHP 7 + 8), but you need to
adjust your projects too to match the changes.
    
Biggest part (in context of scavix-wdf) is this: "Inheritance errors due to incompatible method signatures (LSP violations) will now always generate a fatal error."
It affects the way wdf instanicates objects, here's some more explanation about this:    
scavix-wdf has it's own de-/serializer mechanism that support referencial integrity and is
very fast. For historical reasons the deserializer needed to call the constructor to instanciate objects.
To avoid running much logic while deserializing scavix-wdf uses another method '__initialize' which is implicitely
called by the constructor only if there's no deserialization running.    
The '__initialize' methods have different signatures as they were used as a contructor replacement.    
PHP 8.0 compatibility requires consistent method signatures in inheritance, so we needed to remove the '__initialize'
method completely and redesign the deserializer to work without constructor calls.
Sounds kind or upside-down, but this means you need to replace any occurance of '__initialize' with '__construct'
in your projects to be compatible.    
    
Of course there are many more caveats, so we implemented a task to help you track and fix most of the
issues straight via CLI:    
```bash
cd /path/to/docroot   # this is where your index.php is stored
php index.php check-php8
```
This will list many issues that you can fix one after the other.
Some are just informational like "The @ operator will no longer silence fatal errors" which
is most likely no issue (when for example used like this: `@unlink($filename);`).
