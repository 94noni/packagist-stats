##
## Project
## -------
##

install: ## Install the project from scratch
install: vendor node_modules

vendor: ## Install PHP vendors
vendor: composer.lock
	symfony composer install

node_modules: ## Install assets vendors
node_modules: yarn.lock
	symfony run yarn install
	symfony run yarn dev

start: ## Start the local project
	symfony -d server:start

.PHONY: install vendor node_modules start

.DEFAULT_GOAL := help
help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

.PHONY: help
