FROM debian:trixie-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends opendkim opendkim-tools iproute2 \
    && rm -rf /var/lib/apt/lists/*

RUN install -d -o root -g root -m 0755 /etc/opendkim-final \
    && install -d -o opendkim -g opendkim -m 0750 /etc/opendkim-final/keys

COPY --chmod=0444 opendkim.conf KeyTable SigningTable /etc/opendkim-final/
COPY --chown=opendkim:opendkim --chmod=0400 keys/final.private /etc/opendkim-final/keys/final.private

EXPOSE 8892
ENTRYPOINT ["/usr/sbin/opendkim"]
CMD ["-f", "-x", "/etc/opendkim-final/opendkim.conf"]
