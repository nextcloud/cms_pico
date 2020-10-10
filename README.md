# Pico CMS for Nextcloud

[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![Latest Release](https://img.shields.io/github/v/release/nextcloud/cms_pico?sort=semver)](https://apps.nextcloud.com/apps/cms_pico)
[![Build Status](https://scrutinizer-ci.com/g/nextcloud/cms_pico/badges/build.png?b=master)](https://scrutinizer-ci.com/g/nextcloud/cms_pico/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nextcloud/cms_pico/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/nextcloud/cms_pico/?branch=master)

## About

[Pico CMS for Nextcloud](https://apps.nextcloud.com/apps/cms_pico) combines the power of [**Pico**](http://picocms.org/) and [**Nextcloud**](https://nextcloud.com/) to create simple, secure, shareable and amazingly powerful websites with just a few clicks. Pico is a stupidly simple, blazing fast, flat file CMS - making the web easy!

* :notebook: Start a blog
* :rocket: Share your resume with the world
* :smiling_imp: Create a plan for world domination and only share with the right friends
* :neckbeard: Build a knowledge base and let the smart ones among your colleagues help out

Installing Pico CMS for Nextcloud allows your users to create and manage their own websites. Creating a new page with Pico is no more than creating a simple text file in a users' Nextcloud files. No config is required, no utterly complex management interfaces - just files. It's the perfect match with Nextcloud. Secure Sharing, Collaboration, Access Control - not just for your files, but also your websites, all made possible by Pico CMS for Nextcloud! Breaking the boundaries between your Mobile & Desktop devices and your Server.

Websites will be accessible through URLs like https://cloud.example.com/sites/my_site/ and consist of just a bunch of `.md` text files in a user's Nextcloud files. `.md` stands for [**Markdown**](https://www.markdownguide.org/) - a super simple and intuitive markup to create headings, paragraphs, text formatting, lists, images and links. But don't despair - you don't have to learn yet another language if you don't want to. Consider enabling Nextcloud's [Text](https://apps.nextcloud.com/apps/text) or [Markdown Editor](https://apps.nextcloud.com/apps/files_markdown) apps to make easy things stupidly simple. But what about meta data like a page's title or release date? Guess right, it's all in one place. At the top of your Markdown files you can place a block with such meta data - called the [**YAML**](https://en.wikipedia.org/wiki/YAML) Front Matter. Creating websites can't be easier…

But that wasn't everything… Pico CMS for Nextcloud is highly customizable. You can change Pico’s appearance by using custom themes and add new functionality by using custom plugins. For security reasons users can neither add custom themes nor plugins on their own - but as an admin you can. Plugins and themes aren’t just new "skins" or "widgets", the underlying technologies are powerful frameworks you can leverage to make your users' websites truly unique. However, with great power comes great responsibility. Pico CMS for Nextcloud does its best to prevent users from including scripts into websites, since this might bear security risks (so called "Cross Scripting"). Since this risk doesn't apply to Pico itself, 3rd-party developers of plugins and themes might not be aware of this issue - so be careful when installing custom plugins and themes.

You want to learn more about Pico CMS for Nextcloud? Easy! Just download and enable the app from [Nextcloud's App Store](https://apps.nextcloud.com/apps/cms_pico) and navigate to Nextcloud's settings page. As an admin you'll find two "Pico CMS" sections in your Nextcloud settings - one below "Personal", another below "Administration". The latter allows you to add custom themes, plugins and templates to Pico, as well as tweaking some advanced settings. The "Pico CMS" section below "Personal" exists for all Nextcloud users and allows one to create personal websites. Simply create your first personal website and choose "sample_pico" as website template. Pico's sample contents will explain all you need to know… :wave:

## Installation

### App Store

Pico CMS for Nextcloud can be found in [Nextcloud's App Store](https://apps.nextcloud.com/apps/cms_pico). Installing the app using the app store is super easy: Simply navigate to the Apps management page of your Nextcloud and either search for "Pico CMS" or check the "Tools" section to find Pico CMS for Nextcloud. Hit the "Download and enable" button and you're ready to go!

### Manually

1. Open a shell and navigate to Nextcloud's install directory (e.g. `/var/www/html/nextcloud`). Clone Pico CMS for Nextcloud's Git repository to your `apps/cms_pico/` directory:
   ```
   $ git clone https://github.com/nextcloud/cms_pico.git apps/cms_pico
   ```

2. Run `composer install` to install the app's dependencies. If you haven't installed [Composer](https://getcomposer.org/) yet, you must download it first.
   ```
   $ cd apps/cms_pico/
   $ curl -sSL https://getcomposer.org/installer | php
   $ php composer.phar install
   ```

3. Make sure that your webserver has write permissions on the app's `appdata_public/` directory. You can ensure this by matching the permissions (owner, group and permissions) of Nextcloud's `data/` directory:
   ```
   $ chown --reference=../../data/ appdata_public
   $ chmod --reference=../../data/ appdata_public
   ```

## Getting help

Something went wrong? You need help? No worries, we will help!

If you want to get started using Pico, please refer to [Pico's user docs](http://picocms.org/docs/). You can find officially supported [plugins](http://picocms.org/plugins/) and [themes](http://picocms.org/themes/) on Pico's website. A greater choice of third-party plugins and themes can be found in [Pico's wiki](https://github.com/picocms/Pico/wiki) on the [plugins](https://github.com/picocms/Pico/wiki/Pico-Plugins) or [themes](https://github.com/picocms/Pico/wiki/Pico-Themes) pages respectively. If you want to create your own plugin or theme, please refer to the [“Getting Help as a developer” section of Pico's docs](http://picocms.org/docs/#getting-help-as-a-developer).

When the docs can’t answer your question, you can get help by either joining us on [#picocms on Freenode IRC](https://webchat.freenode.net/?channels=%23picocms) ([logs](http://picocms.org/irc-logs)), or by creating a new thread on [Nextcloud Help](https://help.nextcloud.com/c/apps/cms-pico). When you’re experiencing problems with Pico CMS for Nextcloud, please don’t hesitate to create a new [Issue](https://github.com/nextcloud/cms_pico/issues) on GitHub. Concerning problems with Pico, open a new [Issue](https://github.com/picocms/Pico/issues) on Pico's GitHub repository. If you have problems with plugins or themes, please refer to the website of the developer of this plugin or theme.

**Before creating a new Issue,** please make sure the problem wasn’t reported yet using GitHubs search engine on both the [`nextcloud/cms_pico`](https://github.com/nextcloud/cms_pico/search?type=Issues) and [`picocms/Pico`](https://github.com/picocms/Pico/search?type=Issues) repos, as well as the [search of Nextcloud Help](https://help.nextcloud.com/search). Please describe your issue as clear as possible and always include the *version of Pico CMS for Nextcloud* you’re using. Provided that you’re using custom *plugins* and/or *themes*, include a list of them too. We need information about the *actual and expected behavior* , the *steps to reproduce* the problem, and what steps you have taken to resolve the problem by yourself (i.e. *your own troubleshooting*).
