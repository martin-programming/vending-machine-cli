DC  = docker compose
APP = $(DC) exec app

.DEFAULT_GOAL := help

.PHONY: help build start stop restart shell test test-unit test-feature test-coverage vending install fresh lint lint-fix analyse

help:
	@awk 'BEGIN {FS = ":.*## "}; /^[a-zA-Z0-9_-]+:.*## / {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: ## Build the Docker image
	$(DC) build

start: ## Start containers in the background
	$(DC) up -d

stop: ## Stop and remove containers
	$(DC) down

restart: ## Restart containers
	$(DC) restart

fresh: ## Rebuild image from scratch and restart
	$(DC) down -v
	$(DC) build --no-cache
	$(DC) up -d

test: ## Run the full test suite inside the app container (pass args="--filter=name" to filter)
	$(APP) php artisan test $(args)

vending: ## Start the interactive vending machine
	$(DC) exec -it app php artisan vending-machine

lint: ## Check code style (Laravel Pint)
	$(APP) ./vendor/bin/pint --test

lint-fix: ## Auto-fix code style issues (Laravel Pint)
	$(APP) ./vendor/bin/pint

analyse: ## Run static analysis (PHPStan)
	$(APP) ./vendor/bin/phpstan analyse
