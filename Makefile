###
# Creates and publishes a new release
#
# Defaults to building version 'v1.0.0'. If you want to create and publish
# another release, pass the 'version' environment variable.
#
# Requirements:
# - Target 'sign'
#       Requires OpenSSL and a RSA key to sign the release archive at
#       '~/.nextcloud/certificates/$(app_name).key'
# - Target 'verify'
#       Requires OpenSSL and a RSA public key to verify the release archive at
#       '~/.nextcloud/certificates/$(app_name).pub'
# - Targets 'github-release' and 'github-upload'
#       Requires https://github.com/aktau/github-release and a 'github-token'
#       file at '~/.nextcloud/github-token' with an GitHub API token, e.g.
#           deadbeefc001cafebaadf00dabad1deadeadcode
# - Target 'publish'
#       Requires a 'curlrc' file at '~/.nextcloud/curlrc' with an appropiate
#       Authentication header for the Nextcloud App Store, e.g.
#           header = "Authorization: Token [NEXTCLOUD_API_TOKEN]"
#

version?=v1.0.0
prerelease?=false
nocheck?=false
verify?=$(build_dir)/$(archive)

app_name=cms_pico
app_title=Pico CMS for Nextcloud
build_dir=$(CURDIR)/build
cert_dir=$(HOME)/.nextcloud/certificates
curlrc=$(HOME)/.nextcloud/curlrc
archive=$(app_name)-$(version).tar.gz
export=$(app_name)-export.tar.gz
signature=$(app_name)-$(version).tar.gz.sig
git_remote=origin
github_owner=nextcloud
github_repo=cms_pico
github_token:=$(shell cat "$(HOME)/.nextcloud/github-token")
download_url=https://github.com/$(github_owner)/$(github_repo)/releases/download/$(version)/$(archive)
publish_url=https://apps.nextcloud.com/api/v1/apps/releases
appinfo=./appinfo/info.xml
appinfo_version:=$(shell sed -ne 's/^.*<version>\(.*\)<\/version>.*$$/v\1/p' "$(CURDIR)/$(appinfo)")
git_local:=$(shell git status --porcelain)
git_local_head:=$(shell git rev-parse HEAD)
git_local_tag:=$(shell git rev-parse --verify "refs/tags/$(version)" 2> /dev/null)
git_remote_tag:=$(shell git ls-remote "$(git_remote)" "refs/tags/$(version)" | cut -f1 2> /dev/null)

all: build

clean:
	rm -rf "$(build_dir)"

clean-build:
	rm -rf "$(build_dir)/$(app_name)"
	rm -f "$(build_dir)/$(archive)"

clean-export:
	rm -f "$(build_dir)/$(export)"

check:
	@:
ifneq ($(appinfo_version),$(version))
	$(error Version mismatch: Building $(version), but $(appinfo) indicates $(appinfo_version))
endif
ifneq ($(git_local),)
	$(error Version mismatch: Building $(version), but working tree is not clean)
endif
ifeq ($(git_local_tag),)
	$(error Version mismatch: Building $(version), but no matching local Git tag found)
endif
ifneq ($(git_local_head),$(git_local_tag))
	$(error Version mismatch: Building $(version), but the matching Git tag is not checked out)
endif
ifeq ($(git_remote_tag),)
	$(error Version mismatch: Building $(version), but no matching remote Git tag found)
endif
ifneq ($(git_local_tag),$(git_remote_tag))
	$(error Version mismatch: Building $(version), but the matching local and remote Git tags differ)
endif

check-composer:
	composer update --no-suggest --no-dev --dry-run 2>&1 \
		| grep --quiet '^Nothing to install or update$$'

lazy-check:
	@:
ifeq ($(or $(filter $(appinfo_version) latest,$(version)), $(filter true,$(nocheck))),)
	$(error Version mismatch: Building $(version), but $(appinfo) indicates $(appinfo_version))
endif

composer:
	composer install --no-suggest --no-dev --prefer-dist --optimize-autoloader

build: lazy-check clean-build composer
	mkdir -p "$(build_dir)"
	rsync -a \
		--exclude="/.github" \
		--exclude="/.idea" \
		--exclude="/.tx" \
		--exclude="/appdata/plugins/.gitignore" \
		--exclude="/appdata/themes/.gitignore" \
		--exclude="/appdata_public/*" \
		--exclude="/build" \
		--exclude="/l10n/.gitignore" \
		--exclude="/nextcloud" \
		--exclude="/screenshots" \
		--exclude="/tests" \
		--exclude="/vendor/*/*/.git" \
		--exclude="/vendor/picocms/pico/index.php" \
		--exclude="/vendor/picocms/pico/index.php.dist" \
		--exclude="/.gitattributes" \
		--exclude="/.gitignore" \
		--exclude="/.phpcs.xml" \
		--exclude="/.scrutinizer.yml" \
		--exclude="/composer.json" \
		--exclude="/composer.lock" \
		--exclude="/Makefile" \
		--exclude="/*.phar" \
		--exclude=".git" \
		./ "$(build_dir)/$(app_name)/"
	tar cfz "$(build_dir)/$(archive)" \
		-C "$(build_dir)" "$(app_name)"

export: clean-export
	mkdir -p "$(build_dir)"
	git archive --prefix "$(app_name)/" -o "$(build_dir)/$(export)" HEAD

sign: build
	openssl dgst -sha512 \
		-sign "$(cert_dir)/$(app_name).key" \
		"$(build_dir)/$(archive)" \
			| openssl base64 -A > "$(build_dir)/$(signature)"

verify:
	openssl base64 -A -d \
		< "$(verify).sig" \
			| openssl dgst -sha512 \
				-verify "$(cert_dir)/$(app_name).pub" \
				-signature /dev/stdin \
				"$(verify)"

github-release: export GITHUB_TOKEN=$(github_token)
github-release: check
	github-release release \
		--user "$(github_owner)" \
		--repo "$(github_repo)" \
		--tag "$(version)" \
		--name "$(version)" \
		--description "$(app_title) $(version)" \
		$(if $(filter true,$(prerelease)),--pre-release,)

github-upload: export GITHUB_TOKEN=$(github_token)
github-upload: check check-composer build github-release
	github-release upload \
		--user "$(github_owner)" \
		--repo "$(github_repo)" \
		--tag "$(version)" \
		--name "$(archive)" \
		--file "$(build_dir)/$(archive)"

publish: check check-composer sign github-upload
	php -r 'echo json_encode([ "download" => $$_SERVER["argv"][1], "signature" => file_get_contents($$_SERVER["argv"][2]), "nightly" => !!$$_SERVER["argv"][3] ]);' \
		"$(download_url)" "$(build_dir)/$(signature)" "$(if $(filter true,$(prerelease)),1,0)" \
			| curl -K "$(curlrc)" \
				-H "Content-Type: application/json" -d "@-" \
				-X POST "$(publish_url)"

github-release-dev: prerelease=true
github-release-dev: github-release

github-upload-dev: prerelease=true
github-upload-dev: github-upload

publish-dev: prerelease=true
publish-dev: publish

.PHONY: all \
	clean clean-build clean-export \
	check check-composer lazy-check \
	composer build export \
	sign verify \
	github-release github-release-dev \
	github-upload github-upload-dev \
	publish publish-dev
