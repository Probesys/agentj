FROM debian:bookworm-slim

SHELL [ "/bin/sh", "-e", "-c" ]

RUN --mount=type=cache,id=apt-cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,id=apt-lib,target=/var/lib/apt,sharing=locked \
    --mount=type=cache,id=debconf,target=/var/cache/debconf,sharing=locked \
    apt-get update -q --fix-missing && \
    apt-get -y install --no-install-recommends \
    postfix \ 
    postfix-mysql \ 
    postfix-lmdb \
    tzdata  \ 
    iproute2   

COPY in /etc/postfix-in
COPY out /etc/postfix-out
COPY common /etc/postfix-common
COPY entrypoint.sh /entrypoint.sh

EXPOSE 25/tcp

ENTRYPOINT [ "/entrypoint.sh" ]
# use default configuration instead of our own to prevent postfix status to cleverly and annoyingly output
# its status check to the same output as the others daemons, which ends in our container log
HEALTHCHECK --start-period=60s CMD MAIL_CONFIG=/etc/postfix postfix status || exit 1
