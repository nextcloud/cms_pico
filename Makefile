app_name=cms_pico
version?=1.0.0
prerelease?=false

build_dir=$(CURDIR)/build
cert_dir=$(HOME)/.nextcloud/certificates
curlrc=$(HOME)/.nextcloud/curlrc
archive=$(app_name)-v$(version).tar.gz
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
	composer install --no-dev --prefer-dist --optimize-autoloader

build: clean composer
	mkdir -p "$(build_dir)"
	rsync -a \
		--exclude="/build" \
		--exclude="/tests" \
		--exclude="/vendor/picocms/pico/index.php" \
		--exclude="/vendor/picocms/pico/index.php.dist" \
		--exclude="/.tx" \
		--exclude="/composer.json" \
		--exclude="/composer.lock" \
		--exclude="/Makefile" \
		--exclude="/README.md" \
		--exclude="/.drone.yml" \
		--exclude="/.phpcs.xml" \
		--exclude="/.scrutinizer.yml" \
		--exclude=".git" \
		--exclude=".github" \
		--exclude=".gitattributes" \
		--exclude=".gitignore" \
		./ "$(build_dir)/$(app_name)/"
	tar czf "$(build_dir)/$(archive)" \
		-C "$(build_dir)" "$(app_name)"

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
		--name "$(archive)"
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
