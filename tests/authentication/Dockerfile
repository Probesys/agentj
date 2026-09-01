FROM python:3.13-slim

RUN pip install --no-cache-dir \
        'dkimpy[ARC]==1.1.8' \
        'dnspython==2.7.0'

COPY verify.py /usr/local/bin/verify-authentication

ENTRYPOINT ["python", "/usr/local/bin/verify-authentication"]
