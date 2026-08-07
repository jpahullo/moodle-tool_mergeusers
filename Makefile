# Coexistence between running make from the host and from a shell already inside the
# dev container: /.dockerenv only exists inside a real Docker container, so it wins
# over container_name - a .env checked in (or exported) with container_name set
# still works unwrapped from inside the container, no need to unset/comment it out
# to switch between the two. Only outside a container, with container_name defined,
# do targets wrap with "docker exec -it $(container_name)"; otherwise php runs bare.
in-container := $(wildcard /.dockerenv)
current-dir := $(dir $(realpath $(lastword $(MAKEFILE_LIST))))

# Absolute path to this plugin, and to the Moodle dirroot it lives under, both
# derived from where this Makefile itself is - never hardcoded - so this works the
# same whether the dirroot is .../admin/tool/mergeusers (Moodle up to 4.x) or
# .../public/admin/tool/mergeusers (Moodle 5.x's "public" docroot), and passed as-is
# to $(docker)/$(docker-with-xdebug): this assumes the dev container bind-mounts the
# codebase at the same absolute path as the host, which most docker-compose Moodle
# setups do.
plugin-dir := $(patsubst %/,%,$(current-dir))
moodle-dir := $(patsubst %/admin/tool/mergeusers,%,$(plugin-dir))

# vendor/ (composer deps, incl. phpunit) sits inside the dirroot on Moodle <=4.x, but
# one level above it - alongside "public/" - on Moodle 5.x's split docroot layout.
# Detected once, on the host, rather than assumed.
ifneq ($(wildcard $(moodle-dir)/vendor/bin/phpunit),)
vendor-dir := $(moodle-dir)/vendor
else
vendor-dir := $(abspath $(moodle-dir)/../vendor)
endif

# By default, only "container_name=name_of_container" is required. See below.
# This file should define at least:
# ===========================
# container_name=name_of_container
# start=command to start the whole stack (web server, database, etc) to test the plugin
# stop=command to stop th whole stack
# ===========================
# On my setup, both "start" and "stop" commands must be placed without colon nor double colons
# to work properly.
# I decided to proceed this way, since I work with custom shell scripts over the moodle-docker setup.
-include $(current-dir).env

# Redefine any of docker/docker-with-xdebug/phpcbf/moodle-plugin-ci in .env to
# override. See the comment at the top of this file for what decides docker/
# docker-with-xdebug otherwise.
ifneq ($(in-container),)
docker :=
docker-with-xdebug :=
else
ifdef container_name
docker := docker exec -it $(container_name)
docker-with-xdebug := docker exec -e XDEBUG_SESSION=1 -it $(container_name)
else
docker :=
docker-with-xdebug :=
endif
endif
ifndef phpcbf
phpcbf := $(docker) php $(moodle-dir)/local/codechecker/vendor/bin/phpcbf
endif

# Path to the locally-managed moodle-plugin-ci.phar, kept in sync with the latest
# GitHub release via ensure-moodle-plugin-ci/update-moodle-plugin-ci below. .bin/ is
# gitignored: this is downloaded, never committed.
moodle-plugin-ci-phar := $(current-dir).bin/moodle-plugin-ci.phar
ifndef moodle-plugin-ci
moodle-plugin-ci := $(docker) php $(plugin-dir)/.bin/moodle-plugin-ci.phar
endif

.PHONY: start
start:
	bash -c "$(start)"

.PHONY: stop
stop:
	bash -c "$(stop)"

# Pass XDEBUG=1 to run under xdebug instead (replaces the old pass-tests-with-xdebug
# target - both spellings still work, the latter just forwards here).
.PHONY: pass-tests
pass-tests: options =
pass-tests: docker-cmd = $(if $(XDEBUG),$(docker-with-xdebug),$(docker))
pass-tests:
	$(docker-cmd) php $(vendor-dir)/bin/phpunit -c $(plugin-dir) --testdox $(options)

.PHONY: pass-tests-with-xdebug
pass-tests-with-xdebug: options =
pass-tests-with-xdebug:
	$(MAKE) -f $(current-dir)Makefile pass-tests XDEBUG=1 options='$(options)'

.PHONY: build-phpunit-xml
build-phpunit-xml:
	$(docker)  php $(moodle-dir)/admin/tool/phpunit/cli/util.php --buildcomponentconfig

.PHONY: init-phpunit
init-phpunit:
	$(docker) php $(moodle-dir)/admin/tool/phpunit/cli/init.php

# No phpcs target: ci-codechecker below runs the same check moodle-ci.yml enforces
# in CI, via the real moodle-plugin-ci tool - it superseded this. phpcbf stays: there
# is no moodle-plugin-ci equivalent for auto-fixing.
.PHONY: phpcbf
phpcbf: options = $(plugin-dir)
phpcbf:
	$(phpcbf) $(options)

.PHONY: phpcbf-for-staged-files
phpcbf-for-staged-files:
	$(phpcbf) $$( echo $$(git diff --cached --name-only | xargs -I {} -n 1 echo '$(plugin-dir)/{}')) --ignore=tests/

# Makes sure .bin/moodle-plugin-ci.phar exists and was checked against the latest
# GitHub release today; does nothing (no network call) if it was already checked
# today, regardless of whether that check found a new version. Only needs curl/php,
# no Moodle bootstrap, so it always runs bare (no $(docker)), even when invoked from
# the host - there is nothing container-specific about downloading a phar.
.PHONY: ensure-moodle-plugin-ci
ensure-moodle-plugin-ci:
	@if [ -f "$(moodle-plugin-ci-phar)" ] && \
	    [ "$$(date -r "$(moodle-plugin-ci-phar)" +%Y-%m-%d 2>/dev/null)" = "$$(date +%Y-%m-%d)" ]; then \
		echo "moodle-plugin-ci.phar already checked today."; \
	else \
		$(MAKE) -f $(current-dir)Makefile update-moodle-plugin-ci; \
	fi

# Unconditionally checks GitHub for the latest moodle-plugin-ci release (regardless
# of when it was last checked) and downloads it if we don't already have that exact
# version. Always touches the phar afterwards, so ensure-moodle-plugin-ci won't
# check again until tomorrow. Use this directly when you need the fix in a release
# that just shipped, without waiting for the daily check.
.PHONY: update-moodle-plugin-ci
update-moodle-plugin-ci:
	@set -eu; \
	mkdir -p "$$(dirname "$(moodle-plugin-ci-phar)")"; \
	latest=$$(curl -fsSL https://api.github.com/repos/moodlehq/moodle-plugin-ci/releases/latest \
		| grep -m1 '"tag_name"' | sed -E 's/.*"tag_name": *"([^"]+)".*/\1/'); \
	if [ -z "$$latest" ]; then \
		echo "Could not determine the latest moodle-plugin-ci release." >&2; \
		exit 1; \
	fi; \
	current=""; \
	if [ -x "$(moodle-plugin-ci-phar)" ]; then \
		current=$$(php "$(moodle-plugin-ci-phar)" --version 2>/dev/null | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1); \
	fi; \
	if [ "$$current" = "$$latest" ]; then \
		echo "moodle-plugin-ci.phar is already the latest ($$latest)."; \
		touch "$(moodle-plugin-ci-phar)"; \
	else \
		echo "Downloading moodle-plugin-ci.phar $$latest (had: $${current:-none})..."; \
		curl -fsSL -o "$(moodle-plugin-ci-phar)" \
			"https://github.com/moodlehq/moodle-plugin-ci/releases/download/$$latest/moodle-plugin-ci.phar"; \
		chmod +x "$(moodle-plugin-ci-phar)"; \
		echo "Installed moodle-plugin-ci.phar $$latest."; \
	fi

# Individual moodle-plugin-ci checks, matching the steps enabled in
# .github/workflows/moodle-ci.yml (grunt/behat/release/version-bump-check are
# disabled there, so have no equivalent here). codechecker/phplint/savepoints run
# standalone; validate/mustache/phpdoc need a Moodle root, given explicitly via
# --moodle=$(moodle-dir) rather than relying on cwd.
.PHONY: ci-codechecker
ci-codechecker: ensure-moodle-plugin-ci
	$(moodle-plugin-ci) codechecker --max-warnings=0 $(plugin-dir)

.PHONY: ci-phplint
ci-phplint: ensure-moodle-plugin-ci
	$(moodle-plugin-ci) phplint $(plugin-dir)

.PHONY: ci-savepoints
ci-savepoints: ensure-moodle-plugin-ci
	$(moodle-plugin-ci) savepoints $(plugin-dir)

.PHONY: ci-validate
ci-validate: ensure-moodle-plugin-ci
	$(moodle-plugin-ci) validate --moodle=$(moodle-dir) $(plugin-dir)

.PHONY: ci-mustache
ci-mustache: ensure-moodle-plugin-ci
	$(moodle-plugin-ci) mustache --moodle=$(moodle-dir) $(plugin-dir)

.PHONY: ci-phpdoc
ci-phpdoc: ensure-moodle-plugin-ci
	$(moodle-plugin-ci) phpdoc --moodle=$(moodle-dir) $(plugin-dir)

# Runs every local-equivalent check moodle-ci.yml enables, in the same spirit as the
# GitHub Actions job. phpunit is deliberately not included here - use pass-tests for
# that, which runs $(vendor-dir)/bin/phpunit directly: moodle-plugin-ci's own phpunit
# command hardcodes "<moodle-dir>/vendor/bin/phpunit", which does not account for
# Moodle 5.x's split docroot (see $(vendor-dir) above) and would fail here.
.PHONY: ci-local
ci-local: ci-phplint ci-codechecker ci-validate ci-savepoints ci-mustache ci-phpdoc
	@echo "All local CI-equivalent checks passed."

.PHONY: purgecaches
purgecaches:
	$(docker) php $(moodle-dir)/admin/cli/purge_caches.php

.PHONY: upgrade
upgrade:
	$(docker) php $(moodle-dir)/admin/cli/upgrade.php --non-interactive

.PHONY: run-cli-merge
run-cli-merge:
	$(docker) php $(plugin-dir)/cli/climerger.php

.PHONY: list-user-fields
list-user-fields:
	$(docker) php $(plugin-dir)/cli/listuserfields.php
