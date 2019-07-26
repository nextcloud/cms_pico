app_name=cms_pico

project_dir=$(CURDIR)/../$(app_name)
build_dir=$(CURDIR)/build/artifacts
appstore_dir=$(build_dir)/appstore
package_name=$(app_name)
cert_dir=$(HOME)/.owncloud/certificates
version+=0.9.8
codecov_token_dir=$(HOME)/.owncloud/codecov_token
github_account=VicDeo
branch=master

appstore_package_name=$(appstore_dir)/$(app_name)

COMPOSER_BIN=$(build_dir)/composer.phar

#
# Signing
#
occ=$(CURDIR)/../../occ
private_key=$(cert_dir)/$(app_name).key
certificate=$(cert_dir)/$(app_name).crt
sign=php -f $(occ) integrity:sign-app --privateKey="$(private_key)" --certificate="$(certificate)"
sign_skip_msg="Skipping signing, either no key and certificate found in $(private_key) and $(certificate) or occ can not be found at $(occ)"

ifneq (,$(wildcard $(private_key)))
ifneq (,$(wildcard $(certificate)))
ifneq (,$(wildcard $(occ)))
	CAN_SIGN=true
endif
endif
endif


all: appstore github-release github-upload

#
# Basic required tools
#
$(COMPOSER_BIN):
	mkdir -p $(build_dir)
	cd $(build_dir) && curl -sS https://getcomposer.org/installer | php


release: appstore create-tag

github-release:
	github-release release \
		--user $(github_account) \
		--repo $(app_name) \
		--target $(branch) \
		--tag v$(version) \
		--name "$(app_name) v$(version)"

github-upload:
	github-release upload \
		--user $(github_account) \
		--repo $(app_name) \
		--tag v$(version) \
		--name "$(app_name)-$(version).tar.gz" \
		--file $(build_dir)/$(app_name)-$(version).tar.gz

composer: $(COMPOSER_BIN)
	cd $(project_dir) && php $(COMPOSER_BIN) install

test: SHELL:=/bin/bash
test: composer
	phpunit --coverage-clover=coverage.xml --configuration=tests/phpunit.xml tests

clean:
	rm -rf $(build_dir)
	rm -rf node_modules
	
appstore: clean composer
	mkdir -p $(appstore_dir)
	rsync -a \
	--exclude=/build \
	--exclude=/docs \
	--exclude=/translationfiles \
	--exclude=/tests \
	--exclude=/.tx \
	--exclude=/.git \
	--exclude=/.github \
	--exclude=/composer.json \
	--exclude=/composer.lock \
	--exclude=/l10n/l10n.pl \
	--exclude=/CONTRIBUTING.md \
	--exclude=/issue_template.md \
	--exclude=/README.md \
	--exclude=/.gitattributes \
	--exclude=.gitignore \
	--exclude=/.scrutinizer.yml \
	--exclude=/.travis.yml \
	--exclude=/.drone.yml \
	--exclude=/Makefile \
	--exclude=/vendor/picocms/pico/index.php \
	$(project_dir)/ $(appstore_package_name)

    ifdef CAN_SIGN
	   $(sign) --path="$(appstore_package_name)"
    else
	    @echo $(sign_skip_msg)
    endif
	tar --format=gnu -czf $(appstore_package_name).tar.gz -C $(appstore_package_name)/../ $(app_name)

