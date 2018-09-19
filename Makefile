app_name=cms_pico

project_dir=$(CURDIR)/../$(app_name)
build_dir=$(CURDIR)/build/artifacts
appstore_dir=$(build_dir)/appstore
source_dir=$(build_dir)/source
sign_dir=$(build_dir)/sign
package_name=$(app_name)
cert_dir=$(HOME)/.owncloud/certificates
codecov_token_dir=$(HOME)/.nextcloud/codecov_token
version+=0.9.7
appstore_package_name=$(sign_dir)/$(app_name)

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


all: appstore

#
# Basic required tools
#
$(COMPOSER_BIN):
	mkdir -p $(build_dir)
	cd $(build_dir) && curl -sS https://getcomposer.org/installer | php


release: appstore create-tag

create-tag:
	git tag -s -a v$(version) -m "Tagging the $(version) release."
	git push origin v$(version)

clean:
	rm -rf $(build_dir)
	rm -rf node_modules

composer: $(COMPOSER_BIN)
	php $(COMPOSER_BIN) install

test: SHELL:=/bin/bash
test: composer
	phpunit --coverage-clover=coverage.xml --configuration=tests/phpunit.xml tests
	@if [ -f $(codecov_token_dir)/$(app_name) ]; then \
		bash <(curl -s https://codecov.io/bash) -t @$(codecov_token_dir)/$(app_name) ; \
	fi


appstore: composer clean
	mkdir -p $(sign_dir)
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
	--exclude=/Makefile \
	--exclude=/vendor/picocms/pico/index.php \
	$(project_dir)/ $(sign_dir)/$(app_name)

    ifdef CAN_SIGN
	   $(sign) --path="$(appstore_package_name)"
    else
	    @echo $(sign_skip_msg)
    endif
	tar -czf $(appstore_package_name).tar.gz -C $(appstore_package_name)/../ $(app_name)
