SHELL := /bin/sh
COMPOSE := docker compose
APP := $(COMPOSE) exec app
TEST := $(COMPOSE) --profile test run --rm test

.DEFAULT_GOAL := help
.PHONY: help install build build-production up down restart logs ps shell composer-install init \
	migrate seed fresh db-fresh test test-unit test-feature test-integration test-security \
	test-architecture test-parallel test-coverage coverage lint format analyse quality \
	docs openapi docs-check readme-check security-check trace-check rules-check skills-check validate \
	ai-task-start ai-task-status ai-task-check ai-task-complete ai-approve ai-context \
	ai-sync-skills ai-trace-check ai-security-check git-safe-commit git-safe-push pr-create ai-finish

help: ## Show available commands
	@awk 'BEGIN {FS = ":.*## "}; /^[a-zA-Z0-9_-]+:.*## / {printf "%-22s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: ## Build development application images
	$(COMPOSE) build app nginx

build-production: ## Build the production runtime image
	docker build --target production -t laravel-api:production .

up: ## Start the development stack
	$(COMPOSE) up -d --build

down: ## Stop the development stack
	$(COMPOSE) down --remove-orphans

restart: down up ## Restart the development stack

logs: ## Follow service logs
	$(COMPOSE) logs -f

ps: ## Show service health and status
	$(COMPOSE) ps

shell: ## Open a shell as the application user
	$(APP) sh

composer-install: ## Install Composer dependencies
	$(APP) composer install --no-interaction --prefer-dist

install: composer-install ## Install project dependencies

init: ## Initialize keys, database, and admin
	$(APP) php artisan boilerplate:init

migrate: ## Run pending migrations
	$(APP) php artisan migrate --force

seed: ## Run idempotent seeders
	$(APP) php artisan db:seed --force

db-fresh: ## Rebuild testing database only
	@test "$${APP_ENV:-}" = "testing" || { echo "Refusing db-fresh: APP_ENV must be testing."; exit 1; }
	$(TEST) php artisan migrate:fresh --seed --force

fresh: db-fresh ## Rebuild testing database only

test: ## Run the Pest test suite
	$(TEST) php artisan test

test-unit: ## Run unit tests
	$(TEST) php artisan test tests/Unit

test-feature: ## Run feature tests
	$(TEST) php artisan test tests/Feature

test-integration: ## Run integration tests
	$(TEST) php artisan test tests/Integration

test-security: ## Run security tests
	$(TEST) php artisan test tests/Security

test-architecture: ## Run architecture tests
	$(TEST) php artisan test tests/Architecture

test-parallel: ## Run tests in parallel
	$(TEST) php artisan test --parallel

coverage: ## Enforce at least 85 percent coverage
	$(TEST) php artisan test --coverage --min=85

test-coverage: coverage ## Enforce at least 85 percent coverage

lint: ## Check code formatting
	$(TEST) vendor/bin/pint --test

format: ## Format PHP code
	$(APP) vendor/bin/pint

analyse: ## Run Larastan static analysis
	$(TEST) vendor/bin/phpstan analyse --no-progress --memory-limit=1G

quality: validate lint analyse test security-check docs-check readme-check ## Run the local quality gate

openapi: ## Export the OpenAPI document
	$(APP) php artisan scramble:export --path=docs/openapi/openapi.json

docs: openapi ## Export the OpenAPI document

docs-check: ## Validate documentation hooks
	$(TEST) php artisan test tests/Integration/OpenApiTest.php
	$(TEST) ./scripts/ai/docs-check.sh

readme-check: ## Validate README command references
	./scripts/ai/readme-check.sh

security-check: ## Audit dependencies and scan tracked content
	$(TEST) composer audit --locked --no-interaction
	./scripts/ai/security-check.sh worktree
	./scripts/ai/secret-scan.sh worktree

trace-check: ## Validate sanitized trace files
	./scripts/ai/trace-check.sh

rules-check: ## Validate Cursor rules when present
	./scripts/ai/rules-check.sh

skills-check: ## Validate agent skills when present
	./scripts/ai/skill-check.sh

validate: ## Validate infrastructure and project metadata
	docker compose config --quiet
	$(TEST) composer validate --strict --no-interaction
	./scripts/check-project.sh all
	./scripts/ai/readme-check.sh
	./scripts/ai/rules-check.sh
	./scripts/ai/skill-check.sh
	./scripts/ai/trace-check.sh

ai-task-start: ## Create a sanitized AI trace
	@test -n "$(PURPOSE)" || { echo "PURPOSE is required."; exit 1; }
	./scripts/ai/trace.sh new "$(PURPOSE)"

ai-task-status: ## Show active AI trace status
	./scripts/ai/trace-check.sh

ai-task-check: ## Validate the active AI task
	./scripts/ai/validate.sh

ai-task-complete: ## Mark the active AI task validated
	./scripts/ai/trace.sh finish

ai-approve: ## Record an explicitly granted operation approval
	@test -n "$(OPERATION)" || { echo "OPERATION=commit|push|pr is required."; exit 1; }
	AI_HUMAN_APPROVAL=yes ./scripts/ai/approval.sh record "$(OPERATION)" "$(or $(APPROVER),human)"

ai-context: ## Print focused context for TASK
	@test -n "$(TASK)" || { echo "TASK is required."; exit 1; }
	./scripts/ai/context.sh "$(TASK)"

ai-sync-skills: ## Synchronize canonical agent Skills
	./scripts/ai/skill-sync.sh

ai-trace-check: trace-check ## Validate active trace

ai-security-check: security-check ## Run repository security checks

git-safe-commit: ## Commit staged changes after explicit approval
	@test -n "$(SUBJECT)" || { echo "SUBJECT is required."; exit 1; }
	./scripts/ai/safe-commit.sh "$(SUBJECT)"

git-safe-push: ## Push the current branch after explicit approval
	./scripts/ai/safe-push.sh

pr-create: ## Prepare pull request content after approval
	./scripts/ai/create-pull-request.sh "$(or $(BASE_BRANCH),develop)"

ai-finish: ## Validate and finalize local AI work
	./scripts/ai/ai-finish.sh
