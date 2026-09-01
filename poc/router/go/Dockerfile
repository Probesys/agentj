FROM golang:1.23-alpine AS build

WORKDIR /src
COPY go.mod go.sum ./
RUN go mod download
COPY . ./
RUN go test ./...
RUN CGO_ENABLED=0 go build -trimpath -ldflags="-s -w" -o /out/agentj-router .

FROM alpine:3.21

RUN addgroup -S -g 65532 agentj && adduser -S -D -H -u 65532 -G agentj agentj \
    && mkdir -p /tmp/agentj-router \
    && chown agentj:agentj /tmp/agentj-router
COPY --from=build /out/agentj-router /usr/local/bin/agentj-router

USER agentj
EXPOSE 2525
ENTRYPOINT ["/usr/local/bin/agentj-router"]
