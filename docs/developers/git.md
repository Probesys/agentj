# Git tips

## Managing version branches

For each minor version of AgentJ, we create a dedicated Git "patch" branch (e.g. [branch `2.1`](https://github.com/Probesys/agentj/tree/2.1)).
This allows to release patch versions while working on major features on the `main` branch.

Generally speaking, if you work on a new feature, start your branch from `main`.
If you work on a bugfix, you should start your branch from the latest patch branch.
Some maintenance tasks may also start from the patch branch if it can be helpful.
Pull requests must target the corresponding branch.

Maintainers must synchronize as often as possible the `main` branch with the latest patch branch by creating a branch from `main` and merge the patch branch in it.
Then, follow the normal workflow by opening a pull request.
