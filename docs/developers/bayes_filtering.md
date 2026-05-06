# SpamAssassin Bayes filtering

AgentJ is able to filter spam using [Bayes classifiers](https://en.wikipedia.org/wiki/Naive_Bayes_classifier#Spam_filtering).
Internally, it uses [SpamAssassin's Bayesian module](https://spamassassin.apache.org/full/3.2.x/doc/sa-learn.html).

To work, a `sa_learn` Docker volume is shared between the `amavis` and `app` containers.
In development, the folder `sa_learn/` at the root of the project is used so you can easily inspect it.

This folder contains two subfolders: `hams/` and `spams/`.
Each hour, a script in the `amavis` container reads the mails present in these both folders to learn what is a spam and what is a ham.

Mails can be put in this folder either manually, or using the dedicated Symfony command:

```console
$ ./scripts/console agentj:messages:mark-as spam [MAIL ID]
$ ./scripts/console agentj:messages:mark-as ham [MAIL ID]
```

This command also marks the mail as spam in the interface, or release it in case of ham.
