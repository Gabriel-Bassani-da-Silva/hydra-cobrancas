<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use App\Integrations\Bling\BlingService;
use App\Repositories\PedidoRepository;
use App\Repositories\ContatoRepository;

class BlingWebhookTest extends TestCase
{
    public function test_webhook_receives_empty_payload()
    {
        $response = $this->postJson('/bling/webhook', []);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'ignored',
                     'message' => 'Payload vazio'
                 ]);
    }

    public function test_webhook_processes_conta_receber_event()
    {
        $this->mock(BlingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getContaReceber')
                 ->with(123)
                 ->andReturn(['id' => 123, 'valor' => 100.00]);
        });

        $this->mock(PedidoRepository::class, function (MockInterface $mock) {
            $mock->shouldReceive('importarPedidos')
                 ->once()
                 ->with([['id' => 123, 'valor' => 100.00]], \Mockery::type(BlingService::class), 'upsert');
        });

        $payload = [
            'tipo' => 'conta.receber',
            'data' => [
                ['id' => 123]
            ]
        ];

        $response = $this->postJson('/bling/webhook', $payload);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);
    }

    public function test_webhook_processes_contato_event()
    {
        $this->mock(BlingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getContato')
                 ->with(456)
                 ->andReturn(['id' => 456, 'nome' => 'Test Contact']);
        });

        $this->mock(ContatoRepository::class, function (MockInterface $mock) {
            $mock->shouldReceive('importarContatos')
                 ->once()
                 ->with([['id' => 456, 'nome' => 'Test Contact']]);
        });

        $payload = [
            'tipo' => 'contato',
            'data' => [
                ['id' => 456]
            ]
        ];

        $response = $this->postJson('/bling/webhook', $payload);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);
    }
}
