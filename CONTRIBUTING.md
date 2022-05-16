# Contributing Guide

This project is in early development and not all processes have been formalized yet.

Please consider these to be guidelines. If in doubt, please create an issue and tag the [maintainers](README.md#maintainers) to discuss.

## Feature Requests and Ideas

Please [create an issue](https://gitlab.com/gitlab-com/business-technology/engineering/access-manager/packages/composer/google-auth-sdk/-/issues) and describe what you'd like to see. Since this project is designed as an internal tool, we will help where we can but no guarantees.

## Code Contributions

Please create an issue first to document the purpose of the contribution from a changelog and release notes perspective. After the issue is created, create a merge request from inside the issue, then checkout the branch that was created automatically for the issue and merge request. By creating the merge request from inside the issue, everything stays connected automatically and there are no name disparities.

Due to the volume of commits in merge requests, MR comments are easy to overlook. Please have any discussions in the comments of the issue when possible.

All merge requests can be assigned to one or all of the maintainers at your discretion. It is helpful to comment in the issue when you're ready to merge with any context that the maintainer/reviewer should know or be on the look out for.

## Environment Configuration

### Configuring Your Development Environment with Working Copies of Packages

When you run `composer install`, you will get the latest copy of the packages from the GitHub and GitLab repositories. However, you won't be able to see real-time changes if you change any code in the packages.

You can mitigate this problem by creating a local symlink (with resolved namespaces) for the package inside of your application that you're using for development and testing. By symlinking the packages into the newly created `packages` directory, you'll be able to preview and test your work without doing any Git commits (bad practice).

```bash
# Pre-Requisite (you should already have this)
# You can use any directory you want (if not using ~/Sites)
cd ~/Sites
git clone https://gitlab.com/gitlab-com/business-technology/engineering/access-manager/packages/composer/google-auth-sdk.git
git clone https://gitlab.com/gitlab-com/business-technology/engineering/access-manager/gitlab-access-manager-app.git
```

```bash
cd ~/Sites/gitlab-access-manager-app
mkdir -p packages/glamstack
cd packages/glamstack
ln -s ~/Sites/google-auth-sdk google-auth-sdk
```

### Application Composer

Update the `composer.json` file in your testing application (not the package) to add the package to the `autoload.psr-4` array (append the array, don't replace anything).

```json
# ~/Sites/gitlab-access-manager-app/composer.json

"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Glamstack\\GoogleAuth\\": "packages/glamstack/google-auth-sdk/src"
    }
},
```

### Configure Local Composer Repository

Credit: <https://laravel-news.com/developing-laravel-packages-with-local-composer-dependencies>

```bash
cd ~/Sites/gitlab-access-manager-app

composer config repositories.local '{"type": "path", "url": "packages/glamstack/google-auth-sdk"}' --file composer.json

composer require glamstack/google-auth-sdk

# Package operations: 1 install, 0 updates, 0 removals
#  - Installing glamstack/google-auth-sdk (dev-1-add-package-scaffolding): Symlinking from packages/glamstack/google-auth-sdk
```

### Caching Problems

If you run into any classes or files that are renamed and are throwing `Not Found` errors, you may need to use the `composer dump-autoload` command.
