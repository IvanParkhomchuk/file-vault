<?php

namespace App\Services;

use App\Models\StoredFile;
use Closure;
use DateTimeInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class RabbitMqDeletionNotificationPublisher implements DeletionNotificationPublisher
{
    public function __construct(private readonly ?Closure $connectionFactory = null) {}

    public function publish(StoredFile $file, string $source, DateTimeInterface $deletedAt): void
    {
        $settings = config('services.rabbitmq');
        $queue = $settings['queue'] ?? '';
        $recipient = $settings['recipient_email'] ?? '';

        if (! in_array($source, ['manual', 'expiration'], true)) {
            throw new DeletionNotificationPublicationException('Invalid file deletion source.');
        }

        if ($queue === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new DeletionNotificationPublicationException('RabbitMQ deletion queue and notification recipient email must be configured.');
        }

        $message = new AMQPMessage(json_encode([
            'type' => 'file.deleted',
            'version' => 1,
            'recipient_email' => $recipient,
            'file_id' => $file->getKey(),
            'original_name' => $file->original_name,
            'deleted_at' => $deletedAt->format(DateTimeInterface::ATOM),
            'deletion_source' => $source,
        ], JSON_THROW_ON_ERROR), [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        try {
            $connection = $this->connectionFactory
                ? ($this->connectionFactory)($settings)
                : new AMQPStreamConnection(
                    $settings['host'],
                    $settings['port'],
                    $settings['user'],
                    $settings['password'],
                    $settings['vhost'],
                );

            $channel = $connection->channel();
            $channel->queue_declare($queue, false, true, false, false);
            $channel->confirm_select();
            $channel->set_return_listener(function () {
                throw new DeletionNotificationPublicationException('RabbitMQ returned an unroutable deletion notification.');
            });
            $channel->set_nack_handler(function () {
                throw new DeletionNotificationPublicationException('RabbitMQ rejected a deletion notification.');
            });
            $channel->basic_publish($message, '', $queue, true);
            $channel->wait_for_pending_acks_returns(5);
            $channel->close();
            $connection->close();
        } catch (Throwable $exception) {
            throw new DeletionNotificationPublicationException(
                'Failed to publish file deletion notification to RabbitMQ.',
                previous: $exception,
            );
        }
    }
}
