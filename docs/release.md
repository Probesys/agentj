# Release a version

## Before the release

First, make sure that all the issues from the current milestone are done.
Also, see if you can fix any minor bugs or small technical debt issues first: that's always a good thing to do.

Before releasing a version, you should at least update the dependencies to their latest patch versions.

## Start the release process

There's a `make` command to release a new version of AgentJ:

```console
$ git switch -c release/2.0.0
$ make release VERSION=2.0.0
```

It will update the version number in the file [`.env.example`](/.env.example).

## Generate the changelog

Next, the command will open a text editor so you can edit the changelog.

If updating AgentJ to the new version requires specific actions, make sure they are listed in a “Migration notes” section.

Next, generate the list of messages to include in the changelog with:

```console
$ git log --first-parent --pretty=format:'- %s (%h)' --abbrev-commit $(git describe --abbrev=0 --tags)..
```

You might want to create a git alias in your `.gitconfig`.

Organize the commits in the following sections:

- Security
- New
- Bug fixes
- Technical
- Documentation
- Developers

Feel free to rename the commit messages if you think they aren't clear enough (at least to remove the prefixes).
If several commits refer to a single important item, you can merge them into a single message, and list their references in parentheses.
You can also remove messages if they don't bring value to the changelog.

Finally, add links to GitHub commits on the hashes (e.g. `[82961cb](https://github.com/Probesys/agentj/commit/82961cb)`).

## Complete the release

Once the changelog is complete, close your editor and the command will commit your changes.

Push your branch to GitHub:

```console
$ git push -u origin HEAD
```

And open a pull request.

Once merged, don't forget to push the version tag:

```console
$ git push --tags
```

## Publish on GitHub

Once the version tag is pushed, you must publish the version on GitHub:

1. start [a new release](https://github.com/Probesys/agentj/releases/new);
2. choose the tag corresponding to the new version;
3. name the release to the version tag (e.g. “AgentJ 2.0.0”);
4. copy the content of the changelog corresponding to the version;
5. you should adapt the title levels in Markdown (i.e. titles of level 3 `###` must be changed by titles of level 2 `##`);

## Fix mistakes made during the release

You may realize you made a mistake (e.g. commited a file which should not, a typo in the changelog) during the release process.
If the pull request is not merged yet, you still can fix your error.

First, reset the last commit:

```console
$ git reset HEAD^
```

Delete the tag (replace `<VERSION_NUMBER>` by the version you're releasing):

```console
$ git tag -d <VERSION_NUMBER>
$ # and if you pushed the tag on GitHub
$ git push -d origin <VERSION_NUMBER>
```

Then, fix your error, and re-run the release command:

```console
$ make release VERSION=<VERSION_NUMBER>
```

If you already pushed your commits on GitHub, force push your changes:

```console
$ git push --force-with-lease
```
