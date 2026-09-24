<?php

namespace Tests\Unit;

use App\Models\StoredFile;
use App\Services\DeletionNotificationPublicationException;
use App\Services\DeletionNotificationPublisher;
use App\Services\RabbitMqDeletionNotificationPublisher;
use DateTimeImmutable;
use Mockery;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Tests\TestCase;

class RabbitMqDeletionNotificationPublisherTest extends TestCase
{
    public function test_binding_and_configured_queue_recipient_and_message_contract(): void
    {
        config()->set('services.rabbitmq', [
            'host' => 'broker.internal',
            'port' => 5673,
            'user' => 'publisher',
            'password' => 'secret',
            'vhost' => '/files',
            'queue' => 'deleted_documents',
            'recipient_email' => 'notices@example.test',
        ]);

        $this->assertInstanceOf(RabbitMqDeletionNotificationPublisher::class, app(DeletionNotificationPublisher::class));

        $file = new StoredFile(['original_name' => 'report.pdf']);
        $file->id = 42;
        $channel = Mockery::mock(AMQPChannel::class);
        $connection = Mockery::mock(AMQPStreamConnection::class);
        $connection->shouldReceive('channel')->once()->andReturn($channel);
        $channel->shouldReceive('queue_declare')->once()->with('deleted_documents', false, true, false, false);
        $channel->shouldReceive('confirm_select')->once();
        $channel->shouldReceive('set_return_listener')->once();
        $channel->shouldReceive('set_nack_handler')->once();
        $channel->shouldReceive('basic_publish')->once()->withArgs(function (AMQPMessage $message, string $exchange, string $routingKey, bool $mandatory) {
            $this->assertSame('', $exchange);
            $this->assertSame('deleted_documents', $routingKey);
            $this->assertTrue($mandatory);
            $this->assertSame('application/json', $message->get('content_type'));
            $this->assertSame(AMQPMessage::DELIVERY_MODE_PERSISTENT, $message->get('delivery_mode'));
            $this->assertSame([
                'type' => 'file.deleted',
                'version' => 1,
                'recipient_email' => 'notices@example.test',
                'file_id' => 42,
                'original_name' => 'report.pdf',
                'deleted_at' => '2026-09-24T12:30:00+00:00',
                'deletion_source' => 'manual',
            ], json_decode($message->getBody(), true, flags: JSON_THROW_ON_ERROR));

            return true;
        });
        $channel->shouldReceive('wait_for_pending_acks_returns')->once()->with(5);
        $channel->shouldReceive('close')->once();
        $connection->shouldReceive('close')->once();

        $publisher = new RabbitMqDeletionNotificationPublisher(function (array $settings) use ($connection) {
            $this->assertSame('broker.internal', $settings['host']);
            $this->assertSame(5673, $settings['port']);
            $this->assertSame('/files', $settings['vhost']);

            return $connection;
        });

        $publisher->publish($file, 'manual', new DateTimeImmutable('2026-09-24 12:30:00 UTC'));
    }

    public function test_confirmation_failure_is_reported(): void
    {
        config()->set('services.rabbitmq.queue', 'deleted_documents');
        config()->set('services.rabbitmq.recipient_email', 'notices@example.test');

        $file = new StoredFile(['original_name' => 'report.pdf']);
        $file->id = 42;
        $channel = Mockery::mock(AMQPChannel::class);
        $connection = Mockery::mock(AMQPStreamConnection::class);
        $connection->shouldReceive('channel')->andReturn($channel);
        $channel->shouldReceive('queue_declare');
        $channel->shouldReceive('confirm_select');
        $channel->shouldReceive('set_return_listener');
        $channel->shouldReceive('set_nack_handler');
        $channel->shouldReceive('basic_publish');
        $channel->shouldReceive('wait_for_pending_acks_returns')->andThrow(new RuntimeException('Broker rejected message'));

        $publisher = new RabbitMqDeletionNotificationPublisher(fn () => $connection);

        try {
            $publisher->publish($file, 'expiration', new DateTimeImmutable);
            $this->fail('A failed confirmation must be reported.');
        } catch (DeletionNotificationPublicationException $exception) {
            $this->assertSame('Failed to publish file deletion notification to RabbitMQ.', $exception->getMessage());
            $this->assertSame('Broker rejected message', $exception->getPrevious()->getMessage());
        }
    }

    public function test_missing_recipient_fails_before_connecting(): void
    {
        config()->set('services.rabbitmq.recipient_email', null);
        $publisher = new RabbitMqDeletionNotificationPublisher(fn () => $this->fail('Must not connect'));

        $this->expectException(DeletionNotificationPublicationException::class);
        $this->expectExceptionMessage('RabbitMQ deletion queue and notification recipient email must be configured.');

        $publisher->publish(new StoredFile, 'manual', new DateTimeImmutable);
    }
}
