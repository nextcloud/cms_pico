> :exclamation: :exclamation: **END OF LIFE NOTICE** :exclamation: :exclamation:
>
> Development of Pico CMS for Nextcloud has stopped a very long time ago alongside with [Pico](http://picocms.org/). **We strongly advise against using Pico CMS for Nextcloud.** This Nextcloud app isn't compatible with any still supported Nextcloud version, and Pico itself wasn't designed for modern PHP versions. However, please note that there are no known security issues.
>
> For a very limited number of trusted users, you might want to check out some of Pico's amazing alternatives, like [Grav CMS](https://getgrav.org/), [HTMLy](https://www.htmly.com/), [Automad](https://automad.org/), or [Typemill](https://typemill.net/). You could install these alongside with Nextcloud. If you're an experienced server administrator and/or developer, and know what you're doing, you could possibly even mount the CMS' content directory as [external storage](https://docs.nextcloud.com/server/latest/admin_manual/configuration_files/external_storage_configuration_gui.html) of your Nextcloud. However, be **very careful** not to introduce catastrophic security issues by allowing users to modify active contents and thus execute arbitrary code on your server. Unfortunately we're not aware of any alternative to Pico CMS for Nextcloud.
>
> If you're interested in taking over Pico's development (and in a possible second step also that of Pico CMS for Nextcloud), please don't hesitate to contact us by creating a [new Issue](https://github.com/picocms/Pico/issues/new) on Pico's GitHub repository. Please provide some *brief* information about the extent of your commitment, your motivation, and your experience with Pico, Nextcloud, PHP programming, and Open Source Software development in general. We're happy to help you take over the baton, but unfortunately are no longer able to maintain this project.
>
> :exclamation: :exclamation: **END OF LIFE NOTICE** :exclamation: :exclamation:

# Pico CMS for Nextcloud

[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![Latest Release](https://img.shields.io/github/v/release/nextcloud/cms_pico?sort=semver)](https://apps.nextcloud.com/apps/cms_pico)
[![Build Status](https://img.shields.io/github/checks-status/nextcloud/cms_pico/master?label=build)](https://github.com/nextcloud/cms_pico/actions/workflows/test.yml)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nextcloud/cms_pico/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/nextcloud/cms_pico/?branch=master)

## About

[Pico CMS for Nextcloud](https://apps.nextcloud.com/apps/cms_pico) combines the power of [**Pico**](http://picocms.org/) and [**Nextcloud**](https://nextcloud.com/) to create simple, secure, shareable and amazingly powerful websites with just a few clicks. Pico is a stupidly simple, blazing fast, flat file CMS - making the web easy!

* :notebook: Start a blog
* :rocket: Share your resume with the world
* :smiling_imp: Create a plan for world domination and only share with the right friends
* :neckbeard: Build a knowledge base and let the smart ones among your colleagues help out

Installing Pico CMS for Nextcloud allows your users to create and manage their own websites. Creating a new page with Pico is no more than creating a simple text file in a users' Nextcloud files. No config is required, no utterly complex management interfaces - just files. It's the perfect match with Nextcloud. Secure Sharing, Collaboration, Access Control - not just for your files, but also your websites, all made possible by Pico CMS for Nextcloud! Breaking the boundaries between your Mobile & Desktop devices and your Server.

Websites will be accessible through URLs like https://cloud.example.com/sites/my_site/ and consist of just a bunch of `.md` text files in a user's Nextcloud files. `.md` stands for [**Markdown**](https://www.markdownguide.org/) - a super simple and intuitive markup to create headings, paragraphs, text formatting, lists, images and links. But don't despair - you don't have to learn yet another language if you don't want to. Consider enabling Nextcloud's [Markdown Editor](https://apps.nextcloud.com/apps/files_markdown) app to make easy things stupidly simple. Please note that Nextcloud's built-in Text editor is incompatible with Pico CMS for Nextcloud. But what about meta data like a page's title or release date? Guess right, it's all in one place. At the top of your Markdown files you can place a block with such meta data - called the [**YAML**](https://en.wikipedia.org/wiki/YAML) Front Matter. Creating websites can't be easier…

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

## Known limitations

### HTML in Markdown files

One of Markdown's key features is that users can use arbitrary HTML in their Markdown files to enable more advanced contents. However, since all websites of Pico CMS for Nextcloud run under the same domain as Nextcloud, this bears a huge security risk: Users with some knowledge could attack other users of your Nextcloud, including you, the Nextcloud admin (so called "Cross Scripting"). Pico CMS for Nextcloud follows a "Better safe than sorry" mentality, thus we let [HTMLPurifier](http://htmlpurifier.org/) remove any potentially active content from Markdown files.

For this reason you cannot use HTML features like `<iframe>`, `<audio>`, `<video>` and `<script>` in your Markdown files - on purpose! These limitations don't apply to themes, so if you know what you're doing, you can create a custom theme to include any advanced features you need (for example a video player). However, please be careful not to introduce security risks!

### Nextcloud's Text App

Nextcloud's official [Text](https://apps.nextcloud.com/apps/text) app is incompatible with Pico CMS for Nextcloud, as is destroys otherwise valid Markdown files (it e.g. removes YAML Front Matters). Unfortunately we cannot do anything about this, it's a rather complex issue in the realm of the Text app. Please see [#116](https://github.com/nextcloud/cms_pico/issues/116) for more info.

In the meantime we recommend using Nextcloud's [Markdown editor](https://apps.nextcloud.com/apps/files_markdown) app or the [Plain text editor](https://apps.nextcloud.com/apps/files_texteditor) app. Please note that Nextcloud's Text app will still interfere with your Nextcloud install (also see [App behaviors](https://github.com/icewind1991/files_markdown#behaviors)), thus we recommend you to disable the Text app altogether.

### App incompatibilities

Due to how Nextcloud and most other PHP applications handle dependencies, there's a huge potential of dependency conflicts. Due to this some Nextcloud apps have known incompatibilities with Pico CMS for Nextcloud. This is no-one's fault, neither are Nextcloud nor the conflicting apps to blame, this is just some technical limitation of Nextcloud's app infrastructure we cannot solve in the short term. Please see [#97](https://github.com/nextcloud/cms_pico/issues/97) for more info.

In the meantime you must remove all conflicting apps. Known conflicting apps are [Issue Template](https://apps.nextcloud.com/apps/issuetemplate) and [Terms of service](https://apps.nextcloud.com/apps/terms_of_service). If you see the error `"Call to undefined method ParsedownExtra::textElements()"` in Nextcloud's log even though you\'ve removed all conflicting apps, please don't hesitate to [open a new Issue on GitHub](https://github.com/nextcloud/cms_pico/issues/new) with a copy of the error including its stack trace and a complete list of all apps installed.

## Getting help

Something went wrong? You need help? No worries, we will help!

If you want to get started using Pico, please refer to [Pico's user docs](http://picocms.org/docs/). You can find officially supported [plugins](http://picocms.org/plugins/) and [themes](http://picocms.org/themes/) on Pico's website. A greater choice of third-party plugins and themes can be found in [Pico's wiki](https://github.com/picocms/Pico/wiki) on the [plugins](https://github.com/picocms/Pico/wiki/Pico-Plugins) or [themes](https://github.com/picocms/Pico/wiki/Pico-Themes) pages respectively. If you want to create your own plugin or theme, please refer to the [“Getting Help as a developer” section of Pico's docs](http://picocms.org/docs/#getting-help-as-a-developer).

When the docs cannot answer your question, you can get help by either joining us on [#picocms on Libera.Chat](https://web.libera.chat/#picocms) ([logs](http://picocms.org/irc-logs)), or by creating a new thread on [Nextcloud Help](https://help.nextcloud.com/c/apps/cms-pico). When you’re experiencing problems with Pico CMS for Nextcloud, please don’t hesitate to create a new [Issue](https://github.com/nextcloud/cms_pico/issues) on GitHub. Concerning problems with Pico, open a new [Issue](https://github.com/picocms/Pico/issues) on Pico's GitHub repository. If you have problems with plugins or themes, please refer to the website of the developer of this plugin or theme.

**Before creating a new Issue,** please make sure the problem wasn’t reported yet using GitHubs search engine on both the [`nextcloud/cms_pico`](https://github.com/nextcloud/cms_pico/search?type=Issues) and [`picocms/Pico`](https://github.com/picocms/Pico/search?type=Issues) repos, as well as the [search of Nextcloud Help](https://help.nextcloud.com/search). Please describe your issue as clear as possible and always include the *exact error message* (if any) as well as all related messages in Nextcloud's logs. Also include the exact *Nextcloud version* and the *version of Pico CMS for Nextcloud* you’re using. Provided that you’re using custom *plugins* and/or *themes*, include a list of them too. We need information about the *actual and expected behavior* , the *steps to reproduce* the problem, and what steps you have taken to resolve the problem by yourself (i.e. *your own troubleshooting*).

## Nextcloud 26+ compatibility
:exclamation: I've made this app compatible with Nextcloud 31, however, for now, the HTML purifier is bypassed which might be an issue depending of your user case.
Also, as this project is using other unmaintained projects, it is very possible that things might break again in the future.
I will do my best to keep it up and running until my users have agreed to move to another platform.
Also, keep in mind that my PHP knowledge and experience is limited, so, for now it works on my current Nextcloud instance, but it might not with your configuration.
