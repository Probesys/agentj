FROM debian:bookworm-slim

RUN apt update
RUN apt install -y python3-pip python3-mysqldb
RUN pip3 install --break-system-packages policyd-rate-limit
COPY policyd-rate-limit.yaml /etc/policyd-rate-limit.yaml

EXPOSE 8552/tcp

RUN mkdir -p /var/run/policyd-rate-limit
RUN useradd -m policyd-rate-limit
RUN chown policyd-rate-limit /var/run/policyd-rate-limit
USER policyd-rate-limit
CMD policyd-rate-limit
