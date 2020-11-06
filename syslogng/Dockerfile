FROM balabit/syslog-ng:3.29.1

LABEL maintainer = "Sébastien Poher <sebastien.poher@probesys.com>"
LABEL name = "AgentJ logs server"
LABEL description = "A central log server for AgentJ stack services based on official syslog-ng image"

COPY conf/syslog-ng.conf /etc/syslog-ng/syslog-ng.conf

VOLUME [ "/var/log/" ]
