vendor/bin/rector process src/ --config vendor/thecodingmachine/safe/rector-migrate.php
php-cs-fixer fix ./src --rules=@Symfony      
php-cs-fixer fix ./test --rules=@Symfony  
# XDEBUG_MODE=coverage ./vendor/bin/pest --coverage   
# vendor/bin/phpstan analyse src --level 8      
