FROM alpine:latest AS base
RUN apk update && apk add --no-cache wget
RUN wget -P /opt https://github.com/rabbitmq/rabbitmq-delayed-message-exchange/releases/download/v3.8.0/rabbitmq_delayed_message_exchange-3.8.0.ez

FROM rabbitmq:3.8.7-management-alpine AS prod
COPY --from=base /opt/rabbitmq_delayed_message_exchange-3.8.0.ez $RABBITMQ_HOME/plugins/rabbitmq_delayed_message_exchange-3.8.0.ez
RUN rabbitmq-plugins enable --offline rabbitmq_delayed_message_exchange
RUN rabbitmq-plugins enable --offline rabbitmq_consistent_hash_exchange
