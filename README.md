wp-crypto-accounts
==================

Allow Wordpress users to deposit and withdraw bitcoin.

More documentation to come.

Running tests
-------------

1. Create a database user.
2. run ./bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [host]
3. Run phpunit

Caveats when running tests
--------------------------

Host defaults to localhost, which should in theory be fine. This might not always work, 
however, depending on your php setup. You can try 127.0.0.1 as well.
