FROM gitlab.probesys.com:5050/agentj/policyd-rate-limit
COPY policyd-rate-limit.yaml .config/policyd-rate-limit.yaml
COPY entrypoint.sh /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
CMD policyd-rate-limit & while true ; do sleep 86400 ; policyd-rate-limit --clean ; done