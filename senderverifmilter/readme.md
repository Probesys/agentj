
# senderverifmilter

custom postfix [milter](https://www.postfix.org/MILTER_README.html)

thanks to [nitmir's policyd-rate-limit](https://github.com/nitmir/policyd-rate-limit), also used [in this project](../policyd-rate-limit), for the milter implementation

## brief description

`db.py`         db connexion information
`main.py`       daemon/socket managment
`handler.py`    read and answer to milter messages

The goal of this milter is this SQL request (in `handler.py`) :

```sql
select 'OK' from domain a
join domain_relay b on a.id = b.domain_id
join users c on a.id = c.domain_id
where b.ip_address = `<current_client_ip_address>` and c.email = `<current_mail_from>`
```

This is a protection for AgentJ instance with multiple domains, to ensure an user of domain A can't send mail as domain B.
