FROM python:3-slim

ARG UID=1001
ARG GID=1001

RUN groupadd -g $GID milter
RUN useradd -u $UID -g $GID -md /app milter
USER milter
COPY *.py requirements.txt /app/
WORKDIR /app
RUN pip install --user -r requirements.txt
CMD ["python", "-u", "main.py"]
