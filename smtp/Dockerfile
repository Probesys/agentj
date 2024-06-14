FROM debian:bookworm-slim

LABEL maintainer="Sébastien Poher <sebastien.poher@probesys.com>"
LABEL name = "Postfix incoming smtp for AgentJ"
LABEL description = "Postfix server to relay incoming mails to anti-spam stack"

SHELL [ "/bin/sh", "-e", "-c" ]

RUN --mount=type=cache,id=apt-cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,id=apt-lib,target=/var/lib/apt,sharing=locked \
    --mount=type=cache,id=debconf,target=/var/cache/debconf,sharing=locked \
    apt-get update -q --fix-missing && \
    apt-get -y install --no-install-recommends \
    postfix \ 
    postfix-mysql \ 
    postfix-policyd-spf-python \ 
    postfix-lmdb \
    supervisor  \ 
    rsyslog \ 
    tzdata  \ 
    iproute2   

RUN mkdir -p /etc/conf
COPY conf /etc/conf 
COPY entrypoint.sh /entrypoint.sh

EXPOSE 25/tcp
EXPOSE 10025/tcp

ENTRYPOINT [ "/entrypoint.sh" ]
HEALTHCHECK --start-period=60s CMD postfix status 2>/dev/null || exit 1
