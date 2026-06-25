<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TelefonesAtualizados implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $idContatoBling;
    public $telefones;

    /**
     * Create a new event instance.
     */
    public function __construct($idContatoBling, $telefones)
    {
        $this->idContatoBling = $idContatoBling;
        $this->telefones = $telefones;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Usar um canal público para facilitar, focado no ID do contato
        return [
            new Channel('contato.' . $this->idContatoBling),
        ];
    }
}
