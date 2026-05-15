# SpamAssassin Bayes filtering

AgentJ is able to filter spam using [Bayes classifiers](https://en.wikipedia.org/wiki/Naive_Bayes_classifier#Spam_filtering).
Internally, it uses [SpamAssassin's Bayesian module](https://spamassassin.apache.org/full/3.2.x/doc/sa-learn.html).

To work, a `sa_learn` Docker volume is shared between the `amavis` and `app` containers.
In development, the folder `sa_learn/` at the root of the project is used so you can easily inspect it.

This folder contains two subfolders: `hams/` and `spams/`.
Each hour, a script in the `amavis` container reads the mails present in these both folders to learn what is a spam and what is a ham.

Mails can be marked as spam/ham with different methods:

1. The recommended method is to use the web interface as an admin to mark the mails;
2. Alternatively, a console command can be used:
    ```console
    $ ./scripts/console agentj:messages:mark-as spam [MAIL ID]
    $ ./scripts/console agentj:messages:mark-as ham [MAIL ID]
    ```
3. To setup the system the first time, you may want to put spams and hams in the folders manually.

When marking a mail as spam using methods 1 and 2, it also moves any untreated mail to spams.
In case of marking a spam as ham using methods 1 and 2, the mail is automatically released.
