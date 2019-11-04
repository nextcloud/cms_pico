###
# Creates and publishes a new release
#
# Defaults to version '1.0.0'. If you want to create and publish another
# release, pass the 'version' environment variable.
#
# Requirements:
# - Target 'export'
#       Requires the current working dir to be a Git repo to export the repo's
#       current 'HEAD'
# - Target 'sign'
#       Requires OpenSSL and a RSA key for signing the release archive at
#       '~/.nextcloud/certificates/$(app_name).key'
# - Targets 'github-release' and 'github-upload'
#       Requires https://github.com/aktau/github-release and the 'GITHUB_TOKEN'
#       environment variable to be set to your GitHub API token
# - Target 'publish'
#       Requires a 'curlrc' file at '~/.nextcloud/curlrc' with an appropiate
#       Authentication header for the Nextcloud App Store, e.g.
#           header = "Authorization: Token [NEXTCLOUD_API_TOKEN]"
#

app_name=cms_pico
version?=1.0.0
prerelease?=false

build_dir=$(CURDIR)/build
cert_dir=$(HOME)/.nextcloud/certificates
curlrc=$(HOME)/.nextcloud/curlrc
archive=$(app_name)-v$(version).tar.gz
export=$(app_name)-export.tar.gz
signature=$(app_name)-v$(version).tar.gz.sig
github_owner=nextcloud
github_repo=cms_pico
github_branch=master
download_url=https://github.com/$(github_owner)/$(github_repo)/releases/download/v$(version)/$(archive)
publish_url=https://apps.nextcloud.com/api/v1/apps/releases

all: build

clean:
	rm -rf "$(build_dir)"

composer:
	composer install --no-suggest --no-dev --prefer-dist --optimize-autoloader

build: clean composer
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

export:
	mkdir -p "$(build_dir)"
	git archive --prefix "$(app_name)/" -o "$(build_dir)/$(export)" HEAD

sign: build
	openssl dgst -sha512 \
		-sign "$(cert_dir)/$(app_name).key" \
		"$(build_dir)/$(archive)" \
			| openssl base64 -A > "$(build_dir)/$(signature)"

github-release:
	github-release release \
		--user "$(github_owner)" \
		--repo "$(github_repo)" \
		--tag "v$(version)" \
		--target "$(github_branch)" \
		--name "Pico CMS for Nextcloud v$(version)" \
		--description "Pico CMS for Nextcloud v$(version)" \
		$(if $(findstring true,$(prerelease)),--pre-release,)

github-upload: build github-release
	github-release upload \
		--user "$(github_owner)" \
		--repo "$(github_repo)" \
		--tag "v$(version)" \
		--name "$(archive)" \
		--file "$(build_dir)/$(archive)"

publish: sign github-upload
	php -r 'echo json_encode([ "download" => $$_SERVER["argv"][1], "signature" => file_get_contents($$_SERVER["argv"][2]), "nightly" => !!$$_SERVER["argv"][3] ]);' "" \
		"$(download_url)" "$(build_dir)/$(signature)" "$(if $(findstring true,$(prerelease)),1,0)" \
			| curl -K "$(curlrc)" \
				-H "Content-Type: application/json" -d "@-" \
				-X POST "$(publish_url)"

github-release-dev: prerelease=true
github-release-dev: github-release

github-upload-dev: prerelease=true
github-upload-dev: github-upload

publish-dev: prerelease=true
publish-dev: publish
