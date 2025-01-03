PORT ?= 8000
dump-autoload:
	composer dump-autoload

start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src public

fix-lint:
	composer exec --verbose phpcbf -- --standard=PSR12 src public

install:
	composer install

validate:
	composer validate

