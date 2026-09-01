# Build from the repository root:
# docker build -f poc/milter/go/Dockerfile .
FROM golang:1.23-alpine AS build

WORKDIR /src
COPY poc/milter/go/go.mod poc/milter/go/go.sum ./
RUN go mod download

COPY poc/milter/go/ ./
COPY tests/milter/fixtures/ /fixtures/
RUN MILTER_FIXTURES=/fixtures go test ./...
RUN CGO_ENABLED=0 go build -trimpath -ldflags="-s -w" -o /out/agentj-milter .

FROM alpine:3.21

RUN addgroup -S -g 10001 agentj && adduser -S -D -H -u 10001 -G agentj agentj \
    && mkdir -p /var/spool/agentj-milter \
    && chown agentj:agentj /var/spool/agentj-milter
COPY --from=build /out/agentj-milter /usr/local/bin/agentj-milter

USER agentj
EXPOSE 9999
ENTRYPOINT ["/usr/local/bin/agentj-milter"]
